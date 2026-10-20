@extends('layouts.public')

@section('title', ($seller && $seller->isReseller()) ? ($seller->brand_name ?: $seller->name) . ' | Cuentas Streaming' : 'CStreame Master Platform | Paneles & Cuentas Streaming')

@section('content')
<div class="space-y-16 pb-16" x-data="{
    calcTier: 'basic',
    calcQty: 10,
    get unitPrice() {
        if (this.calcTier === 'basic') {
            return this.calcQty >= 10 ? 2500 : 3000;
        } else {
            return this.calcQty >= 10 ? 4000 : 5000;
        }
    },
    get totalPrice() {
        return this.unitPrice * this.calcQty;
    }
}">

    <!-- ======================================================== -->
    <!-- HERO SECTION DINÁMICO (SUPER ADMIN vs REVENDEDOR) -->
    <!-- ======================================================== -->
    @if($seller && $seller->isReseller())
    <!-- ========================================== -->
    <!-- LANDING PERSONALIZADA PARA REVENDEDOR CON EL NOMBRE DE SU PANEL -->
    <!-- ========================================== -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8 sm:pt-12">
        <div class="relative rounded-3xl bg-gradient-to-br from-slate-900 via-[#0d1424] to-slate-950 border border-slate-800 p-8 sm:p-12 overflow-hidden shadow-2xl">
            <!-- Glow background -->
            <div class="absolute -top-24 -right-24 w-80 h-80 rounded-full blur-3xl pointer-events-none opacity-20" style="background-color: {{ $primaryColor }}"></div>

            <div class="relative z-10 max-w-3xl space-y-5">
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-slate-800/80 border border-slate-700 text-xs font-bold font-mono">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span class="text-white">TIENDA OFICIAL ASOCIADA</span>
                    <span class="text-sky-400">• Ref: {{ $seller->referral_code }}</span>
                    @if($seller->isPanelBasic())
                    <span class="px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/30 text-[10px]">Panel BÁSICO</span>
                    @else
                    <span class="px-2 py-0.5 rounded-full bg-purple-500/20 text-purple-300 border border-purple-500/30 text-[10px]">Panel PRO ILIMITADO</span>
                    @endif
                </div>

                <h1 class="text-3xl sm:text-5xl font-black text-white tracking-tight leading-tight">
                    Bienvenido a <span class="bg-gradient-to-r from-red-500 via-rose-400 to-amber-400 bg-clip-text text-transparent">{{ $brandName }}</span>
                </h1>

                <p class="text-sm sm:text-base text-slate-300 leading-relaxed">
                    Tu distribuidor de confianza para cuentas y perfiles de Netflix 4K, Disney+, Max, Prime Video y MagisTV. Entrega segura y protegida en tu <strong>Bóveda Privada de Cliente</strong> con biometría y PIN personal.
                </p>

                <!-- Botones de Acción -->
                <div class="flex flex-wrap items-center gap-3 pt-2">
                    <a href="#catalogo" class="px-5 py-3 rounded-2xl bg-red-600 hover:bg-red-500 text-white font-bold text-xs flex items-center gap-2 shadow-xl shadow-red-600/30 transition-all cursor-pointer">
                        <i data-lucide="tv" class="w-4 h-4"></i>
                        <span>Ver Cuentas Disponibles</span>
                    </a>

                    <a href="{{ route('boveda.login') }}" class="px-5 py-3 rounded-2xl bg-slate-800/90 hover:bg-slate-700 text-slate-200 border border-slate-700 font-bold text-xs flex items-center gap-2 transition-all cursor-pointer">
                        <i data-lucide="key" class="w-4 h-4 text-amber-400"></i>
                        <span>Acceder a Mi Bóveda</span>
                    </a>

                    @if($supportWhatsapp)
                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $supportWhatsapp) }}?text=Hola%20{{ urlencode($brandName) }},%20tengo%20una%20consulta%20sobre%20tus%20servicios%20de%20streaming." target="_blank" class="px-5 py-3 rounded-2xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs flex items-center gap-2 shadow-xl shadow-emerald-600/20 transition-all">
                        <i data-lucide="message-circle" class="w-4 h-4"></i>
                        <span>Contactar por WhatsApp</span>
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </section>

    @else
    <!-- ========================================== -->
    <!-- LANDING PAGE SUPER ADMIN (VENTA DE PANELES & CRÉDITOS) -->
    <!-- ========================================== -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8 sm:pt-14 space-y-12">
        <div class="text-center max-w-4xl mx-auto space-y-5">
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-red-600/10 border border-red-500/20 text-red-400 text-xs font-bold tracking-wide">
                <span class="w-2 h-2 rounded-full bg-red-500 animate-ping"></span>
                <span>PLATAFORMA OFICIAL MAYORISTA & SISTEMA DE PANELES STREAMING</span>
            </div>

            <h1 class="text-4xl sm:text-6xl font-black text-white tracking-tight leading-tight">
                Lanza Tu Propia Franquicia de <br class="hidden sm:inline">
                <span class="bg-gradient-to-r from-red-500 via-rose-400 to-amber-400 bg-clip-text text-transparent">Cuentas Streaming</span> con Marca Blanca
            </h1>

            <p class="text-base sm:text-lg text-slate-300 leading-relaxed max-w-2xl mx-auto">
                Gestiona y vende Netflix 4K, Disney+, Max y más. Panel propio, activación con créditos, <strong>Bóveda con Biometría</strong> para clientes, consignación 48hs y comisiones en tiempo real.
            </p>

            <div class="flex flex-wrap items-center justify-center gap-3 pt-3">
                <a href="#planes-paneles" class="px-6 py-3.5 rounded-2xl bg-gradient-to-r from-red-600 to-rose-600 hover:opacity-90 text-white font-black text-xs uppercase tracking-wider shadow-2xl shadow-red-600/30 flex items-center gap-2 transition-all cursor-pointer">
                    <i data-lucide="layers" class="w-4 h-4"></i>
                    <span>Ver Planes Básico & PRO</span>
                </a>

                <a href="#cotizador-creditos" class="px-6 py-3.5 rounded-2xl bg-slate-800 hover:bg-slate-700 text-sky-400 font-bold text-xs uppercase tracking-wider border border-slate-700 flex items-center gap-2 transition-all cursor-pointer">
                    <i data-lucide="calculator" class="w-4 h-4"></i>
                    <span>Cotizar Créditos de Panel</span>
                </a>

                <a href="{{ route('boveda.login') }}" class="px-6 py-3.5 rounded-2xl bg-amber-500/10 hover:bg-amber-500/20 text-amber-300 font-bold text-xs uppercase tracking-wider border border-amber-500/30 flex items-center gap-2 transition-all cursor-pointer">
                    <i data-lucide="shield-check" class="w-4 h-4 text-amber-400"></i>
                    <span>Bóveda Clientes</span>
                </a>
            </div>
        </div>

        <!-- Métricas de Confianza -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 max-w-4xl mx-auto">
            <div class="p-4 rounded-2xl bg-slate-900/80 border border-slate-800 text-center">
                <span class="text-2xl font-black text-white block">+15.000</span>
                <span class="text-xs text-slate-400">Cuentas Entregadas</span>
            </div>
            <div class="p-4 rounded-2xl bg-slate-900/80 border border-slate-800 text-center">
                <span class="text-2xl font-black text-emerald-400 block">99.9%</span>
                <span class="text-xs text-slate-400">Uptime & Garantía</span>
            </div>
            <div class="p-4 rounded-2xl bg-slate-900/80 border border-slate-800 text-center">
                <span class="text-2xl font-black text-sky-400 block">48hs</span>
                <span class="text-xs text-slate-400">Consignación de Gracia</span>
            </div>
            <div class="p-4 rounded-2xl bg-slate-900/80 border border-slate-800 text-center">
                <span class="text-2xl font-black text-amber-400 block">WebAuthn</span>
                <span class="text-xs text-slate-400">Biometría Bóveda</span>
            </div>
        </div>
    </section>

    <!-- ======================================================== -->
    <!-- COMPARATIVA DE PANELES: BÁSICO (10 SERVICIOS) vs PRO ILIMITADO -->
    <!-- ======================================================== -->
    <section id="planes-paneles" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8 scroll-mt-24">
        <div class="text-center max-w-2xl mx-auto space-y-2">
            <h2 class="text-3xl font-black text-white tracking-tight">Elige Tu Tipo de Panel de Streaming</h2>
            <p class="text-xs sm:text-sm text-slate-400">
                El Super Admin vende paquetes de créditos para activación y renovación de paneles por 30 días.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-5xl mx-auto">
            <!-- PANEL BÁSICO (10 SERVICIOS) -->
            <div class="rounded-3xl bg-[#111827] border border-amber-500/30 p-7 sm:p-9 flex flex-col justify-between relative shadow-2xl hover:border-amber-500/60 transition-all">
                <div class="space-y-6">
                    <div class="flex items-center justify-between">
                        <span class="px-3 py-1 rounded-full text-xs font-bold font-mono bg-amber-500/20 text-amber-300 border border-amber-500/30">
                            📦 PANEL BÁSICO
                        </span>
                        <span class="text-xs text-slate-400 font-bold">10 Servicios Max</span>
                    </div>

                    <div>
                        <h3 class="text-2xl font-black text-white">Panel Distribuidor Básico</h3>
                        <p class="text-xs text-slate-400 mt-1">Ideal para revendedores que comercializan hasta 10 plataformas principales.</p>
                    </div>

                    <!-- Precios de Créditos -->
                    <div class="p-4 rounded-2xl bg-slate-900/90 border border-slate-800 space-y-2">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-amber-400 block">Tarifas Oficiales de Créditos:</span>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-300">Pack 10 Créditos:</span>
                            <span class="text-white font-mono font-bold">$2.500 <span class="text-[10px] text-slate-400 font-sans">c/u ($25.000 total)</span></span>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-300">Promo 5 Créditos:</span>
                            <span class="text-white font-mono font-bold">$3.000 <span class="text-[10px] text-slate-400 font-sans">c/u ($15.000 total)</span></span>
                        </div>
                        <span class="text-[10px] text-slate-400 block pt-1 border-t border-slate-800">
                            * Cada crédito activa o renueva 1 panel básico por 30 días.
                        </span>
                    </div>

                    <!-- Características -->
                    <ul class="space-y-2.5 text-xs text-slate-300">
                        <li class="flex items-center gap-2">
                            <i data-lucide="check" class="w-4 h-4 text-emerald-400 shrink-0"></i>
                            <span>Catálogo limitado a <strong>10 servicios activos</strong></span>
                        </li>
                        <li class="flex items-center gap-2">
                            <i data-lucide="check" class="w-4 h-4 text-emerald-400 shrink-0"></i>
                            <span>Bóveda privada de clientes con <strong>PIN y Biometría</strong></span>
                        </li>
                        <li class="flex items-center gap-2">
                            <i data-lucide="check" class="w-4 h-4 text-emerald-400 shrink-0"></i>
                            <span>Entrega protegida: sin saldo no se revelan credenciales</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <i data-lucide="check" class="w-4 h-4 text-emerald-400 shrink-0"></i>
                            <span>Ventas a consignación con 48hs de gracia</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <i data-lucide="check" class="w-4 h-4 text-emerald-400 shrink-0"></i>
                            <span>Soporte por WhatsApp integrado</span>
                        </li>
                    </ul>
                </div>

                <div class="pt-6 mt-6 border-t border-slate-800">
                    @if($supportWhatsapp)
                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $supportWhatsapp) }}?text=Hola,%20quisiera%20solicitar%20la%20activaci%C3%B3n%20de%20un%20PANEL%20B%C3%81SICO%20(10%20Servicios)%20y%20comprar%20cr%C3%A9ditos." target="_blank" class="w-full py-3 rounded-2xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs uppercase tracking-wider flex items-center justify-center gap-2 transition-all shadow-lg shadow-amber-500/20">
                        <i data-lucide="message-circle" class="w-4 h-4"></i>
                        <span>Solicitar Panel Básico</span>
                    </a>
                    @endif
                </div>
            </div>

            <!-- PANEL PRO (SERVICIOS ILIMITADOS) -->
            <div class="rounded-3xl bg-gradient-to-b from-[#141b2d] to-[#0f172a] border-2 border-purple-500/50 p-7 sm:p-9 flex flex-col justify-between relative shadow-2xl shadow-purple-500/10 hover:border-purple-400 transition-all">
                <!-- Badge Destacado -->
                <div class="absolute -top-3.5 right-6 px-3 py-1 rounded-full bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-black text-[10px] uppercase tracking-wider shadow-lg">
                    🔥 RECOMENDADO - MÁXIMA GANANCIA
                </div>

                <div class="space-y-6">
                    <div class="flex items-center justify-between">
                        <span class="px-3 py-1 rounded-full text-xs font-bold font-mono bg-purple-500/20 text-purple-300 border border-purple-500/30">
                            💎 PANEL PRO (ILIMITADO)
                        </span>
                        <span class="text-xs text-purple-300 font-bold">Servicios ILIMITADOS</span>
                    </div>

                    <div>
                        <h3 class="text-2xl font-black text-white">Panel Mayorista PRO Ilimitado</h3>
                        <p class="text-xs text-slate-400 mt-1">Para proveedores y super revendedores que quieren libertad total de servicios y stock.</p>
                    </div>

                    <!-- Precios de Créditos -->
                    <div class="p-4 rounded-2xl bg-slate-900/90 border border-purple-500/30 space-y-2">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-purple-400 block">Tarifas Oficiales de Créditos:</span>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-300">Pack 10 Créditos:</span>
                            <span class="text-white font-mono font-bold">$4.000 <span class="text-[10px] text-slate-400 font-sans">c/u ($40.000 total)</span></span>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-300">Promo 5 Créditos:</span>
                            <span class="text-white font-mono font-bold">$5.000 <span class="text-[10px] text-slate-400 font-sans">c/u ($25.000 total)</span></span>
                        </div>
                        <span class="text-[10px] text-slate-400 block pt-1 border-t border-slate-800">
                            * Cada crédito activa o renueva 1 panel PRO ilimitado por 30 días.
                        </span>
                    </div>

                    <!-- Características -->
                    <ul class="space-y-2.5 text-xs text-slate-300">
                        <li class="flex items-center gap-2">
                            <i data-lucide="check-circle-2" class="w-4 h-4 text-purple-400 shrink-0"></i>
                            <span><strong>Servicios y plataformas ILIMITADAS</strong></span>
                        </li>
                        <li class="flex items-center gap-2">
                            <i data-lucide="check-circle-2" class="w-4 h-4 text-purple-400 shrink-0"></i>
                            <span><strong>Agrega tus servicios individuales propios</strong></span>
                        </li>
                        <li class="flex items-center gap-2">
                            <i data-lucide="check-circle-2" class="w-4 h-4 text-purple-400 shrink-0"></i>
                            <span>Copia completa del inventario y cuentas de tu proveedor</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <i data-lucide="check-circle-2" class="w-4 h-4 text-purple-400 shrink-0"></i>
                            <span>Marca Blanca total: tu propio nombre, logo y colores</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <i data-lucide="check-circle-2" class="w-4 h-4 text-purple-400 shrink-0"></i>
                            <span>Comisiones personalizadas por plataforma (Netflix, Disney, etc.)</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <i data-lucide="check-circle-2" class="w-4 h-4 text-purple-400 shrink-0"></i>
                            <span>Soporte WhatsApp propio o derivado a tu proveedor</span>
                        </li>
                    </ul>
                </div>

                <div class="pt-6 mt-6 border-t border-slate-800">
                    @if($supportWhatsapp)
                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $supportWhatsapp) }}?text=Hola,%20quisiera%20solicitar%20la%20activaci%C3%B3n%20de%20un%20PANEL%20PRO%20(Ilimitado)%20y%20comprar%20cr%C3%A9ditos." target="_blank" class="w-full py-3 rounded-2xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:opacity-90 text-white font-black text-xs uppercase tracking-wider flex items-center justify-center gap-2 transition-all shadow-lg shadow-purple-600/30">
                        <i data-lucide="message-circle" class="w-4 h-4"></i>
                        <span>Solicitar Panel PRO (Ilimitado)</span>
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <!-- ======================================================== -->
    <!-- COTIZADOR & CALCULADORA EN VIVO DE CRÉDITOS -->
    <!-- ======================================================== -->
    <section id="cotizador-creditos" class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 scroll-mt-24">
        <div class="rounded-3xl bg-gradient-to-r from-slate-900 via-[#10182b] to-slate-900 border border-slate-800 p-6 sm:p-9 space-y-6 shadow-2xl relative overflow-hidden">
            <div class="flex items-center gap-3 pb-4 border-b border-slate-800">
                <div class="w-12 h-12 rounded-2xl bg-sky-500/20 text-sky-400 flex items-center justify-center font-bold">
                    <i data-lucide="calculator" class="w-6 h-6"></i>
                </div>
                <div>
                    <h3 class="text-xl font-black text-white">Cotizador de Créditos de Activación</h3>
                    <p class="text-xs text-slate-400">Calcula al instante el costo de los créditos vendidos por el Super Admin</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- Selector de Tier -->
                <div class="space-y-2">
                    <label class="block text-xs font-bold uppercase text-slate-400">1. Tipo de Panel a Activar</label>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <button type="button" @click="calcTier = 'basic'" :class="calcTier === 'basic' ? 'bg-amber-500 text-slate-950 font-black border-amber-400 shadow' : 'bg-slate-950 text-slate-300 border-slate-800'" class="p-3 rounded-xl border text-center transition-all cursor-pointer">
                            <span>📦 BÁSICO</span>
                            <span class="block text-[10px] opacity-80">(10 Servicios)</span>
                        </button>
                        <button type="button" @click="calcTier = 'pro'" :class="calcTier === 'pro' ? 'bg-purple-600 text-white font-black border-purple-500 shadow' : 'bg-slate-950 text-slate-300 border-slate-800'" class="p-3 rounded-xl border text-center transition-all cursor-pointer">
                            <span>💎 PRO</span>
                            <span class="block text-[10px] opacity-80">(Ilimitado)</span>
                        </button>
                    </div>
                </div>

                <!-- Selector de Cantidad -->
                <div class="space-y-2">
                    <label class="block text-xs font-bold uppercase text-slate-400">2. Cantidad de Créditos</label>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <button type="button" @click="calcQty = 10" :class="calcQty === 10 ? 'bg-emerald-600 text-white font-black border-emerald-500 shadow' : 'bg-slate-950 text-slate-300 border-slate-800'" class="p-3 rounded-xl border text-center transition-all cursor-pointer">
                            <span>Pack 10 Créditos</span>
                            <span class="block text-[10px] text-emerald-300 font-mono" x-text="calcTier === 'basic' ? '$2.500 c/u' : '$4.000 c/u'"></span>
                        </button>
                        <button type="button" @click="calcQty = 5" :class="calcQty === 5 ? 'bg-emerald-600 text-white font-black border-emerald-500 shadow' : 'bg-slate-950 text-slate-300 border-slate-800'" class="p-3 rounded-xl border text-center transition-all cursor-pointer">
                            <span>Promo 5 Créditos</span>
                            <span class="block text-[10px] text-emerald-300 font-mono" x-text="calcTier === 'basic' ? '$3.000 c/u' : '$5.000 c/u'"></span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Resumen en Vivo -->
            <div class="p-5 rounded-2xl bg-slate-950/90 border border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <span class="text-xs text-slate-400 font-semibold block">Total a Abonar por el Pack:</span>
                    <div class="flex items-baseline gap-2 mt-0.5">
                        <span class="text-3xl font-black text-emerald-400 font-mono" x-text="'$' + totalPrice.toLocaleString('es-AR', {minimumFractionDigits: 2})"></span>
                        <span class="text-xs text-slate-400 font-mono" x-text="'(' + calcQty + ' créditos a $' + unitPrice.toLocaleString('es-AR') + ' c/u)'"></span>
                    </div>
                </div>

                @if($supportWhatsapp)
                <a :href="'https://wa.me/{{ preg_replace('/[^0-9]/', '', $supportWhatsapp) }}?text=Hola,%20quiero%20comprar%20el%20Pack%20de%20' + calcQty + '%20cr%C3%A9ditos%20para%20Panel%20' + calcTier.toUpperCase() + '%20por%20$' + totalPrice.toLocaleString('es-AR') + '.'" target="_blank" class="px-5 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs flex items-center justify-center gap-2 shadow-lg shadow-emerald-600/30 transition-all">
                    <i data-lucide="message-circle" class="w-4 h-4"></i>
                    <span>Ordenar Pack por WhatsApp</span>
                </a>
                @endif
            </div>
        </div>
    </section>
    @endif

    <!-- ======================================================== -->
    <!-- CATÁLOGO DE PLATAFORMAS STREAMING (PVP AL PÚBLICO) -->
    <!-- ======================================================== -->
    <section id="catalogo" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8 scroll-mt-24">
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 pb-2 border-b border-slate-800">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-red-400 block font-mono">Disfruta Tu Contenido Favorito</span>
                <h2 class="text-2xl sm:text-3xl font-black text-white">Catálogo de Cuentas & Pantallas</h2>
            </div>

            <!-- Categories Pills -->
            <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none">
                <a href="{{ route('catalog.index', array_merge(request()->except('category'), ['ref' => request('ref')])) }}" class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all shrink-0 {{ !request('category') ? 'bg-red-600 text-white shadow-md' : 'bg-slate-900 text-slate-400 hover:text-white' }}">
                    Todos
                </a>
                @foreach($categories as $cat)
                <a href="{{ route('catalog.index', array_merge(request()->except('category'), ['category' => $cat, 'ref' => request('ref')])) }}" class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all shrink-0 {{ request('category') === $cat ? 'bg-red-600 text-white shadow-md' : 'bg-slate-900 text-slate-400 hover:text-white' }}">
                    {{ ucfirst($cat) }}
                </a>
                @endforeach
            </div>
        </div>

        <!-- Grid de Servicios -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($services as $serv)
            <div class="bg-[#111827] border border-slate-800 rounded-3xl p-6 flex flex-col justify-between hover:border-slate-700 transition-all hover:scale-[1.01] shadow-xl group relative overflow-hidden">
                <!-- Glow Accent -->
                <div class="absolute -top-12 -right-12 w-28 h-28 rounded-full blur-2xl pointer-events-none opacity-20" style="background-color: {{ $serv->color_hex }}"></div>

                <div>
                    <!-- Header Tarjeta -->
                    <div class="flex items-center justify-between gap-3 mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-2xl flex items-center justify-center font-black text-white shadow-lg text-lg shrink-0" style="background-color: {{ $serv->color_hex }}">
                                {{ substr($serv->name, 0, 2) }}
                            </div>
                            <div>
                                <h3 class="text-lg font-black text-white group-hover:text-red-400 transition-colors">
                                    {{ $serv->name }}
                                </h3>
                                <span class="text-[11px] text-slate-400 capitalize">{{ $serv->category }} · Ultra HD 4K</span>
                            </div>
                        </div>

                        @if($serv->isIndividual())
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-purple-500/20 text-purple-300 border border-purple-500/30">
                            Exclusivo
                        </span>
                        @else
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                            ONLINE
                        </span>
                        @endif
                    </div>

                    <p class="text-xs text-slate-400 mt-2 line-clamp-2">
                        {{ $serv->description ?: 'Disfruta de tus películas y series favoritas con perfiles privados o cuenta completa.' }}
                    </p>

                    <!-- Planes de la Plataforma -->
                    <div class="mt-4 pt-3 border-t border-slate-800/80 space-y-2.5">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Planes Disponibles:</span>
                        @foreach($serv->plans as $plan)
                        <div class="p-3 rounded-2xl bg-[#0b0f19] border border-slate-800 flex items-center justify-between">
                            <div>
                                <span class="text-xs font-bold text-white block">{{ $plan->name }}</span>
                                <span class="text-[10px] text-slate-400">{{ $plan->duration_days }} días · {{ $plan->modality === 'profile' ? 'Pantalla con PIN' : 'Cuenta Completa' }}</span>
                            </div>
                            <div class="text-right">
                                <span class="text-base font-black text-emerald-400">${{ number_format($plan->price, 2) }}</span>
                                <a href="{{ route('catalog.checkout', ['plan' => $plan->id, 'seller_id' => $seller->id]) }}" class="block px-3 py-1 rounded-lg bg-red-600 hover:bg-red-500 text-white font-bold text-[11px] mt-1 shadow-md shadow-red-600/20 transition-all text-center">
                                    Adquirir
                                </a>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Footer Tarjeta -->
                <div class="mt-4 pt-3 border-t border-slate-800 text-[11px] text-slate-400 flex items-center justify-between">
                    <span class="flex items-center gap-1 text-emerald-400 font-semibold">
                        <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                        Garantía Oficial
                    </span>
                    <span class="flex items-center gap-1 text-sky-400 font-semibold">
                        <i data-lucide="zap" class="w-3.5 h-3.5"></i>
                        Entrega en Bóveda
                    </span>
                </div>
            </div>
            @empty
            <div class="col-span-3 py-16 text-center text-slate-500 text-sm">
                No hay plataformas disponibles en esta categoría en este momento.
            </div>
            @endforelse
        </div>
    </section>

    <!-- ======================================================== -->
    <!-- SECCIÓN DE SEGURIDAD: BÓVEDA CON BIOMETRÍA & ANTI-PHISHING -->
    <!-- ======================================================== -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="rounded-3xl bg-gradient-to-r from-slate-900 via-[#0e1628] to-slate-900 border border-slate-800 p-8 sm:p-12">
            <div class="max-w-3xl mx-auto text-center space-y-4">
                <span class="text-xs font-mono font-bold text-amber-400 uppercase tracking-wider">Tecnología de Protección Avanzada</span>
                <h2 class="text-3xl sm:text-4xl font-black text-white">¿Por Qué Usar la Bóveda Privada de Cliente?</h2>
                <p class="text-sm text-slate-300 leading-relaxed">
                    Tus credenciales de streaming nunca se pierden en un chat de WhatsApp. Todos tus correos, claves, perfiles y PINs quedan centralizados y protegidos en una plataforma cifrada grado militar.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-10">
                <div class="p-6 rounded-2xl bg-slate-950/60 border border-slate-800 space-y-3 text-center">
                    <div class="w-12 h-12 rounded-xl bg-amber-500/20 text-amber-400 flex items-center justify-center mx-auto">
                        <i data-lucide="fingerprint" class="w-6 h-6"></i>
                    </div>
                    <h3 class="text-base font-bold text-white">Desbloqueo Biométrico & PIN</h3>
                    <p class="text-xs text-slate-400">Protege la visualización de tus contraseñas con tu huella dactilar, Face ID o PIN personal de 4 dígitos.</p>
                </div>

                <div class="p-6 rounded-2xl bg-slate-950/60 border border-slate-800 space-y-3 text-center">
                    <div class="w-12 h-12 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center mx-auto">
                        <i data-lucide="refresh-cw" class="w-6 h-6"></i>
                    </div>
                    <h3 class="text-base font-bold text-white">Renovación en 1 Clic</h3>
                    <p class="text-xs text-slate-400">Solicita extender tu cuenta por 1, 2, 3 o 6 meses sin perder tu perfil, tus series ni tus preferencias.</p>
                </div>

                <div class="p-6 rounded-2xl bg-slate-950/60 border border-slate-800 space-y-3 text-center">
                    <div class="w-12 h-12 rounded-xl bg-sky-500/20 text-sky-400 flex items-center justify-center mx-auto">
                        <i data-lucide="tv" class="w-6 h-6"></i>
                    </div>
                    <h3 class="text-base font-bold text-white">Guía Smart TV & Anti-Hogar</h3>
                    <p class="text-xs text-slate-400">Tutorial interactivo dentro de tu Bóveda para vincular tu televisor sin bloqueos ni caídas por ubicación.</p>
                </div>
            </div>
        </div>
    </section>

</div>
@endsection
