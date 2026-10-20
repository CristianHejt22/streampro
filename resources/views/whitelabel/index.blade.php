@extends('layouts.app')

@section('title', 'Marca Blanca')
@section('page_title', 'Personalización de Marca Blanca (White-Label)')
@section('page_subtitle', 'Personaliza el logo, colores, fondo de login y datos de contacto de tu plataforma')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-gray-900/80 border border-gray-800 rounded-3xl p-6 md:p-8 shadow-xl">
        <form method="POST" action="{{ route('whitelabel.update') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <!-- Notice about Tier & White-Label status -->
            @if(Auth::user()->isReseller())
                @if(Auth::user()->isPanelBasic())
                <div class="p-5 rounded-2xl bg-gradient-to-r from-amber-950/40 via-slate-900 to-slate-900 border border-amber-500/40 text-xs space-y-3 shadow-lg">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-amber-500 text-slate-950 uppercase tracking-wider">
                                📦 MARCA OFICIAL SPRO PLAY (PANEL BÁSICO)
                            </span>
                            <span class="text-amber-400 font-bold">10 Servicios Máximo</span>
                        </div>
                        <span class="text-[10px] font-mono text-gray-400">Marca Blanca Bloqueada</span>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 rounded-xl bg-[#0b0f19] border border-gray-800">
                        <div class="flex items-center gap-4">
                            <img src="{{ asset('images/spro-play-logo.svg') }}" alt="SPRO PLAY" class="h-10 w-auto object-contain">
                            <div>
                                <h4 class="font-bold text-white text-sm">SPRO PLAY Oficial</h4>
                                <p class="text-[11px] text-gray-400">Tu tienda y catálogo operan bajo la marca oficial SPRO PLAY. Solo puedes editar tus números de soporte.</p>
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
                        ℹ️ <strong>Función de Marca Blanca:</strong> Para utilizar tu propio nombre de marca, subir tu logo personalizado y elegir tus colores corporativos, solicita al Super Admin la actualización a <strong>Panel PRO (Ilimitado)</strong>.
                    </p>
                </div>
                @else
                <div class="p-4 rounded-2xl bg-purple-500/10 border border-purple-500/30 text-purple-300 text-xs flex items-center justify-between gap-3 shadow-lg">
                    <div class="flex items-center gap-3">
                        <i data-lucide="sparkles" class="w-5 h-5 shrink-0 text-purple-400"></i>
                        <span>Función de <strong>MARCA BLANCA HABILITADA (PANEL PRO)</strong>: Los cambios realizados aquí se reflejarán automáticamente en tu tienda para tus clientes.</span>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-purple-500 text-white uppercase tracking-wider shrink-0">
                        💎 PRO TOTAL
                    </span>
                </div>
                @endif
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Brand Name -->
                <div>
                    <label class="block text-xs font-semibold text-gray-300 uppercase tracking-wider mb-2">
                        Nombre de tu Marca / Empresa *
                    </label>
                    @if(Auth::user()->isReseller() && Auth::user()->isPanelBasic())
                    <input type="text" name="brand_name" value="SPRO PLAY" readonly
                        class="w-full px-4 py-3 bg-gray-950 border border-amber-500/40 rounded-xl text-amber-300 font-bold text-sm cursor-not-allowed">
                    <span class="text-[10px] text-amber-400 mt-1 block">Fijado como SPRO PLAY en Panel Básico. Habilita Marca Blanca en PRO.</span>
                    @else
                    <input type="text" name="brand_name" value="{{ old('brand_name', $user->attributes['brand_name'] ?? 'StreamPro Master') }}" required
                        class="w-full px-4 py-3 bg-gray-950 border border-gray-800 rounded-xl text-white text-sm focus:ring-2 focus:ring-indigo-500">
                    @endif
                </div>

                <!-- WhatsApp Support -->
                <div>
                    <label class="block text-xs font-semibold text-gray-300 uppercase tracking-wider mb-2">
                        WhatsApp de Atención al Cliente (con código de país)
                    </label>
                    <input type="text" name="support_whatsapp" value="{{ old('support_whatsapp', $user->support_whatsapp) }}" placeholder="Ej: 5491122334455"
                        class="w-full px-4 py-3 bg-gray-950 border border-gray-800 rounded-xl text-white text-sm focus:ring-2 focus:ring-indigo-500">
                </div>

                <!-- Telegram Support -->
                <div>
                    <label class="block text-xs font-semibold text-gray-300 uppercase tracking-wider mb-2">
                        Canal o Usuario de Telegram
                    </label>
                    <input type="text" name="support_telegram" value="{{ old('support_telegram', $user->support_telegram) }}" placeholder="Ej: streampro_soporte"
                        class="w-full px-4 py-3 bg-gray-950 border border-gray-800 rounded-xl text-white text-sm focus:ring-2 focus:ring-indigo-500">
                </div>

                <!-- Theme Colors -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-300 uppercase tracking-wider mb-2">
                            Color Primario
                        </label>
                        <div class="flex items-center gap-2">
                            <input type="color" name="brand_primary_color" value="{{ $user->brand_primary_color ?: '#6366f1' }}"
                                class="w-10 h-10 rounded-lg cursor-pointer bg-transparent border-0">
                            <span class="text-xs font-mono text-gray-400">{{ $user->brand_primary_color ?: '#6366f1' }}</span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-300 uppercase tracking-wider mb-2">
                            Color Acento
                        </label>
                        <div class="flex items-center gap-2">
                            <input type="color" name="brand_accent_color" value="{{ $user->brand_accent_color ?: '#06b6d4' }}"
                                class="w-10 h-10 rounded-lg cursor-pointer bg-transparent border-0">
                            <span class="text-xs font-mono text-gray-400">{{ $user->brand_accent_color ?: '#06b6d4' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Uploads: Logo & Login Wallpaper -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-gray-800">
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

                <div>
                    <label class="block text-xs font-semibold text-gray-300 uppercase tracking-wider mb-2">
                        Fondo de Pantalla de Login (Wallpaper)
                    </label>
                    <input type="file" name="custom_login_bg" accept="image/*"
                        class="w-full px-3 py-2 bg-gray-950 border border-gray-800 rounded-xl text-xs text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-cyan-600 file:text-white cursor-pointer">
                    <p class="text-[11px] text-gray-500 mt-1">Recomendado: 1920x1080 px en formato JPG o WebP.</p>
                </div>
            </div>

            <!-- Actions -->
            <div class="pt-4 border-t border-gray-800 flex justify-end">
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-cyan-500 hover:opacity-95 text-white font-bold text-xs shadow-lg shadow-indigo-600/30 transition-all flex items-center gap-2">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span>Guardar Identidad de Marca</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
