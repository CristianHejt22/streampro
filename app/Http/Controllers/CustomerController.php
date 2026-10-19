<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Customer;
use App\Models\ServicePlan;
use App\Models\AccountProfile;
use App\Models\StreamingAccount;
use App\Models\Sale;
use App\Models\AuditLog;
use Carbon\Carbon;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Customer::with(['user', 'activeSales.service', 'activeSales.profile'])
            ->withCount(['sales', 'activeSales']);

        if ($user->isReseller()) {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('customer_code', 'like', "%{$search}%");
            });
        }

        $customers = $query->latest()->paginate(15)->withQueryString();
        $availablePlans = ServicePlan::with('service')->where('is_active', true)->get();

        return view('customers.index', compact('customers', 'availablePlans'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'email' => 'required|email|max:255|unique:customers,email',
            'password' => 'nullable|string|min:6',
            'country' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ], [
            'email.unique' => 'Este correo ya se encuentra registrado para otro cliente.',
        ]);

        $validated['user_id'] = Auth::id();
        $validated['customer_code'] = 'CLI-' . rand(1000, 9999);
        $rawPassword = $request->filled('password') ? $request->password : 'Pass' . rand(1000, 9999) . '*';
        $validated['password'] = Hash::make($rawPassword);
        $validated['status'] = 'active';

        $customer = Customer::create($validated);

        return back()->with('success', "Cliente {$customer->name} registrado exitosamente. Contraseña de Bóveda: {$rawPassword}");
    }

    public function update(Request $request, Customer $customer)
    {
        $user = Auth::user();
        if ($user->isReseller() && $customer->user_id !== $user->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'email' => 'required|email|max:255|unique:customers,email,' . $customer->id,
            'country' => 'nullable|string|max:100',
            'status' => 'required|in:active,suspended',
            'notes' => 'nullable|string',
        ]);

        $customer->update($validated);

        return back()->with('success', 'Datos del cliente actualizados.');
    }

    /**
     * Activación directa de cuenta/licencia de streaming para el cliente
     */
    public function activateAccount(Request $request, Customer $customer)
    {
        $user = Auth::user();
        if ($user->isReseller() && $customer->user_id !== $user->id) {
            abort(403);
        }

        $request->validate([
            'service_plan_id' => 'required|exists:service_plans,id',
            'custom_price' => 'nullable|numeric|min:0',
            'months_duration' => 'nullable|integer|in:1,2,3,6,12',
            'is_consignment' => 'nullable|boolean',
        ]);

        $months = max(1, (int) $request->input('months_duration', 1));
        $isConsignment = $request->boolean('is_consignment');

        $plan = ServicePlan::with('service')->findOrFail($request->service_plan_id);
        $service = $plan->service;

        $basePrice = $request->filled('custom_price') ? (float)$request->custom_price : (float)$plan->price;
        $price = $basePrice * $months;
        $baseResellerPrice = (float)($plan->reseller_price > 0 ? $plan->reseller_price : $plan->price);
        $cost = ($user->isReseller() ? $baseResellerPrice : (float)($plan->cost_estimate ?: 0)) * $months;
        $profit = $price - $cost;

        // Comisión del 25% para el panel que ofreció/clonó el servicio
        $parentCommission = 0.00;
        if ($user->parent_id && $user->parent) {
            $pct = (float) ($user->parent_commission_pct ?: 25.00);
            $parentCommission = round($profit * ($pct / 100), 2);
        }

        // Descontar saldo si el emisor es revendedor y NO es consignación
        if ($user->isReseller() && !$isConsignment) {
            if (!$user->hasBalance($cost)) {
                return back()->withErrors(['error' => "Saldo insuficiente en tu billetera. Requiere \${$cost} para {$months} mes(es) y tu saldo es de \${$user->balance}. Puedes activar en Consignación a 48hs."]);
            }
            $user->deductBalance($cost);

            if ($parentCommission > 0 && $user->parent) {
                $user->parent->addBalance($parentCommission);
            }
        }

        // Buscar stock disponible
        $streamingAccount = null;
        $profile = null;

        if ($plan->modality === 'profile') {
            $profile = AccountProfile::whereHas('streamingAccount', function ($q) use ($service) {
                $q->where('service_id', $service->id)->where('status', 'active');
            })->where('status', 'available')->first();

            if (!$profile) {
                return back()->withErrors(['error' => "No hay pantallas disponibles en stock para {$service->name}. Reabastece cuentas en el Inventario primero."]);
            }

            $streamingAccount = $profile->streamingAccount;
            $profile->update(['status' => 'assigned']);
        } else {
            $streamingAccount = StreamingAccount::where('service_id', $service->id)
                ->where('modality', 'full_account')
                ->where('status', 'active')
                ->whereDoesntHave('sales', function ($q) {
                    $q->whereIn('status', ['active', 'expiring_soon']);
                })->first();

            if (!$streamingAccount) {
                return back()->withErrors(['error' => "No hay cuentas completas disponibles en stock para {$service->name}."]);
            }
        }

        $startDate = Carbon::today();
        $endDate = Carbon::today()->addMonths($months);
        $saleCode = 'CST-' . date('Ymd') . '-' . strtoupper(Str::random(4));

        $sale = Sale::create([
            'sale_code' => $saleCode,
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'service_plan_id' => $plan->id,
            'streaming_account_id' => $streamingAccount->id,
            'account_profile_id' => $profile ? $profile->id : null,
            'modality' => $plan->modality,
            'account_email_snapshot' => $streamingAccount->account_email,
            'account_password_snapshot' => $streamingAccount->account_password,
            'profile_name_snapshot' => $profile ? $profile->profile_name : 'Cuenta Completa',
            'profile_pin_snapshot' => $profile ? $profile->pin : null,
            'usage_rules_snapshot' => $service->default_rules,
            'sale_price' => $price,
            'cost_price' => $cost,
            'profit' => $profit,
            'parent_commission_amount' => $parentCommission,
            'months_duration' => $months,
            'is_consignment' => $isConsignment,
            'is_consignment_paid' => !$isConsignment,
            'consignment_expires_at' => $isConsignment ? Carbon::now()->addHours(48) : null,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'status' => 'active',
            'notes' => 'Activada para el cliente ' . $customer->name . ($isConsignment ? ' [CONSIGNACIÓN 48H]' : ''),
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'activate_customer_service',
            'description' => "Servicio {$service->name} activado para cliente {$customer->name} ({$customer->email}). Venta {$saleCode}.",
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', "¡Activación completada con éxito! {$service->name} asignado a {$customer->name}. Las credenciales ya están visibles en su Bóveda.");
    }

    /**
     * Reseteo de contraseña de la Bóveda del Cliente
     */
    public function resetPassword(Request $request, Customer $customer)
    {
        $user = Auth::user();
        if ($user->isReseller() && $customer->user_id !== $user->id) {
            abort(403);
        }

        $newPassword = $request->filled('password') ? $request->password : 'Pass' . rand(1000, 9999) . '!';
        $customer->update([
            'password' => Hash::make($newPassword),
        ]);

        return back()->with('success', "Contraseña de Bóveda para {$customer->name} restablecida a: {$newPassword}");
    }

    public function show(Customer $customer)
    {
        $user = Auth::user();
        if ($user->isReseller() && $customer->user_id !== $user->id) {
            abort(403);
        }

        $customer->load(['sales.service', 'sales.profile', 'sales.warranties']);
        return view('customers.show', compact('customer'));
    }

    public function destroy(Customer $customer)
    {
        $user = Auth::user();
        if ($user->isReseller() && $customer->user_id !== $user->id) {
            abort(403);
        }

        $customer->delete();
        return back()->with('success', 'Cliente eliminado de la agenda.');
    }
}
