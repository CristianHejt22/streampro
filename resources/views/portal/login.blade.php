@extends('layouts.public')

@section('title', 'Ingreso a Bóveda Privada de Streaming')

@section('content')
<div class="min-h-[80vh] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 bg-[#0f172a]/80 backdrop-blur-xl p-8 sm:p-10 rounded-3xl border border-slate-800 shadow-2xl relative overflow-hidden">
        <!-- Glow top -->
        <div class="absolute -top-24 -left-24 w-48 h-48 bg-red-600/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -right-24 w-48 h-48 bg-sky-500/20 rounded-full blur-3xl pointer-events-none"></div>

        <div class="text-center relative z-10">
            <div class="w-16 h-16 mx-auto rounded-2xl bg-gradient-to-tr from-red-600 to-amber-500 flex items-center justify-center text-white shadow-lg shadow-red-600/30 mb-4">
                <i data-lucide="shield-check" class="w-8 h-8"></i>
            </div>
            <h2 class="text-2xl sm:text-3xl font-black text-white tracking-tight">Bóveda de Credenciales</h2>
            <p class="text-xs text-slate-400 mt-2">Ingresa con tu correo de cliente y contraseña para ver tus accesos, pantallas y PINs de streaming.</p>
        </div>

        @if(session('success'))
        <div class="p-3.5 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs flex items-center gap-2">
            <i data-lucide="check-circle" class="w-4 h-4 shrink-0"></i>
            <span>{{ session('success') }}</span>
        </div>
        @endif

        @if(isset($errors) && $errors->any())
        <div class="p-3.5 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-xs flex items-center gap-2">
            <i data-lucide="alert-circle" class="w-4 h-4 shrink-0"></i>
            <span>{{ $errors->first() }}</span>
        </div>
        @endif

        <form method="POST" action="{{ route('boveda.login.process') }}" class="space-y-4 relative z-10">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">Correo Electrónico o WhatsApp de Cliente</label>
                <div class="relative">
                    <input type="text" name="login" value="{{ old('login', old('email')) }}" required placeholder="tu-correo@cliente.com o 54911..." class="w-full pl-10 pr-4 py-3 rounded-xl bg-slate-900/90 border border-slate-700/80 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-red-500 transition-colors">
                    <i data-lucide="user-check" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">Contraseña o PIN de Bóveda (4 Dígitos)</label>
                <div class="relative" x-data="{ show: false }">
                    <input :type="show ? 'text' : 'password'" name="password" required placeholder="Tu contraseña o PIN de 4 dígitos" class="w-full pl-10 pr-10 py-3 rounded-xl bg-slate-900/90 border border-slate-700/80 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-red-500 transition-colors">
                    <i data-lucide="lock" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                    <button type="button" @click="show = !show" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white text-xs cursor-pointer">
                        <span x-text="show ? 'Ocultar' : 'Ver'"></span>
                    </button>
                </div>
            </div>

            <div class="flex items-center justify-between text-xs pt-1">
                <label class="flex items-center gap-2 text-slate-400 cursor-pointer">
                    <input type="checkbox" name="remember" class="rounded bg-slate-900 border-slate-700 text-red-600 focus:ring-0">
                    <span>Recordar sesión</span>
                </label>
                <a href="{{ route('portal.register') }}" class="text-sky-400 hover:text-sky-300 font-semibold">¿No tienes cuenta? Regístrate</a>
            </div>

            <button type="submit" class="w-full py-3.5 rounded-xl bg-gradient-to-r from-red-600 to-red-500 hover:from-red-500 hover:to-red-600 text-white font-black text-sm tracking-wide shadow-lg shadow-red-600/30 transition-all flex items-center justify-center gap-2 mt-4 cursor-pointer">
                <i data-lucide="key" class="w-4 h-4"></i>
                <span>Ingresar a mi Bóveda</span>
            </button>
        </form>

        <div class="pt-4 border-t border-slate-800 text-center relative z-10">
            <a href="{{ route('login') }}" class="text-xs text-slate-500 hover:text-slate-300 transition-colors flex items-center justify-center gap-1.5">
                <i data-lucide="shield" class="w-3.5 h-3.5"></i>
                <span>Acceso a Panel de Administración / Revendedores</span>
            </a>
        </div>
    </div>
</div>
@endsection
