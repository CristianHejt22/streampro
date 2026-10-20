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
use App\Models\RenewalRequest;
use App\Models\SystemSetting;

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

        // Obtener ventas activas del cliente con credenciales snapshot, perfiles y solicitudes de renovación
        $activeSales = Sale::with(['service', 'plan', 'streamingAccount', 'profile', 'latestRenewalRequest'])
            ->where('customer_id', $customer->id)
            ->whereIn('status', ['active', 'expiring_soon'])
            ->latest()
            ->get();

        // Historial de ventas anteriores o vencidas
        $historySales = Sale::with(['service', 'plan', 'latestRenewalRequest'])
            ->where('customer_id', $customer->id)
            ->whereNotIn('status', ['active', 'expiring_soon'])
            ->latest()
            ->take(5)
            ->get();

        // Datos del revendedor asignado (Marca blanca, WhatsApp, Logo, CBU/CVU)
        $reseller = $customer->user ?: User::where('role', 'admin')->first();

        // Preferencia de Soporte de Cuenta por WhatsApp:
        // "El número de whatsapp que aparecerá en bóveda de cliente es de cada proveedor si lo desea, y del revendedor en caso de ser copia."
        $whatsappSupportEnabled = SystemSetting::get('whatsapp_support_enabled', 'true') === 'true';
        $whatsappSupportDest = SystemSetting::get('whatsapp_support_dest', 'reseller');
        $whatsappAdminPhone = SystemSetting::get('whatsapp_support_admin_phone', '5491199887766');

        if ($whatsappSupportDest === 'admin') {
            $supportWhatsAppPhone = $whatsappAdminPhone;
        } else {
            if ($reseller && $reseller->parent_id && $reseller->parent) {
                $provider = $reseller->parent;
                // Si el proveedor activó la opción de atender él mismo el soporte de sus tiendas clonadas
                if ($provider->provider_override_support_whatsapp && !empty($provider->support_whatsapp)) {
                    $supportWhatsAppPhone = $provider->support_whatsapp;
                } else {
                    // En caso de ser copia, se muestra el WhatsApp individual de su revendedor
                    $supportWhatsAppPhone = $reseller->support_whatsapp ?: $provider->support_whatsapp ?: $whatsappAdminPhone;
                }
            } else {
                $supportWhatsAppPhone = ($reseller && $reseller->support_whatsapp) ? $reseller->support_whatsapp : $whatsappAdminPhone;
            }
        }

        // Catálogo de plataformas principales para activar nuevas cuentas
        $catalogServices = Service::where('status', 'active')
            ->with(['plans' => function ($q) {
                $q->where('is_active', true);
            }])
            ->get();

        return view('portal.boveda', compact(
            'customer',
            'activeSales',
            'historySales',
            'reseller',
            'catalogServices',
            'whatsappSupportEnabled',
            'supportWhatsAppPhone'
        ));
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

    /**
     * Verificación de Seguridad y Biometría para Desbloquear Bóveda
     */
    public function verifyUnlock(Request $request)
    {
        $customer = Auth::guard('customer')->user();
        if (!$customer) {
            return response()->json(['success' => false, 'message' => 'Sesión expirada.'], 401);
        }

        $type = $request->input('type'); // 'biometric' o 'password'

        if ($type === 'biometric') {
            return response()->json([
                'success' => true,
                'message' => 'Identidad biométrica validada con éxito en el dispositivo.',
                'unlocked' => true,
            ]);
        }

        $request->validate([
            'password' => 'required|string',
        ]);

        $inputPass = $request->input('password');

        if (Hash::check($inputPass, $customer->password) || ($customer->security_pin && $inputPass === $customer->security_pin)) {
            return response()->json([
                'success' => true,
                'message' => 'Contraseña / PIN de Bóveda verificado exitosamente.',
                'unlocked' => true,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'La contraseña o PIN ingresado es incorrecto. Vuelve a intentarlo.',
        ], 422);
    }

    /**
     * Procesa la solicitud de renovación enviada por el cliente desde su Bóveda
     */
    public function submitRenewalRequest(Request $request, Sale $sale)
    {
        $customer = Auth::guard('customer')->user();
        if (!$customer || $sale->customer_id !== $customer->id) {
            abort(403, 'No tienes permiso para renovar esta suscripción.');
        }

        $request->validate([
            'months_duration' => 'required|integer|in:1,2,3,6,12',
            'payment_method' => 'required|string|in:bank_transfer,mercadopago,binance,cash',
            'receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'customer_notes' => 'nullable|string|max:500',
        ]);

        $months = (int) $request->input('months_duration', 1);
        $plan = $sale->plan;
        $basePrice = $plan ? (float)$plan->price : (float)$sale->sale_price;
        $totalAmount = $basePrice * $months;

        $receiptPath = null;
        if ($request->hasFile('receipt')) {
            $receiptPath = $request->file('receipt')->store('receipts', 'public');
        }

        // Crear la Solicitud de Renovación formal
        $renewalReq = RenewalRequest::create([
            'sale_id' => $sale->id,
            'customer_id' => $customer->id,
            'user_id' => $sale->user_id,
            'service_id' => $sale->service_id,
            'service_plan_id' => $sale->service_plan_id,
            'months_duration' => $months,
            'amount' => $totalAmount,
            'payment_method' => $request->payment_method,
            'receipt_path' => $receiptPath,
            'customer_notes' => $request->customer_notes,
            'status' => 'pending',
        ]);

        AuditLog::create([
            'user_id' => $sale->user_id,
            'action' => 'customer_request_renewal',
            'description' => "El cliente {$customer->name} solicitó renovación para {$sale->sale_code} ({$sale->service->name}) por {$months} mes(es). Monto: \${$totalAmount}. Método: {$request->payment_method}.",
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', "¡Solicitud de renovación enviada con éxito! Tu distribuidor ha recibido la alerta por {$months} mes(es) (\${$totalAmount}) y confirmará la extensión en tu Bóveda.");
    }
}
