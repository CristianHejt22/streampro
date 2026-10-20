<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\SystemSetting;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $brandName = SystemSetting::get('site_name', 'StreamPro Master');
        return view('auth.login', compact('brandName'));
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // 1. Probar autenticación de Panel (Admin / Super Reseller / Reseller)
        if (Auth::guard('web')->attempt($credentials, $request->filled('remember'))) {
            $request->session()->regenerate();
            $user = Auth::guard('web')->user();

            if ($user->status !== 'active') {
                Auth::guard('web')->logout();
                if ($user->status === 'pending') {
                    $tierLabel = ($user->panel_tier === 'pro') ? 'PRO (Ilimitado)' : 'BÁSICO (SPRO PLAY)';
                    return back()->withErrors(['email' => "Tu solicitud para el Panel {$tierLabel} está registrada y PENDIENTE DE ACTIVACIÓN. El Administrador o Proveedor debe activarte mediante créditos de plataforma."]);
                }
                return back()->withErrors(['email' => 'Tu cuenta se encuentra suspendida. Contacta a tu administrador.']);
            }

            return redirect()->intended(route('dashboard'))->with('success', '¡Bienvenido nuevamente, ' . $user->name . '!');
        }

        // 2. Probar autenticación de Cliente (Bóveda Privada de Streaming)
        if (Auth::guard('customer')->attempt($credentials, $request->filled('remember'))) {
            $request->session()->regenerate();
            $customer = Auth::guard('customer')->user();

            if ($customer->status === 'suspended') {
                Auth::guard('customer')->logout();
                return back()->withErrors(['email' => 'Tu acceso a la Bóveda se encuentra suspendido. Contacta a tu proveedor.']);
            }

            return redirect()->route('boveda.index')->with('success', '¡Bienvenido a tu Bóveda Privada, ' . $customer->name . '!');
        }

        return back()->withErrors([
            'email' => 'Las credenciales proporcionadas no coinciden con ningún registro de administrador, revendedor o cliente.',
        ])->withInput($request->only('email', 'remember'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Sesión cerrada correctamente.');
    }
}
