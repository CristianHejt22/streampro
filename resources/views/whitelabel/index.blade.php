@extends('layouts.app')

@section('title', 'Preferencias de Panel')
@section('page_title', 'Preferencias de Panel & Marca')
@section('page_subtitle', 'Configura la atención por WhatsApp en la Bóveda de Clientes, renovaciones e identidad de marca')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Formulario Principal de Preferencias -->
    <form method="POST" action="{{ route('whitelabel.update') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <!-- ESTADO Y NIVEL DE PANEL (BÁSICO SPRO PLAY vs PRO MARCA BLANCA) -->
        @if(Auth::user()->isReseller())
            @if(Auth::user()->isPanelBasic())
            <div class="p-5 rounded-3xl bg-gradient-to-r from-amber-950/40 via-slate-900 to-slate-900 border border-amber-500/40 text-xs space-y-3 shadow-xl">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center gap-2.5">
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-amber-500 text-slate-950 uppercase tracking-wider">
                            📦 MARCA OFICIAL SPRO PLAY (PANEL BÁSICO)
                        </span>
                        <span class="text-amber-400 font-bold">10 Servicios Máximo</span>
                    </div>
                    <span class="text-[10px] font-mono text-gray-400">Marca Blanca Bloqueada</span>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 rounded-2xl bg-[#0b0f19] border border-gray-800">
                    <div class="flex items-center gap-4">
                        <img src="{{ asset('images/spro-play-logo.svg') }}" alt="SPRO PLAY" class="h-10 w-auto object-contain">
                        <div>
                            <h4 class="font-bold text-white text-sm">SPRO PLAY Oficial</h4>
                            <p class="text-[11px] text-gray-400">Tu tienda y catálogo operan bajo la marca oficial SPRO PLAY. Puedes configurar libremente tus números y preferencias de soporte por WhatsApp para la Bóveda de tus clientes.</p>
                        </div>
                    </div>

                    <div class="shrink-0">
                        <span class="px-3 py-1.5 rounded-xl bg-purple-500/10 border border-purple-500/30 text-purple-300 font-bold text-[11px] inline-flex items-center gap-1">
                            <i data-lucide="gem" class="w-3.5 h-3.5 text-purple-400"></i>
                            <span>Marca Blanca en PRO</span>
                        </span>
                    </div>
                </div>

                <p class="text-[11px] text-slate-400 leading-relaxed">
                    ℹ️ <strong>Función de Marca Blanca:</strong> Para utilizar tu propio nombre de marca, subir tu logo personalizado y elegir tus colores corporativos, solicita la actualización a <strong>Panel PRO (Ilimitado)</strong>.
                </p>
            </div>
            @else
            <div class="p-5 rounded-3xl bg-gradient-to-r from-purple-950/30 via-slate-900 to-slate-900 border border-purple-500/30 text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-xl">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-purple-500/20 text-purple-400 border border-purple-500/30 flex items-center justify-center shrink-0">
                        <i data-lucide="gem" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-white text-sm">💎 PANEL PRO ILIMITADO (Marca Blanca Habilitada)</h4>
                        <p class="text-[11px] text-purple-300">Tienes acceso a personalización total (logo propio, colores y nombre) y la posibilidad de revender acceso a paneles a tus sub-revendedores.</p>
                    </div>
                </div>
                <span class="px-3 py-1 rounded-full text-[10px] font-black bg-purple-500 text-white uppercase tracking-wider shrink-0 w-max">
                    PRO TOTAL
                </span>
            </div>
            @endif
        @else
            <div class="p-4 rounded-3xl bg-red-950/30 border border-red-500/30 text-xs flex items-center justify-between gap-3 shadow-xl">
                <div class="flex items-center gap-3">
                    <i data-lucide="shield-check" class="w-5 h-5 text-red-400 shrink-0"></i>
                    <span class="text-white font-medium">Panel Administrador Master · Control Global de Marca y Preferencias</span>
                </div>
                <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-red-600 text-white uppercase tracking-wider">
                    MASTER
                </span>
            </div>
        @endif

        <!-- ======================================================== -->
        <!-- SECCIÓN 1: SOPORTE & RENOVACIONES POR WHATSAPP EN BÓVEDA -->
        <!-- ======================================================== -->
        <div class="bg-gray-900/80 border border-gray-800 rounded-3xl p-6 md:p-8 shadow-xl space-y-6">
            <div class="flex items-center gap-3 pb-4 border-b border-gray-800">
                <div class="w-10 h-10 rounded-2xl bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center shrink-0">
                    <i data-lucide="message-circle" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-base font-black text-white">Soporte & Renovaciones por WhatsApp en Bóveda Clientes</h3>
                    <p class="text-xs text-gray-400">Configura tu número de contacto y el destino donde llegarán las consultas y renovaciones de tus clientes.</p>
                </div>
            </div>

            <!-- Canales de Contacto Directo -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- WhatsApp Support Input -->
                <div>
                    <label class="block text-xs font-semibold text-gray-300 uppercase tracking-wider mb-2">
                        WhatsApp de Atención al Cliente (con código de país) *
                    </label>
                    <div class="relative">
                        <input type="text" name="support_whatsapp" value="{{ old('support_whatsapp', $user->support_whatsapp) }}" placeholder="Ej: 5491122334455"
                            class="w-full pl-10 pr-4 py-3 bg-gray-950 border border-gray-800 rounded-xl text-white text-sm focus:ring-2 focus:ring-emerald-500">
                        <i data-lucide="phone" class="w-4 h-4 text-emerald-400 absolute left-3.5 top-3.5 pointer-events-none"></i>
                    </div>
                    <span class="text-[11px] text-gray-500 mt-1 block">Ingresa tu número completo sin espacios ni signos +.</span>
                </div>

                <!-- Telegram Support Input -->
                <div>
                    <label class="block text-xs font-semibold text-gray-300 uppercase tracking-wider mb-2">
                        Canal o Usuario de Telegram (Opcional)
                    </label>
                    <div class="relative">
                        <input type="text" name="support_telegram" value="{{ old('support_telegram', $user->support_telegram) }}" placeholder="Ej: mitienda_soporte"
                            class="w-full pl-10 pr-4 py-3 bg-gray-950 border border-gray-800 rounded-xl text-white text-sm focus:ring-2 focus:ring-emerald-500">
                        <i data-lucide="send" class="w-4 h-4 text-sky-400 absolute left-3.5 top-3.5 pointer-events-none"></i>
                    </div>
                    <span class="text-[11px] text-gray-500 mt-1 block">Opcional para canal o chat de soporte en Telegram.</span>
                </div>
            </div>

            <!-- SELECTOR: DESTINO DE LAS CONSULTAS Y RENOVACIONES DE BÓVEDA -->
            <div class="pt-4 border-t border-gray-800 space-y-3">
                <div class="space-y-1">
                    <label class="block text-xs font-bold text-white uppercase tracking-wider">
                        ¿A qué número de WhatsApp van las consultas y renovaciones? *
                    </label>
                    <p class="text-xs text-gray-400 leading-relaxed">
                        Cada panelista (proveedor o revendedor) configura su número de WhatsApp. En caso de ser una tienda clonada, las solicitudes de soporte y renovaciones de los clientes de la Bóveda llegan directamente al revendedor, o si éste lo prefiere, al WhatsApp de su proveedor patrocinador para que le brinde soporte directo.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2" x-data="{ currentPref: '{{ old('whatsapp_support_preference', $user->whatsapp_support_preference ?? 'reseller') }}' }">
                    <!-- Opción 1: Mi WhatsApp Propio -->
                    <label class="p-4 rounded-2xl border transition-all cursor-pointer flex flex-col justify-between"
                           :class="currentPref === 'reseller' ? 'bg-emerald-950/20 border-emerald-500 ring-2 ring-emerald-500/30' : 'bg-[#0b0f19] border-gray-800 hover:border-gray-700'">
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2.5">
                                    <input type="radio" name="whatsapp_support_preference" value="reseller" x-model="currentPref" class="text-emerald-500 focus:ring-0">
                                    <span class="text-sm font-bold text-white flex items-center gap-1.5">
                                        <i data-lucide="phone-call" class="w-4 h-4 text-emerald-400"></i>
                                        <span>Mi WhatsApp Propio</span>
                                    </span>
                                </div>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                                    Atención Directa
                                </span>
                            </div>
                            <p class="text-xs text-gray-400 leading-relaxed">
                                Las consultas y solicitudes de renovación de tus clientes en la Bóveda llegarán directamente a tu número de WhatsApp:
                                <strong class="text-white font-mono block mt-0.5">{{ $user->support_whatsapp ?: '(Configura tu número arriba)' }}</strong>
                            </p>
                        </div>
                        <div class="mt-3 pt-2 border-t border-gray-800 text-[11px] text-emerald-400 font-semibold flex items-center gap-1">
                            <i data-lucide="check" class="w-3.5 h-3.5"></i>
                            <span>Tú gestionas y cobras a tus clientes</span>
                        </div>
                    </label>

                    <!-- Opción 2: WhatsApp de Mi Proveedor Patrocinador -->
                    <label class="p-4 rounded-2xl border transition-all cursor-pointer flex flex-col justify-between"
                           :class="currentPref === 'provider' ? 'bg-teal-950/30 border-teal-500 ring-2 ring-teal-500/30' : 'bg-[#0b0f19] border-gray-800 hover:border-gray-700'">
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2.5">
                                    <input type="radio" name="whatsapp_support_preference" value="provider" x-model="currentPref" class="text-teal-500 focus:ring-0">
                                    <span class="text-sm font-bold text-white flex items-center gap-1.5">
                                        <i data-lucide="shield-check" class="w-4 h-4 text-teal-400"></i>
                                        <span>WhatsApp de Mi Proveedor</span>
                                    </span>
                                </div>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-teal-500/20 text-teal-300 border border-teal-500/30">
                                    Soporte Patrocinador
                                </span>
                            </div>
                            <p class="text-xs text-gray-400 leading-relaxed">
                                @if($user->parent)
                                Solicitas que las consultas y renovaciones de los clientes de tu Bóveda se deriven al WhatsApp de tu proveedor patrocinador:
                                <strong class="text-teal-300 font-mono block mt-0.5">{{ $user->parent->name }} · {{ $user->parent->support_whatsapp ?: 'Sin número cargado' }}</strong>
                                @else
                                No tienes proveedor patrocinador asignado (panel independiente). Si seleccionas esta opción, el soporte se derivará a la administración central de la plataforma.
                                @endif
                            </p>
                        </div>
                        <div class="mt-3 pt-2 border-t border-gray-800 text-[11px] text-amber-300 font-semibold flex items-center gap-1">
                            <i data-lucide="info" class="w-3.5 h-3.5"></i>
                            <span>Queda registrado con mensaje en auditoría</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Indicador en Vivo del Destino Resuelto en Bóveda -->
            <div class="p-4 rounded-2xl bg-[#0b0f19] border border-gray-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                <div class="flex items-center gap-3">
                    <span class="inline-block w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    <div>
                        <span class="text-gray-400 block text-[11px]">Destino Efectivo Actual para tus Clientes en Bóveda:</span>
                        <strong class="text-white font-mono text-sm">{{ $user->resolved_vault_whatsapp }}</strong>
                    </div>
                </div>

                <div>
                    @if($user->whatsapp_support_preference === 'provider')
                    <span class="px-3 py-1 rounded-xl bg-teal-500/10 border border-teal-500/30 text-teal-300 font-bold text-[11px] inline-flex items-center gap-1.5">
                        <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                        <span>Enrutado a Proveedor Patrocinador</span>
                    </span>
                    @elseif($user->whatsapp_support_preference === 'reseller')
                    <span class="px-3 py-1 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 font-bold text-[11px] inline-flex items-center gap-1.5">
                        <i data-lucide="user-check" class="w-3.5 h-3.5"></i>
                        <span>Enrutado a tu WhatsApp Propio</span>
                    </span>
                    @else
                    <span class="px-3 py-1 rounded-xl bg-gray-800 border border-gray-700 text-gray-300 font-bold text-[11px] inline-flex items-center gap-1.5">
                        <i data-lucide="cpu" class="w-3.5 h-3.5"></i>
                        <span>Enrutamiento Automático</span>
                    </span>
                    @endif
                </div>
            </div>
        </div>

        <!-- ======================================================== -->
        <!-- SECCIÓN 2: IDENTIDAD VISUAL DE MARCA BLANCA -->
        <!-- ======================================================== -->
        <div class="bg-gray-900/80 border border-gray-800 rounded-3xl p-6 md:p-8 shadow-xl space-y-6">
            <div class="flex items-center justify-between pb-4 border-b border-gray-800">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-500/20 text-indigo-400 border border-indigo-500/30 flex items-center justify-center shrink-0">
                        <i data-lucide="palette" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-black text-white">Identidad de Marca & Personalización Visual</h3>
                        <p class="text-xs text-gray-400">Personaliza el nombre, logo, colores y fondo de login de tu tienda digital.</p>
                    </div>
                </div>

                @if(Auth::user()->isReseller() && Auth::user()->isPanelBasic())
                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                    Bloqueado en Básico
                </span>
                @else
                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-purple-500/20 text-purple-300 border border-purple-500/30">
                    Marca Blanca Activa
                </span>
                @endif
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Brand Name -->
                <div>
                    <label class="block text-xs font-semibold text-gray-300 uppercase tracking-wider mb-2">
                        Nombre de tu Marca / Empresa *
                    </label>
                    @if(Auth::user()->isReseller() && Auth::user()->isPanelBasic())
                    <input type="text" name="brand_name" value="SPRO PLAY" readonly
                        class="w-full px-4 py-3 bg-gray-950 border border-amber-500/40 rounded-xl text-amber-300 font-bold text-sm cursor-not-allowed">
                    <span class="text-[10px] text-amber-400 mt-1 block">Fijado como SPRO PLAY en Panel Básico. Habilitado en PRO.</span>
                    @else
                    <input type="text" name="brand_name" value="{{ old('brand_name', $user->attributes['brand_name'] ?? 'StreamPro Master') }}" required
                        class="w-full px-4 py-3 bg-gray-950 border border-gray-800 rounded-xl text-white text-sm focus:ring-2 focus:ring-indigo-500">
                    @endif
                </div>

                <!-- Theme Colors -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-300 uppercase tracking-wider mb-2">
                            Color Primario
                        </label>
                        @if(Auth::user()->isReseller() && Auth::user()->isPanelBasic())
                        <div class="flex items-center gap-2 p-2 rounded-xl bg-gray-950 border border-gray-800">
                            <span class="w-6 h-6 rounded-lg bg-red-600 block shrink-0"></span>
                            <span class="text-xs font-mono text-gray-400">#E50914 (Oficial)</span>
                        </div>
                        @else
                        <div class="flex items-center gap-2">
                            <input type="color" name="brand_primary_color" value="{{ $user->brand_primary_color ?: '#6366f1' }}"
                                class="w-10 h-10 rounded-lg cursor-pointer bg-transparent border-0">
                            <span class="text-xs font-mono text-gray-400">{{ $user->brand_primary_color ?: '#6366f1' }}</span>
                        </div>
                        @endif
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-300 uppercase tracking-wider mb-2">
                            Color Acento
                        </label>
                        @if(Auth::user()->isReseller() && Auth::user()->isPanelBasic())
                        <div class="flex items-center gap-2 p-2 rounded-xl bg-gray-950 border border-gray-800">
                            <span class="w-6 h-6 rounded-lg bg-amber-500 block shrink-0"></span>
                            <span class="text-xs font-mono text-gray-400">#F59E0B (Oficial)</span>
                        </div>
                        @else
                        <div class="flex items-center gap-2">
                            <input type="color" name="brand_accent_color" value="{{ $user->brand_accent_color ?: '#06b6d4' }}"
                                class="w-10 h-10 rounded-lg cursor-pointer bg-transparent border-0">
                            <span class="text-xs font-mono text-gray-400">{{ $user->brand_accent_color ?: '#06b6d4' }}</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Uploads: Logo & Login Wallpaper -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-gray-800">
                <!-- Logo -->
                <div>
                    <label class="block text-xs font-semibold text-gray-300 uppercase tracking-wider mb-2">
                        Logo Personalizado (PNG transparente)
                    </label>
                    @if(Auth::user()->isReseller() && Auth::user()->isPanelBasic())
                    <div class="p-3 rounded-xl bg-gray-950 border border-gray-800 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <img src="{{ asset('images/spro-play-logo.svg') }}" class="h-8 max-w-[120px] object-contain">
                            <span class="text-xs text-amber-400 font-bold">Logo Oficial SPRO PLAY</span>
                        </div>
                        <span class="text-[10px] text-gray-500 font-mono">Predeterminado en Básico</span>
                    </div>
                    @else
                    <input type="file" name="brand_logo" accept="image/*"
                        class="w-full px-3 py-2 bg-gray-950 border border-gray-800 rounded-xl text-xs text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-600 file:text-white cursor-pointer">
                    @if($user->brand_logo)
                    <div class="mt-2 flex items-center gap-2">
                        <span class="text-[11px] text-gray-400">Logo actual:</span>
                        <img src="{{ asset('storage/' . $user->brand_logo) }}" class="h-8 max-w-[120px] object-contain rounded bg-gray-950 p-1 border border-gray-800">
                    </div>
                    @endif
                    @endif
                </div>

                <!-- Wallpaper Login -->
                <div>
                    <label class="block text-xs font-semibold text-gray-300 uppercase tracking-wider mb-2">
                        Fondo de Pantalla de Login (Wallpaper)
                    </label>
                    @if(Auth::user()->isReseller() && Auth::user()->isPanelBasic())
                    <div class="p-3 rounded-xl bg-gray-950 border border-gray-800 text-xs text-gray-400">
                        <span>Fondo cinematográfico predeterminado oficial de SPRO PLAY.</span>
                    </div>
                    @else
                    <input type="file" name="custom_login_bg" accept="image/*"
                        class="w-full px-3 py-2 bg-gray-950 border border-gray-800 rounded-xl text-xs text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-cyan-600 file:text-white cursor-pointer">
                    <p class="text-[11px] text-gray-500 mt-1">Recomendado: 1920x1080 px en formato JPG o WebP.</p>
                    @endif
                </div>
            </div>

            <!-- Botón de Guardar Todo -->
            <div class="pt-4 border-t border-gray-800 flex justify-end">
                <button type="submit" class="px-6 py-3 rounded-xl bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600 hover:opacity-95 text-white font-bold text-xs shadow-lg shadow-emerald-600/30 transition-all flex items-center gap-2 cursor-pointer">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span>Guardar Preferencias de Panel</span>
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
