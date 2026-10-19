<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ResellerController extends Controller
{
    public function index()
    {
        $resellers = User::whereIn('role', ['reseller', 'super_reseller'])
            ->with(['parent', 'subResellers'])
            ->withCount(['sales', 'orders'])
            ->latest()
            ->paginate(20);

        $superResellersList = User::where('role', 'super_reseller')->get();

        return view('resellers.index', compact('resellers', 'superResellersList'));
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
     * Clona o Renueva un sub-panel como copia por $15.000 x 30 días
     */
    public function clonePanel(Request $request, User $reseller)
    {
        $reseller->panel_type = 'cloned';
        $reseller->panel_monthly_fee = 15000.00;
        $reseller->panel_expires_at = Carbon::now()->addDays(30);
        $reseller->reseller_commission_pct = 40.00; // 40% Ganancia Revendedor
        $reseller->parent_commission_pct = 25.00;   // 25% Ganancia Panel Patrocinador
        $reseller->status = 'active';

        if ($request->filled('parent_id')) {
            $reseller->parent_id = $request->parent_id;
        }

        $reseller->save();

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'clone_panel_subscription',
            'description' => "Panel clonado activado para {$reseller->name} por \$15.000 (30 días). Ganancia revendedor 40%, Ganancia patrocinador 25%.",
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', "¡Panel clonado configurado para {$reseller->name}! Suscripción activa por 30 días (\$15.000). Margen revendedor 40%, comisión patrocinador 25%.");
    }

    /**
     * Ajuste de porcentajes de comisión (40% Revendedor / 25% Patrocinador)
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
}
