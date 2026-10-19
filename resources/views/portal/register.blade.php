@extends('layouts.public')

@section('title', 'Registro de Cliente - Activación de Bóveda Streaming')

@section('content')
@php
    $brand = $reseller->brand_name ?: config('app.name', 'CStreame Master');
    $primaryColor = $reseller->brand_primary_color ?: '#E50914';
    $whatsapp = $reseller->support_whatsapp ?: '5491199887766';
@endphp

<div class="max-w-4xl mx-auto py-10 px-4 sm:px-6">
    <!-- Header de Registro con Marca del Revendedor -->
    <div class="text-center mb-10 space-y-3">
        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-slate-900 border border-slate-800 text-xs text-slate-300 font-mono mb-2">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            <span>Distribuidor Oficial: <strong>{{ $brand }}</strong></span>
            @if($refCode)
            <span class="text-sky-400">({{ $refCode }})</span>
            @endif
        </div>
        <h1 class="text-3xl sm:text-4xl font-black text-white tracking-tight">Registro & Activación de Cuentas</h1>
        <p class="text-sm text-slate-400 max-w-xl mx-auto">
            Crea tu acceso personal con tu correo y contraseña para consultar tus credenciales oficiales de streaming, perfiles asignados y PINs de seguridad en tu Bóveda Privada.
        </p>
    </div>

    @if($errors->any())
    <div class="mb-6 p-4 rounded-2xl bg-red-500/10 border border-red-500/20 text-red-400 text-xs space-y-1">
        <div class="font-bold flex items-center gap-2">
            <i data-lucide="alert-circle" class="w-4 h-4"></i>
            <span>Por favor corrige los siguientes inconvenientes:</span>
        </div>
        <ul class="list-disc list-inside pl-2 space-y-0.5">
            @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        <!-- Formulario de Registro -->
        <div class="lg:col-span-7 bg-[#0f172a]/90 backdrop-blur-xl p-6 sm:p-8 rounded-3xl border border-slate-800 shadow-2xl">
            <form method="POST" action="{{ route('portal.register.process') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="reseller_id" value="{{ $reseller->id }}">
                @if(request('plan'))
                <input type="hidden" name="plan_id" value="{{ request('plan') }}">
                @endif

                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">Nombre Completo *</label>
                    <input type="text" name="name" value="{{ old('name') }}" required placeholder="Ej: Carlos Gómez" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700/80 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-red-500">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">Correo Electrónico *</label>
                        <input type="email" name="email" value="{{ old('email') }}" required placeholder="tu-correo@gmail.com" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700/80 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-red-500">
                        <span class="text-[10px] text-slate-500 mt-0.5 block">Será tu usuario para ingresar a la Bóveda.</span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">WhatsApp / Celular *</label>
                        <input type="text" name="phone" value="{{ old('phone') }}" required placeholder="+54 9 11 0000-0000" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700/80 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-red-500">
                        <span class="text-[10px] text-slate-500 mt-0.5 block">Para soporte y avisos de renovación.</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">Crear Contraseña *</label>
                        <input type="password" name="password" required placeholder="Mínimo 6 caracteres" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700/80 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-red-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">Confirmar Contraseña *</label>
                        <input type="password" name="password_confirmation" required placeholder="Repite tu contraseña" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700/80 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-red-500">
                    </div>
                </div>

                <!-- Selección Opcional de Servicio Inicial -->
                <div class="pt-2">
                    <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Plataforma de Interés Inicial</label>
                    <div class="grid grid-cols-2 gap-2.5">
                        @foreach($services as $srv)
                        <label class="p-3 rounded-xl bg-slate-900/60 border border-slate-800 hover:border-slate-700 cursor-pointer flex items-center gap-2.5 transition-all">
                            <input type="radio" name="service_choice" value="{{ $srv->id }}" class="text-red-600 focus:ring-0">
                            <div>
                                <span class="text-xs font-bold text-white block">{{ $srv->name }}</span>
                                <span class="text-[10px] text-slate-400 font-mono">{{ $srv->plans->count() }} planes disponibles</span>
                            </div>
                        </label>
                        @endforeach
                    </div>
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full py-4 rounded-xl bg-gradient-to-r from-red-600 to-red-500 hover:from-red-500 hover:to-red-600 text-white font-black text-sm tracking-wide shadow-xl shadow-red-600/30 transition-all flex items-center justify-center gap-2 cursor-pointer">
                        <i data-lucide="shield-check" class="w-5 h-5"></i>
                        <span>Crear Cuenta & Activar Bóveda</span>
                    </button>
                </div>

                <div class="text-center pt-2">
                    <span class="text-xs text-slate-400">¿Ya tienes una cuenta registrada? </span>
                    <a href="{{ route('boveda.login') }}" class="text-xs font-bold text-sky-400 hover:text-sky-300">Inicia sesión en tu Bóveda aquí</a>
                </div>
            </form>
        </div>

        <!-- Columna Lateral: Beneficios de la Bóveda -->
        <div class="lg:col-span-5 space-y-4">
            <div class="bg-gradient-to-br from-[#0f172a] to-[#1e293b] p-6 rounded-3xl border border-slate-800 shadow-xl space-y-4">
                <div class="w-12 h-12 rounded-2xl bg-red-600/20 text-red-500 flex items-center justify-center border border-red-500/30">
                    <i data-lucide="lock" class="w-6 h-6"></i>
                </div>
                <h3 class="text-lg font-black text-white">¿Qué es tu Bóveda Privada?</h3>
                <p class="text-xs text-slate-300 leading-relaxed">
                    Es tu panel de usuario protegido donde podrás gestionar todas tus cuentas de streaming contratadas:
                </p>

                <div class="space-y-3 pt-2 text-xs">
                    <div class="flex items-start gap-3">
                        <div class="w-5 h-5 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0 text-[10px]">✓</div>
                        <p class="text-slate-300"><strong>Credenciales Oficiales:</strong> Acceso a correo y contraseña de cada plataforma (Netflix, HBO, Disney).</p>
                    </div>

                    <div class="flex items-start gap-3">
                        <div class="w-5 h-5 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0 text-[10px]">✓</div>
                        <p class="text-slate-300"><strong>Perfil Asignado & PIN:</strong> Número de pantalla y código de 4 dígitos exclusivo para ti.</p>
                    </div>

                    <div class="flex items-start gap-3">
                        <div class="w-5 h-5 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0 text-[10px]">✓</div>
                        <p class="text-slate-300"><strong>SLA & Garantía 30 Días:</strong> Contador en vivo de vencimiento y botón de reporte rápido de caídas.</p>
                    </div>

                    <div class="flex items-start gap-3">
                        <div class="w-5 h-5 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0 text-[10px]">✓</div>
                        <p class="text-slate-300"><strong>Renovación sin Pérdida de Datos:</strong> Renueva el mismo perfil mes a mes con 1 clic.</p>
                    </div>
                </div>
            </div>

            <!-- Soporte WhatsApp -->
            <div class="bg-slate-900/60 p-5 rounded-2xl border border-slate-800 flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-white block">¿Consultas previas al registro?</span>
                    <span class="text-[11px] text-slate-400">Atención personalizada con tu asesor</span>
                </div>
                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $whatsapp) }}?text=Hola,%20tengo%20una%20consulta%20sobre%20el%20registro%20en%20{{ urlencode($brand) }}" target="_blank" class="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs flex items-center gap-1.5 transition-all shadow">
                    <i data-lucide="message-circle" class="w-4 h-4"></i>
                    <span>WhatsApp</span>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
