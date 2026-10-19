<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ResellerController extends Controller
{
    public function index()
    {
        $resellers = User::where('role', 'reseller')
            ->withCount(['sales', 'orders'])
            ->latest()
            ->paginate(15);

        return view('resellers.index', compact('resellers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:50',
            'password' => 'required|min:6',
            'reseller_plan' => 'required|in:standard,vip,mayorista',
            'initial_balance' => 'nullable|numeric|min:0',
            'brand_name' => 'nullable|string|max:255',
        ]);

        $referralCode = 'RES-' . strtoupper(Str::random(6));
        $initialBalance = (float) $request->input('initial_balance', 0.00);

        $reseller = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => 'reseller',
            'status' => 'active',
            'reseller_plan' => $request->reseller_plan,
            'balance' => $initialBalance,
            'referral_code' => $referralCode,
            'brand_name' => $request->brand_name ?: $request->name . ' Streaming',
        ]);

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'create_reseller',
            'description' => "Revendedor {$reseller->name} ({$reseller->email}) creado con Plan {$reseller->reseller_plan} y Saldo inicial: \${$initialBalance}.",
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('resellers.index')->with('success', "Revendedor {$reseller->name} creado exitosamente con código de referido {$referralCode}.");
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

    public function resetPassword(Request $request, User $reseller)
    {
        $request->validate([
            'new_password' => 'required|min:6',
        ]);

        $reseller->password = Hash::make($request->new_password);
        $reseller->save();

        return back()->with('success', "Contraseña de {$reseller->name} actualizada correctamente.");
    }
}
