@extends('layouts.public')

@section('title', ($seller && $seller->isReseller()) ? ($seller->brand_name ?: $seller->name) . ' | Cuentas Streaming' : 'CStreame Master Platform | Paneles & Cuentas Streaming')

@section('content')
<div class="space-y-16 pb-16" x-data="{
    calcPack: 'combo_5_5',
    openFaq: null,
    toggleFaq(id) {
        this.openFaq = this.openFaq === id ? null : id;
    },
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
                    Bienvenido a <span class="bg-gradient-to-r from-red-500 via-rose-400 to-amber-400 bg-clip-text text-transparent">{{ $brandName ?? ($seller->brand_name ?? 'CStreame Streaming') }}</span>
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
                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $supportWhatsapp) }}?text=Hola%20{{ urlencode($brandName ?? 'CStreame') }},%20tengo%20una%20consulta%20sobre%20tus%20servicios%20de%20streaming." target="_blank" class="px-5 py-3 rounded-2xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs flex items-center gap-2 shadow-xl shadow-emerald-600/20 transition-all">
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
                    <span>Cotizar Créditos</span>
                </a>

                <a href="#como-funciona" class="px-6 py-3.5 rounded-2xl bg-slate-900 hover:bg-slate-800 text-slate-300 font-bold text-xs uppercase tracking-wider border border-slate-800 flex items-center gap-2 transition-all cursor-pointer">
                    <i data-lucide="help-circle" class="w-4 h-4 text-red-400"></i>
                    <span>¿Cómo Funciona?</span>
                </a>

                <a href="#faq" class="px-6 py-3.5 rounded-2xl bg-slate-900 hover:bg-slate-800 text-slate-300 font-bold text-xs uppercase tracking-wider border border-slate-800 flex items-center gap-2 transition-all cursor-pointer">
                    <i data-lucide="message-square" class="w-4 h-4 text-amber-400"></i>
                    <span>FAQ</span>
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

    <!-- ======================================================== -->
    <!-- SECCIÓN: CÓMO FUNCIONA EL ECOSISTEMA CSTREAME -->
    <!-- ======================================================== -->
    <section id="como-funciona" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8 scroll-mt-24">
        <div class="text-center max-w-3xl mx-auto space-y-3">
            <span class="text-xs font-mono font-bold text-red-400 uppercase tracking-wider">Flujo de Negocio Paso a Paso</span>
            <h2 class="text-3xl sm:text-4xl font-black text-white">¿Cómo Funciona el Ecosistema?</h2>
            <p class="text-xs sm:text-sm text-slate-400 leading-relaxed">
                Un sistema automatizado y seguro diseñado para que mayoristas, proveedores y revendedores comiencen a operar y generar ingresos hoy mismo sin fricciones.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Paso 1 -->
            <div class="bg-gradient-to-b from-[#111827] to-[#0d1424] border border-slate-800 hover:border-red-500/50 rounded-3xl p-6 flex flex-col justify-between transition-all group relative overflow-hidden shadow-xl">
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="w-10 h-10 rounded-2xl bg-red-600/20 text-red-400 font-mono font-black text-sm flex items-center justify-center border border-red-500/30">
                            01
                        </span>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 text-slate-300">
                            Activación
                        </span>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-red-500/10 text-red-400 flex items-center justify-center">
                        <i data-lucide="layers" class="w-6 h-6"></i>
                    </div>
                    <h3 class="text-lg font-black text-white group-hover:text-red-400 transition-colors">
                        1. Adquiere Tu Panel
                    </h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Elige tu <strong>Panel Básico (SPRO PLAY)</strong> de 10 servicios o el <strong>Panel PRO Ilimitado</strong> con Marca Blanca. Si eres revendedor, puedes actualizar a <strong>Panel Administrador (${{ number_format($seller->panel_monthly_fee ?: 15000, 0, ',', '.') }} / 30 días)</strong> para crear tu propia red de sub-revendedores.
                    </p>
                </div>
                <div class="mt-6 pt-4 border-t border-slate-800/80">
                    <span class="text-[11px] font-mono text-emerald-400 flex items-center gap-1.5 font-bold">
                        <i data-lucide="check" class="w-3.5 h-3.5"></i>
                        Entrega Inmediata
                    </span>
                </div>
            </div>

            <!-- Paso 2 -->
            <div class="bg-gradient-to-b from-[#111827] to-[#0d1424] border border-slate-800 hover:border-amber-500/50 rounded-3xl p-6 flex flex-col justify-between transition-all group relative overflow-hidden shadow-xl">
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="w-10 h-10 rounded-2xl bg-amber-500/20 text-amber-400 font-mono font-black text-sm flex items-center justify-center border border-amber-500/30">
                            02
                        </span>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 text-slate-300">
                            Saldo & PVP
                        </span>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-amber-500/10 text-amber-400 flex items-center justify-center">
                        <i data-lucide="wallet" class="w-6 h-6"></i>
                    </div>
                    <h3 class="text-lg font-black text-white group-hover:text-amber-400 transition-colors">
                        2. Carga Saldo y Fija Margen
                    </h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Carga saldo en tu billetera con tu proveedor o administrador patrocinador. Define tus propios <strong>Precios de Venta al Público (PVP)</strong>. Cada venta deduce el costo mayorista y tu ganancia neta queda en tu bolsillo.
                    </p>
                </div>
                <div class="mt-6 pt-4 border-t border-slate-800/80">
                    <span class="text-[11px] font-mono text-amber-400 flex items-center gap-1.5 font-bold">
                        <i data-lucide="check" class="w-3.5 h-3.5"></i>
                        Margen 100% Libre
                    </span>
                </div>
            </div>

            <!-- Paso 3 -->
            <div class="bg-gradient-to-b from-[#111827] to-[#0d1424] border border-slate-800 hover:border-sky-500/50 rounded-3xl p-6 flex flex-col justify-between transition-all group relative overflow-hidden shadow-xl">
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="w-10 h-10 rounded-2xl bg-sky-500/20 text-sky-400 font-mono font-black text-sm flex items-center justify-center border border-sky-500/30">
                            03
                        </span>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 text-slate-300">
                            Bóveda
                        </span>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-sky-500/10 text-sky-400 flex items-center justify-center">
                        <i data-lucide="fingerprint" class="w-6 h-6"></i>
                    </div>
                    <h3 class="text-lg font-black text-white group-hover:text-sky-400 transition-colors">
                        3. Entrega en Bóveda Cliente
                    </h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Tus clientes reciben su acceso privado a la <strong>Bóveda Cifrada</strong> con desbloqueo por <strong>Biometría o PIN de 4 dígitos</strong> (Ajusta en la primera compra su pin de Bóveda: este PIN sirve para consultar y revelar sus contraseñas en cualquier dispositivo o cuando no use biometría, debiendo recordarlo siempre). Sin saldo en tu billetera, el sistema protege las cuentas y no revela credenciales.
                    </p>
                </div>
                <div class="mt-6 pt-4 border-t border-slate-800/80">
                    <span class="text-[11px] font-mono text-sky-400 flex items-center gap-1.5 font-bold">
                        <i data-lucide="check" class="w-3.5 h-3.5"></i>
                        Cero Fugas & Anti-Phishing
                    </span>
                </div>
            </div>

            <!-- Paso 4 -->
            <div class="bg-gradient-to-b from-[#111827] to-[#0d1424] border border-slate-800 hover:border-purple-500/50 rounded-3xl p-6 flex flex-col justify-between transition-all group relative overflow-hidden shadow-xl">
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="w-10 h-10 rounded-2xl bg-purple-500/20 text-purple-400 font-mono font-black text-sm flex items-center justify-center border border-purple-500/30">
                            04
                        </span>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 text-slate-300">
                            Red & Comisión
                        </span>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-purple-500/10 text-purple-400 flex items-center justify-center">
                        <i data-lucide="copy" class="w-6 h-6"></i>
                    </div>
                    <h3 class="text-lg font-black text-white group-hover:text-purple-400 transition-colors">
                        4. Clona y Gana 25% Pasivo
                    </h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        El proveedor o panel administrador clona su catálogo a sus revendedores en segundos. Cada vez que ellos vendan de tus servicios, <strong>se te acredita automáticamente el 25% de comisión neta</strong> en tu saldo.
                    </p>
                </div>
                <div class="mt-6 pt-4 border-t border-slate-800/80">
                    <span class="text-[11px] font-mono text-purple-400 flex items-center gap-1.5 font-bold">
                        <i data-lucide="check" class="w-3.5 h-3.5"></i>
                        Comisión Automática 25%
                    </span>
                </div>
            </div>
        </div>
    </section>

    <!-- ======================================================== -->
    <!-- SECCIÓN: PREGUNTAS FRECUENTES (FAQ) -->
    <!-- ======================================================== -->
    <section id="faq" class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8 scroll-mt-24">
        <div class="text-center space-y-3">
            <span class="text-xs font-mono font-bold text-amber-400 uppercase tracking-wider">Centro de Respuestas</span>
            <h2 class="text-3xl sm:text-4xl font-black text-white">Preguntas Frecuentes (FAQ)</h2>
            <p class="text-xs sm:text-sm text-slate-400">
                Todo lo que necesitas saber sobre el funcionamiento de los paneles, billeteras, bóveda de clientes y comisiones.
            </p>
        </div>

        <div class="space-y-3">
            <!-- FAQ 1 -->
            <div class="rounded-2xl border transition-all overflow-hidden"
                 :class="openFaq === 1 ? 'border-red-500/50 bg-[#131b2e]' : 'border-slate-800 bg-[#0f172a] hover:border-slate-700'">
                <button type="button" @click="toggleFaq(1)" class="w-full p-5 text-left flex items-center justify-between gap-4 cursor-pointer">
                    <span class="font-bold text-sm sm:text-base text-white flex items-center gap-3">
                        <i data-lucide="help-circle" class="w-5 h-5 text-red-400 shrink-0"></i>
                        ¿Puedo empezar a vender y trabajar hoy mismo?
                    </span>
                    <i data-lucide="chevron-down" class="w-5 h-5 text-slate-400 shrink-0 transition-transform duration-200"
                       :class="openFaq === 1 ? 'rotate-180 text-red-400' : ''"></i>
                </button>
                <div x-show="openFaq === 1" x-collapse class="px-5 pb-5 pt-1 text-xs sm:text-sm text-slate-300 border-t border-slate-800/80 leading-relaxed space-y-2">
                    <p>
                        <strong>¡Sí, totalmente!</strong> El sistema cuenta con inventario real y perfiles precargados con PINs individuales (Netflix 4K UHD, Disney+ Estándar, Disney+ Premium VIP y Max Platino).
                    </p>
                    <p>
                        Al registrarte o activar tu panel, sólo necesitas disponer de saldo en tu billetera para emitir licencias de inmediato a tus clientes. La entrega se realiza al segundo a través de la Bóveda Privada de Clientes.
                    </p>
                </div>
            </div>

            <!-- FAQ 2 -->
            <div class="rounded-2xl border transition-all overflow-hidden"
                 :class="openFaq === 2 ? 'border-amber-500/50 bg-[#131b2e]' : 'border-slate-800 bg-[#0f172a] hover:border-slate-700'">
                <button type="button" @click="toggleFaq(2)" class="w-full p-5 text-left flex items-center justify-between gap-4 cursor-pointer">
                    <span class="font-bold text-sm sm:text-base text-white flex items-center gap-3">
                        <i data-lucide="crown" class="w-5 h-5 text-amber-400 shrink-0"></i>
                        ¿Cómo funciona la compra del Panel Administrador ($15.000 / 30 días)?
                    </span>
                    <i data-lucide="chevron-down" class="w-5 h-5 text-slate-400 shrink-0 transition-transform duration-200"
                       :class="openFaq === 2 ? 'rotate-180 text-amber-400' : ''"></i>
                </button>
                <div x-show="openFaq === 2" x-collapse class="px-5 pb-5 pt-1 text-xs sm:text-sm text-slate-300 border-t border-slate-800/80 leading-relaxed space-y-2">
                    <p>
                        Cualquier revendedor puede evolucionar a <strong>Panel Administrador / Super Revendedor</strong> pagando $15.000 por 30 días. La compra se realiza con 1 solo clic utilizando el saldo de su billetera o reportando el comprobante de pago por WhatsApp a su patrocinador.
                    </p>
                    <p>
                        Al ser Administrador Mayorista obtienes la facultad de afiliar a tus propios revendedores, clonarles tu catálogo fijando tus propios costos mayoristas, y <strong>percibir automáticamente el 25% de comisión neta</strong> por cada venta que concrete tu red.
                    </p>
                </div>
            </div>

            <!-- FAQ 3 -->
            <div class="rounded-2xl border transition-all overflow-hidden"
                 :class="openFaq === 3 ? 'border-purple-500/50 bg-[#131b2e]' : 'border-slate-800 bg-[#0f172a] hover:border-slate-700'">
                <button type="button" @click="toggleFaq(3)" class="w-full p-5 text-left flex items-center justify-between gap-4 cursor-pointer">
                    <span class="font-bold text-sm sm:text-base text-white flex items-center gap-3">
                        <i data-lucide="package" class="w-5 h-5 text-purple-400 shrink-0"></i>
                        ¿Qué diferencia hay entre el Panel Básico y el Panel PRO Ilimitado?
                    </span>
                    <i data-lucide="chevron-down" class="w-5 h-5 text-slate-400 shrink-0 transition-transform duration-200"
                       :class="openFaq === 3 ? 'rotate-180 text-purple-400' : ''"></i>
                </button>
                <div x-show="openFaq === 3" x-collapse class="px-5 pb-5 pt-1 text-xs sm:text-sm text-slate-300 border-t border-slate-800/80 leading-relaxed space-y-2">
                    <p>
                        <strong>📦 Panel Básico (10 Servicios - SPRO PLAY):</strong> Diseñado para revendedores iniciales. Incluye hasta 10 servicios oficiales bajo la marca y logo <strong>SPRO PLAY</strong> para venta directa a clientes finales. 
                        <strong class="text-amber-300 block mt-1">⚠️ El básico no revende ningún plan de panel ni afilia revendedores.</strong>
                        Su costo de activación es de <strong>1 Crédito Básico</strong> ($2.500 c/u en pack mínimo de 10 = $25.000).
                    </p>
                    <p>
                        <strong>💎 Panel PRO (Ilimitado - Marca Blanca):</strong> Para proveedores o revendedores consolidados. Ofrece servicios ilimitados, posibilidad de agregar plataformas propias y personalización <strong>100% Marca Blanca</strong> (logo, nombre comercial y colores propios).
                        <strong class="text-purple-300 block mt-1">✓ El PRO tiene acceso a la posibilidad de revender acceso a funciones de panel a sus propios revendedores.</strong>
                        Su costo de activación es de <strong>1 Crédito PRO</strong> ($4.000 c/u en pack mínimo de 10 = $40.000).
                    </p>
                    <p class="text-emerald-400 font-semibold pt-1">
                        🌟 <strong>Pack Combo Dúo (5 de cada uno juntos):</strong> 5 Créditos Básicos ($3.000 c/u = $15.000) + 5 Créditos PRO ($5.000 c/u = $25.000) por un total de <strong>$40.000 juntos</strong> en tu billetera.
                    </p>
                    <p class="text-sky-300 text-xs pt-1 border-t border-slate-800">
                        ⚡ <strong>Regla de Activación:</strong> Las solicitudes de panel quedan registradas en estado <em>Pendiente</em> y <strong>son activadas desde el panel de administrador exclusivamente consumiendo 1 crédito según el plan adquirido</strong> (Básico o PRO).
                    </p>
                </div>
            </div>

            <!-- FAQ 4 -->
            <div class="rounded-2xl border transition-all overflow-hidden"
                 :class="openFaq === 4 ? 'border-sky-500/50 bg-[#131b2e]' : 'border-slate-800 bg-[#0f172a] hover:border-slate-700'">
                <button type="button" @click="toggleFaq(4)" class="w-full p-5 text-left flex items-center justify-between gap-4 cursor-pointer">
                    <span class="font-bold text-sm sm:text-base text-white flex items-center gap-3">
                        <i data-lucide="fingerprint" class="w-5 h-5 text-sky-400 shrink-0"></i>
                        ¿Cómo funciona la Bóveda de Cliente con PIN y Biometría?
                    </span>
                    <i data-lucide="chevron-down" class="w-5 h-5 text-slate-400 shrink-0 transition-transform duration-200"
                       :class="openFaq === 4 ? 'rotate-180 text-sky-400' : ''"></i>
                </button>
                <div x-show="openFaq === 4" x-collapse class="px-5 pb-5 pt-1 text-xs sm:text-sm text-slate-300 border-t border-slate-800/80 leading-relaxed space-y-2">
                    <p>
                        Cada cliente final accede a su portal personal en <code class="text-sky-300 font-mono text-xs">/boveda/login</code> con su número de WhatsApp y contraseña.
                    </p>
                    <p>
                        <strong>🔑 Ajuste en la primera compra:</strong> Al realizar su primera compra o registro, el cliente define su <strong>PIN de Bóveda de 4 dígitos</strong>. Este PIN es su llave personal de seguridad que le servirá para revelar y consultar sus contraseñas, perfiles y pines en cualquier celular o PC cuando no utilice biometría (Face ID o Huella dactilar). <strong>Debe recordarlo siempre</strong> para consultar sus credenciales en el futuro.
                    </p>
                    <p>
                        <strong>🛡️ Cero Fugas & Anti-Phishing:</strong> Si el revendedor no tiene saldo suficiente en su billetera o si no se ha recibido el pago del proveedor, el sistema retiene las credenciales protegiendo las cuentas maestras de fugas o exposiciones no autorizadas.
                    </p>
                </div>
            </div>

            <!-- FAQ 5 -->
            <div class="rounded-2xl border transition-all overflow-hidden"
                 :class="openFaq === 5 ? 'border-emerald-500/50 bg-[#131b2e]' : 'border-slate-800 bg-[#0f172a] hover:border-slate-700'">
                <button type="button" @click="toggleFaq(5)" class="w-full p-5 text-left flex items-center justify-between gap-4 cursor-pointer">
                    <span class="font-bold text-sm sm:text-base text-white flex items-center gap-3">
                        <i data-lucide="clock" class="w-5 h-5 text-emerald-400 shrink-0"></i>
                        ¿Cómo funcionan las licencias en consignación y el plazo de 48 horas?
                    </span>
                    <i data-lucide="chevron-down" class="w-5 h-5 text-slate-400 shrink-0 transition-transform duration-200"
                       :class="openFaq === 5 ? 'rotate-180 text-emerald-400' : ''"></i>
                </button>
                <div x-show="openFaq === 5" x-collapse class="px-5 pb-5 pt-1 text-xs sm:text-sm text-slate-300 border-t border-slate-800/80 leading-relaxed space-y-2">
                    <p>
                        El revendedor puede emitir licencias de streaming en modalidad <strong>consignación</strong> por meses seleccionables (1, 2, 3 o 6 meses) para habilitar a su cliente al momento.
                    </p>
                    <p>
                        La plataforma otorga una <strong>ventana estricta de 48 horas de gracia</strong>. Si la licencia o el costo mayorista no es liquidado antes del vencimiento del plazo, la cuenta se deshabilita automáticamente, protegiendo al proveedor y al revendedor de pérdidas por impagos.
                    </p>
                </div>
            </div>

            <!-- FAQ 6 -->
            <div class="rounded-2xl border transition-all overflow-hidden"
                 :class="openFaq === 6 ? 'border-rose-500/50 bg-[#131b2e]' : 'border-slate-800 bg-[#0f172a] hover:border-slate-700'">
                <button type="button" @click="toggleFaq(6)" class="w-full p-5 text-left flex items-center justify-between gap-4 cursor-pointer">
                    <span class="font-bold text-sm sm:text-base text-white flex items-center gap-3">
                        <i data-lucide="shield-alert" class="w-5 h-5 text-rose-400 shrink-0"></i>
                        ¿Por qué no se entregan cuentas si no hay saldo en billetera?
                    </span>
                    <i data-lucide="chevron-down" class="w-5 h-5 text-slate-400 shrink-0 transition-transform duration-200"
                       :class="openFaq === 6 ? 'rotate-180 text-rose-400' : ''"></i>
                </button>
                <div x-show="openFaq === 6" x-collapse class="px-5 pb-5 pt-1 text-xs sm:text-sm text-slate-300 border-t border-slate-800/80 leading-relaxed space-y-2">
                    <p>
                        Por seguridad financiera y operativa: <strong>ninguna cuenta se entrega o revela sin el costo mayorista cubierto en la billetera</strong> o sin el pago asentado del proveedor.
                    </p>
                    <p>
                        Esto garantiza que los inventarios nunca queden expuestos ni se originen deudas incobrables entre revendedores y proveedores.
                    </p>
                </div>
            </div>

            <!-- FAQ 7 -->
            <div class="rounded-2xl border transition-all overflow-hidden"
                 :class="openFaq === 7 ? 'border-teal-500/50 bg-[#131b2e]' : 'border-slate-800 bg-[#0f172a] hover:border-slate-700'">
                <button type="button" @click="toggleFaq(7)" class="w-full p-5 text-left flex items-center justify-between gap-4 cursor-pointer">
                    <span class="font-bold text-sm sm:text-base text-white flex items-center gap-3">
                        <i data-lucide="message-square" class="w-5 h-5 text-teal-400 shrink-0"></i>
                        ¿A qué número de WhatsApp van las consultas y renovaciones?
                    </span>
                    <i data-lucide="chevron-down" class="w-5 h-5 text-slate-400 shrink-0 transition-transform duration-200"
                       :class="openFaq === 7 ? 'rotate-180 text-teal-400' : ''"></i>
                </button>
                <div x-show="openFaq === 7" x-collapse class="px-5 pb-5 pt-1 text-xs sm:text-sm text-slate-300 border-t border-slate-800/80 leading-relaxed space-y-2">
                    <p>
                        Cada panelista (proveedor o revendedor) configura su número de WhatsApp en la sección de Preferencias de su panel. En caso de ser una tienda clonada, las solicitudes de soporte y renovaciones de los clientes de la Bóveda llegan directamente al revendedor, o si éste lo prefiere, al WhatsApp de su proveedor patrocinador para que le brinde soporte directo.
                    </p>
                    <p class="text-teal-300 font-semibold pt-1 border-t border-slate-800/80">
                        📱 Todo el proceso también se gestiona por la Bóveda del Cliente y le llega directamente a su panel. El mismo revendedor deberá solicitar revisión al proveedor desde su panel si requiere soporte o autorización directa, quedando registros y mensajes de auditoría entre ambos.
                    </p>
                </div>
            </div>
        </div>
    </section>

</div>
@endsection
