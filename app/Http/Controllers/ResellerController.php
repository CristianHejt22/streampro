<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Service;
use App\Models\ResellerServiceCommission;
use App\Models\SystemSetting;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ResellerController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $query = User::query();

        if ($user->isAdmin()) {
            $query->whereIn('role', ['reseller', 'super_reseller']);
        } elseif ($user->isSuperReseller()) {
            // El Proveedor ve únicamente a sus revendedores patrocinados/sub-paneles
            $query->where('parent_id', $user->id);
        } else {
            abort(403, 'Acceso denegado al módulo de revendedores.');
        }

        $resellers = $query->with(['parent', 'subResellers', 'customServiceCommissions.service'])
            ->withCount(['sales', 'orders'])
            ->latest()
            ->paginate(20);

        $superResellersList = User::where('role', 'super_reseller')->get();
        $servicesList = Service::where('status', 'active')->get();

        // Preferencias de soporte de WhatsApp para que el Admin las configure
        $whatsappSupportEnabled = SystemSetting::get('whatsapp_support_enabled', 'true') === 'true';
        $whatsappSupportDest = SystemSetting::get('whatsapp_support_dest', 'reseller');
        $whatsappAdminPhone = SystemSetting::get('whatsapp_support_admin_phone', '5491199887766');

        return view('resellers.index', compact(
            'resellers',
            'superResellersList',
            'servicesList',
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
            'panel_type' => 'nullable|in:master,cloned',
        ]);

        $referralCode = 'RES-' . strtoupper(Str::random(6));
        $antiPhishingCode = 'SEC-' . strtoupper(Str::random(8));
        $initialBalance = (float) $request->input('initial_balance', 0.00);
        $panelType = $request->input('panel_type', 'master');
        $panelExpiresAt = ($panelType === 'cloned') ? Carbon::now()->addDays(30) : null;

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
            'panel_monthly_fee' => 15000.00,
            'panel_expires_at' => $panelExpiresAt,
            'reseller_commission_pct' => 40.00, // 40% Ganancia Revendedor
            'parent_commission_pct' => 25.00,   // 25% Ganancia Panel Patrocinador
            'brand_name' => $request->brand_name ?: $request->name . ' Streaming',
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
     * La clonación la paga el proveedor al Administrador General ($15.000 / 30 días),
     * y el revendedor le abona a su proveedor de cuentas por clonar su catálogo.
     */
    public function clonePanel(Request $request, User $reseller)
    {
        $user = auth()->user();
        $fee = 15000.00;

        // Si es Proveedor (Super Reseller), paga la clonación de la tienda al Administrador General
        if ($user->isSuperReseller()) {
            if (!$user->hasBalance($fee)) {
                return back()->withErrors([
                    'error' => "Saldo insuficiente para abonar la clonación de la tienda al Administrador General. Requiere \${$fee} y tu saldo actual es de \${$user->balance}. Por favor recarga saldo antes de habilitar la tienda a tu revendedor."
                ]);
            }

            // Descontar $15.000 al proveedor
            $user->deductBalance($fee);

            // Acreditar $15.000 al Administrador General
            $admin = User::where('role', 'admin')->first();
            if ($admin) {
                $admin->addBalance($fee);
            }

            $reseller->parent_id = $user->id;
            $reseller->cloned_store_paid_to_admin = true;
        } elseif ($user->isAdmin()) {
            $reseller->cloned_store_paid_to_admin = true;
            if ($request->filled('parent_id')) {
                $reseller->parent_id = $request->parent_id;
            }
        }

        $currentExpiry = $reseller->panel_expires_at ? Carbon::parse($reseller->panel_expires_at) : null;
        $newExpiry = ($currentExpiry && $currentExpiry->isFuture()) ? $currentExpiry->addDays(30) : Carbon::now()->addDays(30);

        $reseller->panel_type = 'cloned';
        $reseller->panel_monthly_fee = $fee;
        $reseller->panel_expires_at = $newExpiry;
        $reseller->status = 'active';
        $reseller->cloned_store_last_payment_date = Carbon::now();

        // Estado de cobro del revendedor a su proveedor
        if ($request->has('cloned_store_paid_by_reseller')) {
            $reseller->cloned_store_paid_by_reseller = $request->boolean('cloned_store_paid_by_reseller');
        }

        $reseller->save();

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'clone_store_paid_to_admin',
            'description' => "Clonación de tienda activada para {$reseller->name} por \$15.000 (30 días). Abonado al Administrador General por " . ($user->isSuperReseller() ? "Proveedor {$user->name}" : "Administrador") . ". Vence el {$newExpiry->format('d/m/Y')}.",
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', "¡Tienda y catálogo clonados con éxito para {$reseller->name}! Suscripción activa hasta el {$newExpiry->format('d/m/Y')}. Cobro de \$15.000 liquidado al Administrador General.");
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
}
