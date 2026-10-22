<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Service;
use App\Models\ServicePlan;
use App\Models\CreditTransaction;
use App\Models\ResellerServiceCommission;
use App\Models\ResellerPlanPrice;
use App\Models\BalanceTransfer;
use App\Models\SystemSetting;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ResellerController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = User::query();

        if ($user->isAdmin()) {
            $query->whereIn('role', ['reseller', 'super_reseller']);
        } elseif ($user->isSuperReseller() || $user->canResellPanels()) {
            // El Proveedor o Revendedor PRO ve únicamente a sus revendedores patrocinados/sub-paneles
            $query->where('parent_id', $user->id)->where('role', 'reseller');
        } else {
            abort(403, 'Acceso denegado al módulo de revendedores.');
        }

        // Estricto: NUNCA mostrar cuentas de Admin ni Staff en los paneles
        $query->whereNotIn('role', ['admin', 'staff']);

        // Filtros rápidos del Super Admin / Proveedor
        if ($request->filled('tier') && in_array($request->tier, ['basic', 'pro'])) {
            $query->where('panel_tier', $request->tier);
        }
        if ($request->filled('status') && in_array($request->status, ['active', 'suspended', 'expired', 'pending'])) {
            if ($request->status === 'expired') {
                $query->whereNotNull('panel_expires_at')->where('panel_expires_at', '<', Carbon::now());
            } else {
                $query->where('status', $request->status);
            }
        }
        if ($request->filled('parent_id')) {
            $query->where('parent_id', $request->parent_id);
        }
        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhere('brand_name', 'like', "%{$s}%")
                  ->orWhere('referral_code', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%");
            });
        }

        $resellers = $query->with(['parent', 'subResellers', 'customServiceCommissions.service', 'customPlanPrices.servicePlan'])
            ->withCount(['sales', 'orders'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $superResellersList = User::where('role', 'super_reseller')->get();
        $servicesList = Service::where('status', 'active')->with('plans')->get();
        $servicePlansList = ServicePlan::with('service')->where('is_active', true)->get();

        // Estadísticas clave en tiempo real para el panel de gestión
        $baseCountQuery = $user->isAdmin() 
            ? User::whereIn('role', ['reseller', 'super_reseller']) 
            : User::where('parent_id', $user->id);

        $stats = [
            'total_resellers' => (clone $baseCountQuery)->count(),
            'basic_panels' => (clone $baseCountQuery)->where('panel_tier', 'basic')->count(),
            'pro_panels' => (clone $baseCountQuery)->where(function($q) {
                $q->where('panel_tier', 'pro')->orWhereNull('panel_tier');
            })->count(),
            'active_panels' => (clone $baseCountQuery)->where('status', 'active')->count(),
            'pending_panels' => (clone $baseCountQuery)->where('status', 'pending')->count(),
            'expired_panels' => (clone $baseCountQuery)->where(function($q) {
                $q->where('status', 'suspended')
                  ->orWhere(function($sub) {
                      $sub->whereNotNull('panel_expires_at')->where('panel_expires_at', '<', Carbon::now());
                  });
            })->count(),
            'total_wallets_balance' => (float)(clone $baseCountQuery)->sum('balance'),
            'total_credits_basic' => (int)(clone $baseCountQuery)->sum('credits_basic'),
            'total_credits_pro' => (int)(clone $baseCountQuery)->sum('credits_pro'),
        ];

        // Solicitudes de panel pendientes de activación con crédito
        $pendingRequests = (clone $baseCountQuery)->where('status', 'pending')->with('parent')->latest()->get();

        // Transferencias de saldo pendientes de revisión
        $pendingTransfers = $user->isAdmin()
            ? BalanceTransfer::where('status', 'pending')->with(['reseller', 'provider'])->latest()->get()
            : BalanceTransfer::where('provider_id', $user->id)->where('status', 'pending')->with('reseller')->latest()->get();

        // Preferencias de soporte de WhatsApp para que el Admin las configure
        $whatsappSupportEnabled = SystemSetting::get('whatsapp_support_enabled', 'true') === 'true';
        $whatsappSupportDest = SystemSetting::get('whatsapp_support_dest', 'reseller');
        $whatsappAdminPhone = SystemSetting::get('whatsapp_support_admin_phone', '5491199887766');

        return view('resellers.index', compact(
            'resellers',
            'superResellersList',
            'servicesList',
            'servicePlansList',
            'stats',
            'pendingRequests',
            'pendingTransfers',
            'whatsappSupportEnabled',
            'whatsappSupportDest',
            'whatsappAdminPhone'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:50',
            'password' => 'required|min:6',
            'role' => 'nullable|in:reseller,super_reseller',
            'parent_id' => 'nullable|exists:users,id',
            'reseller_plan' => 'required|in:standard,vip,mayorista',
            'initial_balance' => 'nullable|numeric|min:0',
            'brand_name' => 'nullable|string|max:255',
            'panel_type' => 'nullable|in:master,cloned,individual',
        ]);

        $referralCode = 'RES-' . strtoupper(Str::random(6));
        $antiPhishingCode = 'SEC-' . strtoupper(Str::random(8));
        $initialBalance = (float) $request->input('initial_balance', 0.00);
        $panelType = $request->input('panel_type', 'master');
        $panelExpiresAt = ($panelType === 'cloned') ? Carbon::now()->addDays(30) : null;

        $tier = $request->input('panel_tier', 'basic');
        $brandName = ($tier === 'basic') ? 'SPRO PLAY' : ($request->brand_name ?: $request->name . ' Streaming');

        $reseller = new User([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => $request->input('role', 'reseller'),
            'parent_id' => $request->parent_id,
            'status' => 'active',
            'reseller_plan' => $request->reseller_plan,
            'balance' => $initialBalance,
            'referral_code' => $referralCode,
            'anti_phishing_code' => $antiPhishingCode,
            'panel_type' => $panelType,
            'panel_tier' => $tier,
            'panel_monthly_fee' => 15000.00,
            'panel_expires_at' => $panelExpiresAt,
            'reseller_commission_pct' => 40.00, // 40% Ganancia Revendedor
            'parent_commission_pct' => 25.00,   // 25% Ganancia Panel Patrocinador
            'brand_name' => $brandName,
        ]);

        // Guardar contraseña cifrada reversible con AES-256 para que el Super Admin pueda verla
        $reseller->plain_password = $request->password;
        $reseller->save();

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'create_reseller_panel',
            'description' => "Panel {$reseller->name} ({$reseller->email}) creado como {$reseller->role} ({$panelType}). Sello Anti-Phishing: {$antiPhishingCode}.",
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('resellers.index')->with('success', "Panel de revendedor para {$reseller->name} generado exitosamente (Ref: {$referralCode}). Clave registrada en el Super Panel.");
    }

    public function rechargeBalance(Request $request, User $reseller)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string',
        ]);

        $amount = (float) $request->amount;
        $reseller->addBalance($amount);

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'recharge_balance',
            'description' => "Recarga de saldo a {$reseller->name}: +\${$amount}. Nuevo saldo: \${$reseller->balance}. Notas: {$request->notes}",
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', "Saldo de \${$amount} acreditado exitosamente a {$reseller->name}. Saldo total: \${$reseller->balance}.");
    }

    public function toggleStatus(User $reseller)
    {
        $reseller->status = ($reseller->status === 'active') ? 'suspended' : 'active';
        $reseller->save();

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'toggle_reseller_status',
            'description' => "Estado de {$reseller->name} cambiado a {$reseller->status}.",
            'ip_address' => request()->ip(),
        ]);

        return back()->with('success', "El revendedor ha sido " . ($reseller->status === 'active' ? 'reactivado' : 'suspendido') . ".");
    }

    /**
     * Permite al Super Admin ver la contraseña actual del panel generado
     */
    public function revealPassword(User $reseller)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Solo el Super Administrador puede ver contraseñas de paneles.');
        }

        if ($reseller->isAdmin() || $reseller->role === 'staff') {
            abort(403, 'Acceso denegado. No se permite visualizar credenciales de administradores ni staff.');
        }

        $password = $reseller->plain_password ?: 'Clave inicial encriptada / No registrada';

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'superadmin_view_password',
            'description' => "Super Admin consultó la contraseña del panel de {$reseller->name} ({$reseller->email}).",
            'ip_address' => request()->ip(),
        ]);

        return response()->json([
            'success' => true,
            'name' => $reseller->name,
            'email' => $reseller->email,
            'password' => $password,
            'anti_phishing_code' => $reseller->anti_phishing_code ?: 'NO-CONFIGURADO',
        ]);
    }

    /**
     * Permite al Super Admin cambiar la contraseña de cualquier panel generado
     */
    public function updatePassword(Request $request, User $reseller)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Solo el Super Administrador puede modificar contraseñas de paneles.');
        }

        if ($reseller->isAdmin() || $reseller->role === 'staff') {
            abort(403, 'Acceso denegado. No se permite modificar accesos de administradores ni staff desde este panel.');
        }

        $request->validate([
            'new_password' => 'required|min:6',
        ]);

        $reseller->password = Hash::make($request->new_password);
        $reseller->plain_password = $request->new_password;
        $reseller->save();

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'superadmin_change_reseller_password',
            'description' => "Super Admin actualizó la contraseña del panel de {$reseller->name} ({$reseller->email}).",
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', "¡Contraseña del panel de {$reseller->name} actualizada exitosamente! Nueva clave guardada en el Super Panel.");
    }

    /**
     * Clona o Renueva un sub-panel / tienda para el revendedor.
     * Costo oficial: $15.000 / 30 días.
     * Reparto: $8.000 para el Administrador, $7.000 para el Proveedor.
     */
    public function clonePanel(Request $request, User $reseller)
    {
        $user = auth()->user();
        $totalFee = 15000.00;
        $adminFee = 8000.00;
        $providerFee = 7000.00;

        // Caso 1: Proveedor (Super Reseller) que clona o renueva la tienda para su revendedor afiliado
        if ($user->isSuperReseller()) {
            if ($reseller->parent_id && $reseller->parent_id !== $user->id) {
                abort(403, 'Solo puedes clonar o renovar la tienda de tus propios revendedores afiliados.');
            }

            // Opción A: Cobrar los $15.000 directamente del saldo de billetera del revendedor
            if ($request->boolean('debit_from_reseller_balance')) {
                if (!$reseller->hasBalance($totalFee)) {
                    return back()->withErrors([
                        'error' => "Saldo insuficiente en la billetera del revendedor ({$reseller->name}). Requiere \${$totalFee} y dispone de \${$reseller->balance}. Desmarca la casilla para abonar la cuota de plataforma (\$8.000) desde tu billetera de proveedor."
                    ]);
                }
                $reseller->deductBalance($totalFee);

                // $7.000 se acreditan a la billetera del proveedor
                $user->addBalance($providerFee);

                // $8.000 se acreditan al Administrador General
                $admin = User::where('role', 'admin')->first();
                if ($admin) {
                    $admin->addBalance($adminFee);
                }

                $reseller->cloned_store_paid_by_reseller = true;
            } else {
                // Opción B: El proveedor abona la cuota de plataforma ($8.000) al Administrador General
                if (!$user->hasBalance($adminFee)) {
                    return back()->withErrors([
                        'error' => "Saldo insuficiente para abonar la cuota de la tienda al Administrador General. Requiere \${$adminFee} (de los \$15.000 de la tienda) y tu saldo actual es de \${$user->balance}. Por favor recarga saldo antes de habilitar la tienda a tu revendedor."
                    ]);
                }

                // Descontar $8.000 al proveedor
                $user->deductBalance($adminFee);

                // Acreditar $8.000 al Administrador General
                $admin = User::where('role', 'admin')->first();
                if ($admin) {
                    $admin->addBalance($adminFee);
                }

                // Estado de cobro del revendedor a su proveedor ($15.000 cobrados externamente o pendiente)
                if ($request->has('cloned_store_paid_by_reseller')) {
                    $reseller->cloned_store_paid_by_reseller = $request->boolean('cloned_store_paid_by_reseller');
                }
            }

            $reseller->parent_id = $user->id;
            $reseller->cloned_store_paid_to_admin = true;
        } elseif ($user->isAdmin()) {
            // Caso 2: Super Admin
            $reseller->cloned_store_paid_to_admin = true;
            $reseller->cloned_store_paid_by_reseller = true;
            if ($request->filled('parent_id')) {
                $reseller->parent_id = $request->parent_id;
            }

            // Si tiene proveedor patrocinador y se marcó acreditar la comisión del proveedor ($7.000)
            if ($reseller->parent_id && $request->boolean('credit_provider_share')) {
                $parent = User::find($reseller->parent_id);
                if ($parent) {
                    $parent->addBalance($providerFee);
                }
            }
        } elseif ($user->id === $reseller->id) {
            // Caso 3: El revendedor renueva su propia tienda clonada con su billetera ($15.000)
            if (!$user->hasBalance($totalFee)) {
                return back()->withErrors([
                    'error' => "Saldo insuficiente en tu billetera para renovar tu Tienda Clonada. Se requieren \${$totalFee} y dispones de \${$user->balance}."
                ]);
            }

            $user->deductBalance($totalFee);

            if ($user->parent_id && $parent = User::find($user->parent_id)) {
                $parent->addBalance($providerFee); // $7.000 para el proveedor
                $admin = User::where('role', 'admin')->first();
                if ($admin) {
                    $admin->addBalance($adminFee); // $8.000 para el administrador
                }
            } else {
                $admin = User::where('role', 'admin')->first();
                if ($admin) {
                    $admin->addBalance($totalFee);
                }
            }

            $reseller->cloned_store_paid_to_admin = true;
            $reseller->cloned_store_paid_by_reseller = true;
        } else {
            abort(403);
        }

        $currentExpiry = $reseller->panel_expires_at ? Carbon::parse($reseller->panel_expires_at) : null;
        $newExpiry = ($currentExpiry && $currentExpiry->isFuture()) ? $currentExpiry->addDays(30) : Carbon::now()->addDays(30);

        $reseller->panel_type = 'cloned';
        $reseller->panel_monthly_fee = $totalFee;
        $reseller->panel_expires_at = $newExpiry;
        $reseller->status = 'active';
        $reseller->cloned_store_last_payment_date = Carbon::now();
        $reseller->save();

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'clone_store_paid',
            'description' => "Tienda Clonada activada/renovada para {$reseller->name} ($15.000/mes: $8.000 Admin / $7.000 Proveedor). Vence el {$newExpiry->format('d/m/Y')}.",
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', "¡Tienda y catálogo clonados con éxito para {$reseller->name}! Suscripción activa hasta el {$newExpiry->format('d/m/Y')} (\$15.000/mes: \$8.000 Admin / \$7.000 Proveedor).");
    }

    /**
     * Alterna el estado de cobro de la clonación de tienda entre el revendedor y su proveedor
     */
    public function toggleStorePaidByReseller(User $reseller)
    {
        $user = auth()->user();
        if (!$user->isAdmin() && $reseller->parent_id !== $user->id) {
            abort(403);
        }

        $reseller->cloned_store_paid_by_reseller = !$reseller->cloned_store_paid_by_reseller;
        $reseller->save();

        $statusStr = $reseller->cloned_store_paid_by_reseller ? 'PAGADO por el revendedor al proveedor' : 'PENDIENTE de cobro al revendedor';

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'toggle_store_paid_by_reseller',
            'description' => "Estado de cobro de clonación de catálogo para {$reseller->name} cambiado a: {$statusStr}.",
            'ip_address' => request()->ip(),
        ]);

        return back()->with('success', "Estado de cobro de clonación para {$reseller->name} actualizado a: {$statusStr}.");
    }

    /**
     * Ajusta comisiones específicas por servicio / cuenta para el revendedor
     * (El proveedor configura en su panel comisiones particulares por cada plataforma)
     */
    public function updateServiceCommissions(Request $request, User $reseller)
    {
        $user = auth()->user();
        if (!$user->isAdmin() && $reseller->parent_id !== $user->id) {
            abort(403, 'Solo el proveedor patrocinador o el admin pueden ajustar comisiones de este revendedor.');
        }

        $commissions = $request->input('commissions', []);
        foreach ($commissions as $serviceId => $pct) {
            if ($pct === null || $pct === '') {
                continue;
            }
            $pctFloat = max(0, min(100, (float)$pct));
            ResellerServiceCommission::updateOrCreate(
                ['reseller_id' => $reseller->id, 'service_id' => $serviceId],
                ['parent_id' => $reseller->parent_id ?: $user->id, 'commission_pct' => $pctFloat]
            );
        }

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'update_service_commissions',
            'description' => "El proveedor {$user->name} actualizó las comisiones específicas por cuenta/servicio para el revendedor {$reseller->name}.",
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', "¡Comisiones específicas por cuenta/servicio guardadas exitosamente para {$reseller->name}!");
    }

    /**
     * Ajuste de porcentajes de comisión general (40% Revendedor / 25% Patrocinador)
     */
    public function updateCommissions(Request $request, User $reseller)
    {
        $request->validate([
            'reseller_commission_pct' => 'required|numeric|min:0|max:100',
            'parent_commission_pct' => 'required|numeric|min:0|max:100',
            'panel_monthly_fee' => 'nullable|numeric|min:0',
        ]);

        $reseller->update([
            'reseller_commission_pct' => $request->reseller_commission_pct,
            'parent_commission_pct' => $request->parent_commission_pct,
            'panel_monthly_fee' => $request->input('panel_monthly_fee', 15000.00),
        ]);

        return back()->with('success', "Comisiones del panel actualizadas: Ganancia revendedor {$request->reseller_commission_pct}%, Ganancia patrocinador {$request->parent_commission_pct}%.");
    }

    /**
     * Configuración de la preferencia de Soporte por WhatsApp en Bóveda (Solo Admin)
     */
    public function updateWhatsAppSupportSettings(Request $request)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Solo el Administrador General puede configurar las preferencias de soporte.');
        }

        $request->validate([
            'whatsapp_support_dest' => 'required|in:reseller,admin',
            'whatsapp_support_admin_phone' => 'nullable|string|max:50',
        ]);

        SystemSetting::set('whatsapp_support_enabled', $request->boolean('whatsapp_support_enabled') ? 'true' : 'false');
        SystemSetting::set('whatsapp_support_dest', $request->whatsapp_support_dest);
        if ($request->filled('whatsapp_support_admin_phone')) {
            SystemSetting::set('whatsapp_support_admin_phone', $request->whatsapp_support_admin_phone);
        }

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'update_whatsapp_support_settings',
            'description' => "Preferencia de soporte WhatsApp actualizada en la Bóveda de Clientes. Habilitado: " . ($request->boolean('whatsapp_support_enabled') ? 'SÍ' : 'NO') . ", Destino: {$request->whatsapp_support_dest}.",
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', '¡Preferencias de Soporte de Cuenta por WhatsApp guardadas para todas las Bóvedas de Clientes!');
    }

    /**
     * Venta de Créditos de Activación de Paneles por parte del Super Admin
     * - Básico (10 Servicios): Pack 10 créditos ($2.500 c/u) = $25.000
     * - PRO (Ilimitado): Pack 10 créditos ($4.000 c/u) = $40.000
     * - COMBO DÚO (5 de cada uno juntos): 5 Básicos ($3.000 c/u = $15.000) + 5 PRO ($5.000 c/u = $25.000) = $40.000 (10 créditos en total)
     */
    public function sellCredits(Request $request, User $reseller)
    {
        $user = auth()->user();
        if (!$user->isAdmin()) {
            abort(403, 'Solo el Super Administrador puede vender y asignar créditos de activación de paneles.');
        }

        $request->validate([
            'pack_type' => 'required|in:basic_10,pro_10,combo_5_5,basic_5,pro_5,custom',
            'custom_tier' => 'nullable|required_if:pack_type,custom|in:basic,pro',
            'custom_qty' => 'nullable|required_if:pack_type,custom|integer|min:1',
            'custom_price' => 'nullable|required_if:pack_type,custom|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $packType = $request->pack_type;

        // Caso Combo Dúo: 5 Básicos y 5 PRO juntos
        if ($packType === 'combo_5_5') {
            $notesCustom = $request->input('notes', '');
            $noteBasic = "Combo Dúo: 5 Créditos Básicos ($3.000 c/u = $15.000)" . ($notesCustom ? " - {$notesCustom}" : "");
            $notePro = "Combo Dúo: 5 Créditos PRO ($5.000 c/u = $25.000)" . ($notesCustom ? " - {$notesCustom}" : "");

            $reseller->addCredits('basic', 5, 3000.00, $user->id, $noteBasic);
            $reseller->addCredits('pro', 5, 5000.00, $user->id, $notePro);

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'sell_panel_credits_combo',
                'description' => "Super Admin vendió Pack Combo Dúo (5 Básicos @ \$3.000 + 5 PRO @ \$5.000) a {$reseller->name} por \$40.000.",
                'ip_address' => $request->ip(),
            ]);

            return back()->with('success', "¡Pack Combo Dúo acreditado con éxito! Se sumaron +5 créditos BÁSICO (\$3.000 c/u) y +5 créditos PRO (\$5.000 c/u) a {$reseller->name} (Total: \$40.000 por 10 créditos).");
        }

        $tier = 'basic';
        $qty = 10;
        $unitPrice = 2500.00;

        switch ($packType) {
            case 'basic_10':
                $tier = 'basic';
                $qty = 10;
                $unitPrice = 2500.00;
                break;
            case 'pro_10':
                $tier = 'pro';
                $qty = 10;
                $unitPrice = 4000.00;
                break;
            case 'basic_5':
                $tier = 'basic';
                $qty = 5;
                $unitPrice = 3000.00;
                break;
            case 'pro_5':
                $tier = 'pro';
                $qty = 5;
                $unitPrice = 5000.00;
                break;
            case 'custom':
                $tier = $request->custom_tier;
                $qty = (int)$request->custom_qty;
                $unitPrice = (float)$request->custom_price;
                break;
        }

        $totalPrice = $unitPrice * $qty;
        $notes = $request->input('notes', "Pack de {$qty} créditos {$tier} vendido por Super Admin por $" . number_format($totalPrice, 2));

        $reseller->addCredits($tier, $qty, $unitPrice, $user->id, $notes);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'sell_panel_credits',
            'description' => "Super Admin vendió {$qty} créditos {$tier} a {$reseller->name} por \${$totalPrice} (\${$unitPrice} c/u).",
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', "¡Créditos acreditados exitosamente! Se sumaron +{$qty} créditos " . strtoupper($tier) . " a {$reseller->name} (Total: \${$totalPrice}).");
    }

    /**
     * Activación Maestra Super Admin de Paneles (Poder Absoluto)
     * "El Super admin tendra la activacion de todos los paneles para activar"
     */
    public function activatePanelMaster(Request $request, User $reseller)
    {
        $user = auth()->user();
        if (!$user->isAdmin()) {
            abort(403, 'Solo el Super Administrador puede realizar activaciones maestras directas.');
        }

        $request->validate([
            'panel_tier' => 'required|in:basic,pro',
            'duration_days' => 'required|integer|in:30,60,90,180,360',
            'panel_type' => 'nullable|in:master,cloned,individual',
        ]);

        $days = (int)$request->duration_days;
        $tier = $request->panel_tier;
        $type = $request->input('panel_type', 'cloned');

        $currentExpiry = $reseller->panel_expires_at ? Carbon::parse($reseller->panel_expires_at) : null;
        $newExpiry = ($currentExpiry && $currentExpiry->isFuture()) ? $currentExpiry->addDays($days) : Carbon::now()->addDays($days);

        $reseller->panel_tier = $tier;
        $reseller->panel_type = $type;
        $reseller->status = 'active';
        $reseller->panel_expires_at = $newExpiry;
        $reseller->cloned_store_paid_to_admin = true;
        $reseller->cloned_store_paid_by_reseller = true;
        $reseller->cloned_store_last_payment_date = Carbon::now();
        $reseller->save();

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'activate_panel_master',
            'description' => "Super Admin activó directamente el panel de {$reseller->name} como " . strtoupper($tier) . " por {$days} días hasta {$newExpiry->format('d/m/Y')}.",
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', "¡Panel de {$reseller->name} activado con éxito como " . strtoupper($tier) . " ({$days} días)! Vigente hasta el {$newExpiry->format('d/m/Y')}.");
    }

    /**
     * Activación o Renovación de Panel usando Créditos del Revendedor/Proveedor
     */
    public function activateWithCredit(Request $request, User $reseller)
    {
        $user = auth()->user();
        if (!$user->isAdmin() && $reseller->parent_id !== $user->id && $user->id !== $reseller->id) {
            abort(403, 'No tienes permiso para activar este panel.');
        }

        $request->validate([
            'panel_tier' => 'required|in:basic,pro',
        ]);

        $tier = $request->panel_tier;

        if (!$user->isAdmin()) {
            if (!$user->hasCredits($tier, 1)) {
                $costMsg = ($tier === 'basic') 
                    ? 'Básico ($2.500 c/u mínimo 10, o $3.000 c/u mínimo 5)' 
                    : 'PRO ($4.000 c/u mínimo 10, o $5.000 c/u mínimo 5)';
                return back()->withErrors([
                    'error' => "⛔ Créditos insuficientes. No dispones de créditos " . strtoupper($tier) . " en tu billetera de créditos. Contacta al Super Admin para comprar un pack {$costMsg}."
                ]);
            }
            $user->deductCredits($tier, 1, $reseller->name);
        } else {
            // El Super Admin activa de forma directa e ilimitada
            $user->deductCredits($tier, 1, $reseller->name);
        }

        $currentExpiry = $reseller->panel_expires_at ? Carbon::parse($reseller->panel_expires_at) : null;
        $newExpiry = ($currentExpiry && $currentExpiry->isFuture()) ? $currentExpiry->addDays(30) : Carbon::now()->addDays(30);

        $reseller->panel_tier = $tier;
        $reseller->panel_type = 'cloned';
        $reseller->status = 'active';
        $reseller->panel_expires_at = $newExpiry;
        $reseller->cloned_store_paid_to_admin = true;
        $reseller->cloned_store_paid_by_reseller = true;
        $reseller->cloned_store_last_payment_date = Carbon::now();
        $reseller->save();

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'activate_panel_credit',
            'description' => "Panel de {$reseller->name} activado consumiendo 1 crédito " . strtoupper($tier) . " de {$user->name}. Vence el {$newExpiry->format('d/m/Y')}.",
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', "¡Panel " . strtoupper($tier) . " de {$reseller->name} activado con éxito! Se descontó 1 crédito {$tier}. Vigente por 30 días hasta el {$newExpiry->format('d/m/Y')}.");
    }

    /**
     * Descartar / Rechazar una solicitud de panel pendiente
     */
    public function rejectPendingRequest(User $reseller)
    {
        $user = auth()->user();
        if (!$user->isAdmin() && $reseller->parent_id !== $user->id) {
            abort(403, 'No tienes permiso para descartar esta solicitud.');
        }

        if ($reseller->status !== 'pending') {
            return back()->with('error', 'Solo se pueden descartar solicitudes en estado pendiente.');
        }

        $name = $reseller->name;
        $reseller->delete();

        return back()->with('info', "Solicitud de panel de {$name} descartada.");
    }

    /**
     * Alternar si el Proveedor desea que aparezca su WhatsApp o el de su revendedor clonado
     */
    public function toggleProviderWhatsAppOverride(User $reseller)
    {
        $user = auth()->user();
        $target = $reseller->isSuperReseller() ? $reseller : ($reseller->parent ?: $reseller);
        if (!$user->isAdmin() && $user->id !== $target->id) {
            abort(403);
        }

        $target->provider_override_support_whatsapp = !$target->provider_override_support_whatsapp;
        $target->save();

        $statusStr = $target->provider_override_support_whatsapp 
            ? 'ACTIVADO: En la Bóveda de los clientes de tus revendedores aparecerá TU WhatsApp de Proveedor.' 
            : 'DESACTIVADO: En la Bóveda de los clientes de tus revendedores aparecerá el WhatsApp individual de cada revendedor.';

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'toggle_provider_whatsapp_override',
            'description' => "Preferencia de WhatsApp para Bóvedas de revendedores cambiada: {$statusStr}",
            'ip_address' => request()->ip(),
        ]);

        return back()->with('success', $statusStr);
    }

    /**
     * Alternar si un sub-revendedor tiene habilitado ofrecer la reventa de paneles en su catálogo
     */
    public function togglePanelOffer(User $reseller)
    {
        $user = auth()->user();
        if (!$user->isAdmin() && $reseller->parent_id !== $user->id && $user->id !== $reseller->id) {
            abort(403, 'Solo el patrocinador, el propietario o el Super Admin pueden modificar este permiso.');
        }

        if (!$reseller->canResellPanels()) {
            return back()->with('error', 'Este panel es de nivel BÁSICO. Solo los paneles de nivel PRO tienen la capacidad técnica de revender acceso a paneles.');
        }

        $reseller->offer_panels_on_catalog = !$reseller->isPanelSalesOffered();
        $reseller->save();

        $stateMsg = $reseller->offer_panels_on_catalog 
            ? "HABILITADA: {$reseller->name} ahora puede ofrecer suscripciones a paneles en su landing page con comisión por venta." 
            : "DESHABILITADA: Se ha pausado la oferta de paneles en la landing page de {$reseller->name}.";

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'toggle_reseller_panel_offer',
            'description' => $stateMsg,
            'ip_address' => request()->ip(),
        ]);

        return back()->with('success', $stateMsg);
    }

    /**
     * Muestra el formulario público de afiliación de revendedor enviado por el Proveedor
     */
    public function showAffiliateRegister($code)
    {
        $provider = User::where('referral_code', $code)
            ->orWhere('id', $code)
            ->first();

        if (!$provider || (!$provider->isReseller() && !$provider->isAdmin())) {
            abort(404, 'Enlace de afiliación no válido o el proveedor ya no se encuentra activo.');
        }

        // El Básico NO revende ningún plan de panel; solo PRO o Proveedor
        if (!$provider->canResellPanels()) {
            abort(403, 'Este panel no tiene habilitada la reventa de funciones de panel a revendedores (exclusivo para Paneles PRO).');
        }

        return view('portal.affiliate_register', compact('provider'));
    }

    /**
     * Procesa el registro del revendedor desde el formulario enviado por el Proveedor
     */
    public function processAffiliateRegister(Request $request, $code)
    {
        $provider = User::where('referral_code', $code)
            ->orWhere('id', $code)
            ->firstOrFail();

        // El Básico NO revende ningún plan de panel
        if (!$provider->canResellPanels()) {
            abort(403, 'Este panel no tiene habilitada la reventa de funciones de panel.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'support_whatsapp' => 'required|string|max:50',
            'panel_tier' => 'required|in:basic,pro',
            'panel_type' => 'nullable|in:cloned,individual',
            'brand_name' => 'nullable|string|max:100',
        ]);

        $tier = $request->panel_tier;
        $panelType = $request->input('panel_type', 'cloned');
        $brandName = ($tier === 'basic') ? 'SPRO PLAY' : ($request->input('brand_name') ?: 'Mi Tienda Streaming');
        $referralCode = 'RES-' . strtoupper(Str::random(6));
        $antiPhishingCode = 'SEC-' . strtoupper(Str::random(8));

        // Se registra como PENDIENTE de activación con crédito por el Administrador / Proveedor
        $reseller = new User([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->support_whatsapp,
            'support_whatsapp' => $request->support_whatsapp,
            'password' => Hash::make($request->password),
            'role' => 'reseller',
            'parent_id' => $provider->id,
            'status' => 'pending', // PENDIENTE DE ACTIVACIÓN
            'reseller_plan' => $tier === 'pro' ? 'mayorista' : 'standard',
            'balance' => 0.00,
            'referral_code' => $referralCode,
            'anti_phishing_code' => $antiPhishingCode,
            'panel_type' => $panelType,
            'panel_tier' => $tier,
            'panel_monthly_fee' => ($tier === 'pro') ? 6000.00 : 4000.00,
            'panel_expires_at' => null, // Se fija al activar con crédito
            'cloned_store_paid_to_admin' => false,
            'cloned_store_paid_by_reseller' => false,
            'reseller_commission_pct' => 40.00,
            'parent_commission_pct' => 25.00,
            'brand_name' => $brandName,
            'credits_basic' => 0,
            'credits_pro' => 0,
        ]);

        $reseller->plain_password = $request->password;
        $reseller->save();

        AuditLog::create([
            'user_id' => $provider->id,
            'action' => 'affiliate_request_reseller',
            'description' => "Nueva solicitud de panel {$reseller->name} ({$reseller->email}) para {$provider->name} (Plan " . strtoupper($tier) . "). Pendiente de activación con crédito.",
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('login')->with('info', "¡Solicitud de Panel " . strtoupper($tier) . " ({$brandName}) enviada con éxito! Tu registro ha quedado guardado y PENDIENTE DE ACTIVACIÓN. El Administrador o Proveedor ({$provider->name}) activará tu panel mediante créditos de plataforma.");
    }

    /**
     * El Proveedor ajusta sus precios de costo mayorista para su revendedor
     */
    public function updatePlanPrices(Request $request, User $reseller)
    {
        $user = auth()->user();
        if (!$user->isAdmin() && $reseller->parent_id !== $user->id) {
            abort(403, 'Solo el proveedor patrocinador o el Super Admin pueden ajustar precios mayoristas.');
        }

        $costs = $request->input('provider_costs', []);
        foreach ($costs as $planId => $cost) {
            if ($cost === null || $cost === '') continue;
            $costVal = max(0, (float)$cost);
            ResellerPlanPrice::updateOrCreate(
                ['reseller_id' => $reseller->id, 'service_plan_id' => $planId],
                ['parent_id' => $reseller->parent_id ?: $user->id, 'provider_cost' => $costVal]
            );
        }

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'update_reseller_plan_prices',
            'description' => "Precios mayoristas de proveedor ajustados para {$reseller->name}.",
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', "¡Precios de costo de proveedor guardados exitosamente para {$reseller->name}!");
    }

    /**
     * El Revendedor define sus propios precios al público (PVP) para su tienda
     */
    public function updateMyPvp(Request $request)
    {
        $user = auth()->user();
        if (!$user->isReseller() && !$user->isAdmin()) {
            abort(403);
        }

        $pvps = $request->input('reseller_pvps', []);
        foreach ($pvps as $planId => $pvp) {
            if ($pvp === null || $pvp === '') continue;
            $pvpVal = max(0, (float)$pvp);
            ResellerPlanPrice::updateOrCreate(
                ['reseller_id' => $user->id, 'service_plan_id' => $planId],
                ['parent_id' => $user->parent_id, 'reseller_pvp' => $pvpVal]
            );
        }

        return back()->with('success', '¡Tus precios de venta al público (PVP) han sido actualizados en tu tienda!');
    }

    /**
     * El Revendedor notifica una transferencia de dinero a su Proveedor para recargar saldo
     */
    public function storeBalanceTransfer(Request $request)
    {
        $user = auth()->user();
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|string|max:50',
            'reference_code' => 'nullable|string|max:100',
            'receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
            'reseller_notes' => 'nullable|string|max:500',
        ]);

        $providerId = $user->parent_id ?: User::where('role', 'admin')->first()->id;

        $receiptPath = null;
        if ($request->hasFile('receipt')) {
            $receiptPath = $request->file('receipt')->store('balance_transfers', 'public');
        }

        $transfer = BalanceTransfer::create([
            'reseller_id' => $user->id,
            'provider_id' => $providerId,
            'amount' => (float)$request->amount,
            'payment_method' => $request->payment_method,
            'receipt_path' => $receiptPath,
            'reference_code' => $request->reference_code,
            'status' => 'pending',
            'reseller_notes' => $request->reseller_notes,
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'submit_balance_transfer',
            'description' => "Revendedor {$user->name} notificó transferencia de \${$transfer->amount} a su proveedor (Ref: {$transfer->reference_code}).",
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', "¡Transferencia de \${$transfer->amount} enviada con éxito! Tu proveedor revisará el comprobante para acreditarte saldo de inmediato.");
    }

    /**
     * El Proveedor aprueba la transferencia recibida y acredita el saldo a su revendedor
     */
    public function approveBalanceTransfer(BalanceTransfer $transfer)
    {
        $user = auth()->user();
        if (!$user->isAdmin() && $transfer->provider_id !== $user->id) {
            abort(403);
        }

        if ($transfer->status !== 'pending') {
            return back()->with('error', 'Esta transferencia ya fue procesada anteriormente.');
        }

        $transfer->status = 'approved';
        $transfer->processed_at = Carbon::now();
        $transfer->save();

        // Acreditar saldo a la billetera del revendedor
        $transfer->reseller->addBalance($transfer->amount);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'approve_balance_transfer',
            'description' => "Proveedor {$user->name} aprobó transferencia #{$transfer->id} de \${$transfer->amount} para {$transfer->reseller->name}. Saldo acreditado.",
            'ip_address' => request()->ip(),
        ]);

        return back()->with('success', "¡Transferencia aprobada! Se acreditaron \${$transfer->amount} en la billetera de {$transfer->reseller->name} para entregas rápidas.");
    }

    /**
     * El Proveedor rechaza una transferencia recibida
     */
    public function rejectBalanceTransfer(Request $request, BalanceTransfer $transfer)
    {
        $user = auth()->user();
        if (!$user->isAdmin() && $transfer->provider_id !== $user->id) {
            abort(403);
        }

        $transfer->status = 'rejected';
        $transfer->provider_notes = $request->input('reason', 'Comprobante no válido o pago no acreditado.');
        $transfer->processed_at = Carbon::now();
        $transfer->save();

        return back()->with('info', "Transferencia rechazada para {$transfer->reseller->name}.");
    }

    /**
     * Super Admin, Proveedor o Revendedor ajusta la preferencia de WhatsApp para la Bóveda de un panel
     */
    public function updatePanelWhatsAppPreference(Request $request, User $reseller)
    {
        $user = auth()->user();
        if (!$user->isAdmin() && $user->id !== $reseller->id && $reseller->parent_id !== $user->id) {
            abort(403);
        }

        $request->validate([
            'whatsapp_support_preference' => 'required_without:preference|in:auto,reseller,provider,admin',
            'preference' => 'nullable|in:auto,reseller,provider,admin',
            'support_whatsapp' => 'nullable|string|max:50',
        ]);

        $pref = $request->input('whatsapp_support_preference') ?: $request->input('preference');
        $reseller->whatsapp_support_preference = $pref;
        if ($request->filled('support_whatsapp')) {
            $reseller->support_whatsapp = $request->support_whatsapp;
        }
        $reseller->save();

        $destLabel = match($pref) {
            'provider' => 'WhatsApp de su Proveedor Patrocinador (' . ($reseller->parent ? $reseller->parent->name . ' - ' . $reseller->parent->support_whatsapp : 'Admin') . ')',
            'reseller' => 'WhatsApp Propio del Revendedor (' . ($reseller->support_whatsapp ?: 'Sin número') . ')',
            'admin' => 'WhatsApp Central del Super Admin',
            default => 'Modo Automático'
        };

        // Auditoría para quien ejecutó el cambio
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'update_panel_whatsapp_preference',
            'description' => "Preferencia de WhatsApp para panel de {$reseller->name} actualizada a: {$destLabel}.",
            'ip_address' => $request->ip(),
        ]);

        // Si el cambio lo hizo el revendedor y tiene proveedor, notificar al proveedor
        if ($reseller->parent_id && $reseller->parent_id !== $user->id) {
            AuditLog::create([
                'user_id' => $reseller->parent_id,
                'action' => 'reseller_panel_whatsapp_notice',
                'description' => "Tu revendedor {$reseller->name} solicitó atención de Bóveda: {$destLabel}.",
                'ip_address' => $request->ip(),
            ]);
        }

        // Si el cambio lo hizo el proveedor/admin y no el revendedor, registrar en la auditoría del revendedor
        if ($user->id !== $reseller->id) {
            AuditLog::create([
                'user_id' => $reseller->id,
                'action' => 'provider_panel_whatsapp_notice',
                'description' => "Tu patrocinador {$user->name} configuró la atención de WhatsApp para tu Bóveda: {$destLabel}.",
                'ip_address' => $request->ip(),
            ]);
        }

        return back()->with('success', "¡Preferencia de WhatsApp para la Bóveda de {$reseller->name} actualizada con éxito ({$pref})!");
    }

    /**
     * Permite a un Revendedor adquirir / ascender su panel a Panel Administrador / Proveedor (Super Reseller)
     */
    public function upgradeToSuperReseller(Request $request)
    {
        $user = auth()->user();
        if (!$user->isReseller() || $user->isAdmin()) {
            return back()->with('info', 'Ya cuentas con las funciones de Administrador / Proveedor.');
        }

        if ($user->isSuperReseller()) {
            return back()->with('info', 'Tu panel ya cuenta con el rol de Proveedor (Super Reseller).');
        }

        $cost = 15000.00;
        $adminFee = 8000.00;
        $providerFee = 7000.00;

        if ($user->balance < $cost) {
            return back()->with('error', "Saldo insuficiente en tu billetera (\${$user->balance} disponible). Se requieren \$" . number_format($cost, 2) . " para adquirir el Panel Administrador. Puedes recargar saldo transfiriendo a tu proveedor.");
        }

        $user->deductBalance($cost);

        // Reparto oficial de Tienda / Panel: $8.000 para Administrador, $7.000 para Proveedor patrocinador
        if ($user->parent_id && $parent = User::find($user->parent_id)) {
            $parent->addBalance($providerFee);
            $admin = User::where('role', 'admin')->first();
            if ($admin) {
                $admin->addBalance($adminFee);
            }
        } else {
            $admin = User::where('role', 'admin')->first();
            if ($admin) {
                $admin->addBalance($cost);
            }
        }

        $user->role = 'super_reseller';
        $user->panel_tier = 'pro'; // Al ascender a proveedor se le habilita PRO ilimitado
        $user->panel_type = 'cloned';
        $user->panel_expires_at = ($user->panel_expires_at && Carbon::parse($user->panel_expires_at)->isFuture())
            ? Carbon::parse($user->panel_expires_at)->addDays(30)
            : Carbon::now()->addDays(30);
        $user->cloned_store_paid_to_admin = true;
        $user->cloned_store_paid_by_reseller = true;
        $user->save();

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'upgrade_to_super_reseller',
            'description' => "Revendedor {$user->name} adquirió Panel Administrador / Proveedor por \$" . number_format($cost, 2) . " (\$8.000 Admin / \$7.000 Proveedor). Descontado de su billetera.",
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', '¡Felicitaciones! Has activado tu Panel Administrador / Proveedor. Ahora puedes dar de alta a tus revendedores, clonar tu catálogo y ganar el 25% de comisión.');
    }

    /**
     * Super Admin cambia el rol de un usuario entre reseller y super_reseller con 1 clic
     */
    public function toggleRole(User $reseller)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Solo el Super Administrador puede cambiar roles de paneles.');
        }

        if ($reseller->isAdmin() || $reseller->role === 'staff') {
            abort(403, 'No se puede modificar el rol de un Administrador o Staff.');
        }

        $newRole = ($reseller->role === 'super_reseller') ? 'reseller' : 'super_reseller';
        $reseller->role = $newRole;
        if ($newRole === 'super_reseller') {
            $reseller->panel_tier = 'pro';
        }
        $reseller->save();

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'toggle_reseller_role',
            'description' => "Super Admin cambió el rol de {$reseller->name} a " . strtoupper($newRole) . ".",
            'ip_address' => request()->ip(),
        ]);

        $roleLabel = ($newRole === 'super_reseller') ? 'Proveedor / Super Reseller' : 'Revendedor Estándar';
        return back()->with('success', "Rol de {$reseller->name} actualizado a {$roleLabel}.");
    }
}
