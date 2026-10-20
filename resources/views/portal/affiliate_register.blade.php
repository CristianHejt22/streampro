@extends('layouts.public')

@section('title', 'Afíliate como Revendedor | ' . ($provider->effective_brand_name ?? $provider->name))

@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 py-8 sm:py-12" x-data="{
    selectedTier: 'basic',
    panelName: ''
}">
    <!-- Top Provider Banner -->
    <div class="text-center space-y-3 mb-8">
        <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-purple-500/10 border border-purple-500/30 text-purple-300 text-xs font-bold font-mono">
            <span>🤝 INVITACIÓN OFICIAL DE AFILIACIÓN</span>
        </div>
        <h1 class="text-3xl sm:text-4xl font-black text-white tracking-tight">
            Crea Tu Propio Panel de Streaming
        </h1>
        <p class="text-xs sm:text-sm text-slate-300 max-w-lg mx-auto">
            Invitación de tu proveedor oficial <strong class="text-white">{{ $provider->name }}</strong> ({{ $provider->effective_brand_name }}). 
            Vende Netflix 4K, Disney+, Max y más con catálogo sincronizado, Bóveda privada y ganancias directas.
        </p>
    </div>

    <!-- Registration Card -->
    <div class="rounded-3xl bg-[#111827] border border-gray-800 p-6 sm:p-9 shadow-2xl space-y-6 relative overflow-hidden">
        <!-- Glow background -->
        <div class="absolute -top-16 -right-16 w-36 h-36 bg-purple-500/15 rounded-full blur-2xl pointer-events-none"></div>

        @if($errors->any())
        <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs space-y-1">
            <span class="font-bold block">Por favor corrige los siguientes errores:</span>
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ route('resellers.affiliate_register.process', $provider->referral_code) }}" class="space-y-5">
            @csrf

            <!-- Selector de Nivel de Panel -->
            <div class="space-y-2.5">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-400">1. Selecciona Tu Nivel de Panel *</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <!-- Opción BÁSICO -->
                    <label class="p-4 rounded-2xl border cursor-pointer transition-all flex flex-col justify-between"
                           :class="selectedTier === 'basic' ? 'bg-amber-500/10 border-amber-500 ring-2 ring-amber-500/40 text-white shadow-lg' : 'bg-[#0b0f19] border-gray-800 text-slate-400 hover:border-gray-700'">
                        <div class="flex items-center justify-between">
                            <input type="radio" name="panel_tier" value="basic" x-model="selectedTier" class="text-amber-500 focus:ring-0">
                            <span class="px-2 py-0.5 rounded-full text-[9px] font-black bg-amber-500/20 text-amber-300">HASTA 10 SERV</span>
                        </div>
                        <div class="mt-3 space-y-1">
                            <div class="font-black text-white text-base">📦 Panel BÁSICO</div>
                            <span class="text-[11px] text-amber-300 block font-semibold">Marca Oficial: SPRO PLAY</span>
                            <p class="text-[10px] text-slate-400 mt-1">Catálogo limitado a 10 servicios. Utiliza la identidad oficial predeterminada SPRO PLAY.</p>
                        </div>
                        <div class="mt-3 pt-2 border-t border-gray-800/80 flex items-center gap-2">
                            <img src="{{ asset('images/spro-play-logo.svg') }}" alt="SPRO PLAY" class="h-5 object-contain">
                        </div>
                    </label>

                    <!-- Opción PRO -->
                    <label class="p-4 rounded-2xl border cursor-pointer transition-all flex flex-col justify-between"
                           :class="selectedTier === 'pro' ? 'bg-purple-500/10 border-purple-500 ring-2 ring-purple-500/40 text-white shadow-lg' : 'bg-[#0b0f19] border-gray-800 text-slate-400 hover:border-gray-700'">
                        <div class="flex items-center justify-between">
                            <input type="radio" name="panel_tier" value="pro" x-model="selectedTier" class="text-purple-500 focus:ring-0">
                            <span class="px-2 py-0.5 rounded-full text-[9px] font-black bg-gradient-to-r from-purple-500 to-indigo-500 text-white">MARCA BLANCA</span>
                        </div>
                        <div class="mt-3 space-y-1">
                            <div class="font-black text-white text-base">💎 Panel PRO</div>
                            <span class="text-[11px] text-purple-300 block font-semibold">Servicios ILIMITADOS</span>
                            <p class="text-[10px] text-slate-400 mt-1">Marca blanca 100% habilitada. Agrega tus propios servicios y personaliza tu marca.</p>
                        </div>
                        <div class="mt-3 pt-2 border-t border-gray-800/80 text-[10px] text-purple-300 font-mono">
                            ✓ Tu propio nombre y logo
                        </div>
                    </label>
                </div>
            </div>

            <!-- Datos Fundamentales -->
            <div class="space-y-4 pt-2 border-t border-gray-800">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">2. Datos Fundamentales del Revendedor</span>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Nombre Completo *</label>
                    <input type="text" name="name" value="{{ old('name') }}" placeholder="Ej: Santiago Pérez" required class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white focus:border-purple-500">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Correo Electrónico (Acceso al Panel) *</label>
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="tunombre@gmail.com" required class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white focus:border-purple-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Contraseña de Acceso *</label>
                        <input type="password" name="password" placeholder="Mínimo 6 caracteres" required class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white focus:border-purple-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-emerald-400 mb-1">Número de WhatsApp (con código de país) *</label>
                    <input type="text" name="support_whatsapp" value="{{ old('support_whatsapp') }}" placeholder="Ej: 5493512345678" required class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white focus:border-emerald-500 font-mono">
                    <span class="text-[10px] text-gray-400 mt-1 block">Aparecerá en el soporte de tu catálogo y en la Bóveda de tus clientes.</span>
                </div>

                <!-- Nombre de Marca Blanca (Solo para PRO) -->
                <div x-show="selectedTier === 'pro'" class="p-3.5 rounded-2xl bg-purple-500/10 border border-purple-500/30 space-y-1">
                    <label class="block text-xs font-bold uppercase tracking-wider text-purple-300">Nombre de Tu Marca Propia (Marca Blanca Habilitada)</label>
                    <input type="text" name="brand_name" x-model="panelName" placeholder="Ej: Nova Stream Digital" class="w-full px-3.5 py-2 rounded-xl bg-[#0b0f19] border border-purple-500/40 text-xs text-white focus:border-purple-400">
                    <span class="text-[10px] text-purple-300/80 block">Tu tienda y Bóveda tendrán tu nombre personalizado.</span>
                </div>

                <!-- Info para Básico -->
                <div x-show="selectedTier === 'basic'" class="p-3.5 rounded-2xl bg-amber-500/10 border border-amber-500/30 flex items-center gap-3">
                    <img src="{{ asset('images/spro-play-icon.svg') }}" class="w-8 h-8 shrink-0">
                    <div class="text-[11px] text-amber-200">
                        <strong class="text-white block font-sans">Marca Asignada: SPRO PLAY</strong>
                        <span>Tu panel operará con la identidad corporativa oficial SPRO PLAY.</span>
                    </div>
                </div>
            </div>

            <div class="pt-2">
                <button type="submit" class="w-full py-3.5 rounded-2xl bg-gradient-to-r from-purple-600 via-indigo-600 to-sky-600 hover:opacity-90 text-white font-black text-xs uppercase tracking-wider shadow-xl shadow-purple-600/30 flex items-center justify-center gap-2 transition-all cursor-pointer">
                    <i data-lucide="user-plus" class="w-4 h-4"></i>
                    <span>Crear Mi Panel & Comenzar a Vender</span>
                </button>
            </div>
        </form>

        <div class="pt-3 border-t border-gray-800 text-center text-xs text-gray-400">
            ¿Ya tienes cuenta de revendedor?
            <a href="{{ route('login') }}" class="text-sky-400 hover:underline font-bold">Inicia Sesión aquí</a>
        </div>
    </div>
</div>
@endsection
