<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Customer;
use App\Models\User;
use App\Models\Sale;
use App\Models\Service;
use App\Models\ServicePlan;
use App\Models\Warranty;
use App\Models\AuditLog;

class CustomerPortalController extends Controller
{
    /**
     * Muestra la página de registro de cliente (pública o bajo enlace de revendedor)
     */
    public function showRegister(Request $request, ?string $code = null)
    {
        if (Auth::guard('customer')->check()) {
            return redirect()->route('boveda.index');
        }

        $refCode = $code ?: $request->query('ref');
        $reseller = null;

        if ($refCode) {
            $reseller = User::where('referral_code', $refCode)->first();
        }

        if (!$reseller) {
            $reseller = User::where('role', 'admin')->first();
        }

        // Obtener los 4 servicios principales de streaming con sus planes
        $services = Service::where('status', 'active')
            ->with(['plans' => function ($q) {
                $q->where('is_active', true);
            }])
            ->get();

        return view('portal.register', compact('reseller', 'services', 'refCode'));
    }

    /**
     * Procesa el registro de un nuevo cliente con correo y contraseña
     */
    public function processRegister(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:customers,email',
            'phone' => 'required|string|max:50',
            'password' => 'required|string|min:6|confirmed',
            'reseller_id' => 'nullable|exists:users,id',
            'plan_id' => 'nullable|exists:service_plans,id',
        ], [
            'email.unique' => 'Ya existe una cuenta registrada con este correo electrónico. Inicia sesión en tu Bóveda.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
        ]);

        $resellerId = $request->reseller_id ?: User::where('role', 'admin')->value('id');

        $customer = Customer::create([
            'user_id' => $resellerId,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'status' => 'active',
        ]);

        // Autenticar de inmediato en el guard de clientes
        Auth::guard('customer')->login($customer);

        AuditLog::create([
            'user_id' => $resellerId,
            'action' => 'customer_registered',
            'description' => "Nuevo cliente registrado: {$customer->name} ({$customer->email}) asignado a vendedor ID {$resellerId}.",
            'ip_address' => $request->ip(),
        ]);

        // Si seleccionó un plan para adquirir de inmediato, redirigir a checkout
        if ($request->filled('plan_id')) {
            return redirect()->route('catalog.checkout', ['plan' => $request->plan_id])
                ->with('success', '¡Cuenta creada con éxito! Ahora completa la activación de tu plan.');
        }

        return redirect()->route('boveda.index')
            ->with('success', "¡Bienvenido a tu Bóveda de Streaming, {$customer->name}! Aquí podrás consultar tus accesos y perfiles.");
    }

    /**
     * Muestra la pantalla de inicio de sesión para clientes
     */
    public function showLogin()
    {
        if (Auth::guard('customer')->check()) {
            return redirect()->route('boveda.index');
        }

        return view('portal.login');
    }

    /**
     * Procesa el login del cliente mediante correo y contraseña
     */
    public function processLogin(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (Auth::guard('customer')->attempt($credentials, $request->filled('remember'))) {
            $request->session()->regenerate();
            $customer = Auth::guard('customer')->user();

            if ($customer->status === 'suspended') {
                Auth::guard('customer')->logout();
                return back()->withErrors(['email' => 'Tu cuenta se encuentra suspendida temporalmente. Contacta a tu distribuidor.']);
            }

            return redirect()->route('boveda.index')
                ->with('success', "¡Hola de nuevo, {$customer->name}! Tus servicios de streaming están activos.");
        }

        return back()->withErrors([
            'email' => 'El correo o la contraseña no coinciden con los registros de la Bóveda.',
        ])->withInput($request->only('email'));
    }

    /**
     * Cierra la sesión del cliente
     */
    public function logout(Request $request)
    {
        Auth::guard('customer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('boveda.login')
            ->with('success', 'Has cerrado sesión correctamente de tu Bóveda Privada.');
    }

    /**
     * Bóveda Privada de Credenciales de Streaming del Cliente
     */
    public function index()
    {
        $customer = Auth::guard('customer')->user();
        if (!$customer) {
            return redirect()->route('boveda.login');
        }

        // Obtener ventas activas del cliente con credenciales snapshot y perfiles
        $activeSales = Sale::with(['service', 'plan', 'streamingAccount', 'profile'])
            ->where('customer_id', $customer->id)
            ->whereIn('status', ['active', 'expiring_soon'])
            ->latest()
            ->get();

        // Historial de ventas anteriores o vencidas
        $historySales = Sale::with(['service', 'plan'])
            ->where('customer_id', $customer->id)
            ->whereNotIn('status', ['active', 'expiring_soon'])
            ->latest()
            ->take(5)
            ->get();

        // Datos del revendedor asignado (Marca blanca, WhatsApp, Logo)
        $reseller = $customer->user ?: User::where('role', 'admin')->first();

        // Catálogo de plataformas principales para activar nuevas cuentas
        $catalogServices = Service::where('status', 'active')
            ->with(['plans' => function ($q) {
                $q->where('is_active', true);
            }])
            ->get();

        return view('portal.boveda', compact('customer', 'activeSales', 'historySales', 'reseller', 'catalogServices'));
    }

    /**
     * Reporte de Caída o Falla desde la Bóveda del Cliente
     */
    public function reportIssue(Request $request, Sale $sale)
    {
        $customer = Auth::guard('customer')->user();

        if ($sale->customer_id !== $customer->id) {
            abort(403, 'No tienes permiso para reportar incidencias en esta cuenta.');
        }

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        // Registrar Garantía en estado pendiente
        Warranty::create([
            'sale_id' => $sale->id,
            'user_id' => $sale->user_id,
            'old_profile_id' => $sale->account_profile_id,
            'reason' => 'Reportado por cliente ' . $customer->name . ': ' . $request->reason,
            'status' => 'pending',
        ]);

        // Marcar la venta en estado de revisión
        $sale->update(['status' => 'expiring_soon']);

        AuditLog::create([
            'user_id' => $sale->user_id,
            'action' => 'customer_report_issue',
            'description' => "El cliente {$customer->name} reportó un problema en la venta {$sale->sale_code} ({$sale->service->name}): {$request->reason}",
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', '¡Reporte registrado con éxito! Tu distribuidor ha recibido la alerta y verificará tu cuenta de inmediato.');
    }
}
