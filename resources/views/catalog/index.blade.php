@extends('layouts.public')

@section('title', ($seller && $seller->isReseller()) ? ($seller->brand_name ?: $seller->name) . ' | Cuentas Streaming' : 'CStreame Master Platform | Paneles & Cuentas Streaming')

@section('content')
<div class="space-y-16 pb-16" x-data="{
    calcPack: 'combo_5_5',
    get packDetails() {
        if (this.calcPack === 'basic_10') {
            return {
                title: 'Pack 10 Créditos Básicos',
                tierBadge: '📦 BÁSICO (10 SERVICIOS)',
                desc: '10 créditos para activar paneles básicos (hasta 10 servicios)',
                unitPriceText: '$2.500 c/u (mínimo 10 créditos)',
                totalPrice: 25000,
                qtyText: '10 Créditos Básicos'
            };
        } else if (this.calcPack === 'pro_10') {
            return {
                title: 'Pack 10 Créditos PRO',
                tierBadge: '💎 PRO ILIMITADO',
                desc: '10 créditos para activar paneles PRO (servicios ilimitados)',
                unitPriceText: '$4.000 c/u (mínimo 10 créditos)',
                totalPrice: 40000,
                qtyText: '10 Créditos PRO'
            };
        } else {
            return {
                title: 'Pack Combo Dúo (5 de Cada Uno)',
                tierBadge: '🌟 5 BÁSICOS + 5 PRO JUNTOS',
                desc: '5 créditos Básico a $3.000 ($15.000) + 5 créditos PRO a $5.000 ($25.000)',
                unitPriceText: '5 Básicos ($3.000 c/u) + 5 PRO ($5.000 c/u)',
                totalPrice: 40000,
                qtyText: '10 Créditos Mixtos (5 Básicos + 5 PRO)'
            };
        }
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
                        <span class="text-[10px] font-bold uppercase tracking-wider text-amber-400 block">Tarifa Oficial de Créditos:</span>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-300">Pack 10 Créditos Básicos:</span>
                            <span class="text-white font-mono font-bold">$2.500 <span class="text-[10px] text-slate-400 font-sans">c/u ($25.000 total)</span></span>
                        </div>
                        <span class="text-[10px] text-slate-400 block pt-1 border-t border-slate-800">
                            * Mínimo 10 créditos. Cada crédito activa o renueva 1 panel básico por 30 días.
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
                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $supportWhatsapp) }}?text=Hola,%20quisiera%20solicitar%20la%20activaci%C3%B3n%20de%20un%20PANEL%20B%C3%81SICO%20(10%20Servicios)%20y%20el%20Pack%20de%2010%20cr%C3%A9ditos%20por%20$25.000." target="_blank" class="w-full py-3 rounded-2xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs uppercase tracking-wider flex items-center justify-center gap-2 transition-all shadow-lg shadow-amber-500/20 cursor-pointer">
                        <i data-lucide="message-circle" class="w-4 h-4"></i>
                        <span>Solicitar Panel Básico (10 Créditos)</span>
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
                        <p class="text-xs text-slate-400 mt-1">Para proveedor o revendedor que quiere libertad total de servicios y stock.</p>
                    </div>

                    <!-- Precios de Créditos -->
                    <div class="p-4 rounded-2xl bg-slate-900/90 border border-purple-500/30 space-y-2">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-purple-400 block">Tarifa Oficial de Créditos:</span>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-300">Pack 10 Créditos PRO:</span>
                            <span class="text-white font-mono font-bold">$4.000 <span class="text-[10px] text-slate-400 font-sans">c/u ($40.000 total)</span></span>
                        </div>
                        <span class="text-[10px] text-slate-400 block pt-1 border-t border-slate-800">
                            * Mínimo 10 créditos. Cada crédito activa o renueva 1 panel PRO ilimitado por 30 días.
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
                            <span>Copia completa del inventario y cuentas de tu proveedor o revendedor patrocinador</span>
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
                            <span>Soporte WhatsApp propio o derivado a tu proveedor o revendedor patrocinador</span>
                        </li>
                    </ul>
                </div>

                <div class="pt-6 mt-6 border-t border-slate-800">
                    @if($supportWhatsapp)
                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $supportWhatsapp) }}?text=Hola,%20quisiera%20solicitar%20la%20activaci%C3%B3n%20de%20un%20PANEL%20PRO%20(Ilimitado)%20y%20el%20Pack%20de%2010%20cr%C3%A9ditos%20por%20$40.000." target="_blank" class="w-full py-3 rounded-2xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:opacity-90 text-white font-black text-xs uppercase tracking-wider flex items-center justify-center gap-2 transition-all shadow-lg shadow-purple-600/30 cursor-pointer">
                        <i data-lucide="message-circle" class="w-4 h-4"></i>
                        <span>Solicitar Panel PRO (10 Créditos)</span>
                    </a>
                    @endif
                </div>
            </div>
        </div>

        <!-- ======================================================== -->
        <!-- PACK COMBO DÚO: 5 DE CADA UNO JUNTOS -->
        <!-- ======================================================== -->
        <div class="max-w-5xl mx-auto rounded-3xl bg-gradient-to-r from-amber-950/40 via-[#131b2e] to-purple-950/40 border-2 border-emerald-500/50 p-6 sm:p-8 shadow-2xl relative overflow-hidden flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-2">
                <div class="flex items-center gap-2">
                    <span class="px-3 py-1 rounded-full text-xs font-black bg-emerald-500 text-slate-950 uppercase tracking-wider">
                        🌟 PACK COMBO DÚO (5 DE CADA UNO)
                    </span>
                    <span class="text-xs font-bold text-emerald-400 font-mono">10 Créditos Mixtos</span>
                </div>
                <h3 class="text-xl sm:text-2xl font-black text-white">5 Créditos Básico + 5 Créditos PRO Juntos</h3>
                <p class="text-xs text-slate-300 max-w-xl leading-relaxed">
                    Para proveedor o revendedor que desea activar y gestionar ambos tipos de paneles con flexibilidad total: 
                    5 créditos Básico a $3.000 c/u ($15.000) y 5 créditos PRO a $5.000 c/u ($25.000).
                </p>
                <div class="flex flex-wrap items-center gap-2 pt-1 text-xs font-mono">
                    <span class="px-2.5 py-1 rounded-xl bg-amber-500/15 border border-amber-500/30 text-amber-300">📦 5 Básicos: $15.000 ($3.000 c/u)</span>
                    <span class="px-2.5 py-1 rounded-xl bg-purple-500/15 border border-purple-500/30 text-purple-300">💎 5 PRO: $25.000 ($5.000 c/u)</span>
                </div>
            </div>

            <div class="flex flex-col sm:items-end justify-center shrink-0 space-y-3">
                <div class="sm:text-right">
                    <span class="text-xs text-slate-400 uppercase font-bold block">Inversión Total Combo:</span>
                    <span class="text-3xl font-black text-emerald-400 font-mono">$40.000</span>
                    <span class="text-[11px] text-slate-400 block font-mono">10 créditos listos en tu billetera</span>
                </div>
                @if($supportWhatsapp)
                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $supportWhatsapp) }}?text=Hola,%20quisiera%20solicitar%20el%20PACK%20COMBO%20D%C3%9AO%20(5%20B%C3%A1sicos%20+%205%20PRO)%20por%20$40.000." target="_blank" class="px-6 py-3 rounded-2xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs flex items-center gap-2 shadow-xl shadow-emerald-600/30 transition-all cursor-pointer">
                    <i data-lucide="message-circle" class="w-4 h-4"></i>
                    <span>Solicitar Combo Dúo ($40k)</span>
                </a>
                @endif
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
                    <p class="text-xs text-slate-400">Selecciona el paquete para proveedor o revendedor y cotiza al instante</p>
                </div>
            </div>

            <!-- Selector de Paquete Oficial -->
            <div class="space-y-3">
                <label class="block text-xs font-bold uppercase text-slate-400">Selecciona el Paquete de Créditos</label>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                    <!-- 10 Básicos -->
                    <button type="button" @click="calcPack = 'basic_10'" 
                            :class="calcPack === 'basic_10' ? 'bg-amber-500/20 border-amber-500 ring-2 ring-amber-500/30 text-white' : 'bg-slate-950 border-slate-800 text-slate-400 hover:border-slate-700'"
                            class="p-4 rounded-2xl border text-left flex flex-col justify-between transition-all cursor-pointer">
                        <div>
                            <span class="text-[10px] font-mono font-bold text-amber-400 block uppercase">10 Básicos</span>
                            <strong class="text-white text-sm block mt-1">Pack 10 Básicos</strong>
                            <span class="text-[11px] text-slate-400 block mt-0.5">$2.500 c/u</span>
                        </div>
                        <div class="mt-3 pt-2 border-t border-slate-800/80 font-black text-white font-mono text-sm">
                            $25.000
                        </div>
                    </button>

                    <!-- 10 PRO -->
                    <button type="button" @click="calcPack = 'pro_10'" 
                            :class="calcPack === 'pro_10' ? 'bg-purple-500/20 border-purple-500 ring-2 ring-purple-500/30 text-white' : 'bg-slate-950 border-slate-800 text-slate-400 hover:border-slate-700'"
                            class="p-4 rounded-2xl border text-left flex flex-col justify-between transition-all cursor-pointer">
                        <div>
                            <span class="text-[10px] font-mono font-bold text-purple-400 block uppercase">10 PRO</span>
                            <strong class="text-white text-sm block mt-1">Pack 10 PRO</strong>
                            <span class="text-[11px] text-slate-400 block mt-0.5">$4.000 c/u · Ilimitado</span>
                        </div>
                        <div class="mt-3 pt-2 border-t border-slate-800/80 font-black text-white font-mono text-sm">
                            $40.000
                        </div>
                    </button>

                    <!-- Combo 5 de Cada Uno -->
                    <button type="button" @click="calcPack = 'combo_5_5'" 
                            :class="calcPack === 'combo_5_5' ? 'bg-emerald-500/20 border-emerald-500 ring-2 ring-emerald-500/30 text-white' : 'bg-slate-950 border-slate-800 text-slate-400 hover:border-slate-700'"
                            class="p-4 rounded-2xl border text-left flex flex-col justify-between transition-all cursor-pointer">
                        <div>
                            <span class="text-[10px] font-mono font-bold text-emerald-400 block uppercase">Combo Dúo</span>
                            <strong class="text-white text-sm block mt-1">5 de Cada Uno</strong>
                            <span class="text-[11px] text-slate-400 block mt-0.5">5 Básicos + 5 PRO</span>
                        </div>
                        <div class="mt-3 pt-2 border-t border-slate-800/80 font-black text-emerald-400 font-mono text-sm">
                            $40.000
                        </div>
                    </button>
                </div>
            </div>

            <!-- Resumen en Vivo -->
            <div class="p-5 rounded-2xl bg-slate-950/90 border border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="space-y-1">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold font-mono bg-slate-800 text-slate-300 inline-block" x-text="packDetails.tierBadge"></span>
                    <div class="flex items-baseline gap-2 mt-0.5">
                        <span class="text-3xl font-black text-emerald-400 font-mono" x-text="'$' + packDetails.totalPrice.toLocaleString('es-AR', {minimumFractionDigits: 2})"></span>
                        <span class="text-xs text-slate-400 font-mono" x-text="'(' + packDetails.unitPriceText + ')'"></span>
                    </div>
                    <p class="text-xs text-slate-400" x-text="packDetails.desc"></p>
                </div>

                @if($supportWhatsapp)
                <a :href="'https://wa.me/{{ preg_replace('/[^0-9]/', '', $supportWhatsapp) }}?text=Hola,%20quiero%20comprar%20el%20' + encodeURIComponent(packDetails.title) + '%20por%20$' + packDetails.totalPrice.toLocaleString('es-AR') + '.'" target="_blank" class="px-5 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs flex items-center justify-center gap-2 shadow-lg shadow-emerald-600/30 transition-all shrink-0 cursor-pointer">
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
