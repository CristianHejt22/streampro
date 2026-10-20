@extends('layouts.app')

@section('title', 'Panel de Revendedor')
@section('page_title', 'Escritorio de Revendedor')
@section('page_subtitle', 'Panel personal de ventas de cuentas streaming y billetera prepago')

@section('content')
<div class="space-y-6" x-data="{ rechargeModal: false }">

    <!-- Tarjeta Principal de Billetera y Métricas -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Saldo Billetera -->
        <div class="p-6 rounded-2xl bg-gradient-to-br from-gray-900 via-gray-900 to-[#0e1424] border border-cyan-500/40 shadow-xl relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-cyan-400">Saldo Disponible</span>
                <i data-lucide="wallet" class="w-6 h-6 text-cyan-400"></i>
            </div>
            <div class="mt-4">
                <span class="text-3xl font-black text-emerald-400">${{ number_format($user->balance, 2) }}</span>
                <span class="text-xs text-gray-400 block mt-1">Crédito prepago para emitir cuentas</span>
            </div>
            <div class="mt-4 pt-3 border-t border-gray-800 text-xs flex items-center justify-between">
                <a href="{{ route('sales.index') }}" class="text-cyan-400 hover:text-cyan-300 font-bold flex items-center gap-1">
                    Emitir Cuenta <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                </a>
                <button type="button" @click="rechargeModal = true" class="px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-[11px] flex items-center gap-1 shadow cursor-pointer">
                    <i data-lucide="plus-circle" class="w-3.5 h-3.5"></i>
                    Cargar Saldo
                </button>
            </div>
        </div>

        <!-- Mis Ventas Activas -->
        <div class="p-6 rounded-2xl bg-[#111827] border border-gray-800 shadow-lg">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-400">Ventas Activas</span>
                <i data-lucide="key" class="w-6 h-6 text-blue-400"></i>
            </div>
            <div class="mt-4">
                <span class="text-3xl font-black text-white">{{ $myActiveSales }}</span>
                <span class="text-xs text-gray-400 block mt-1">de {{ $mySalesCount }} históricas</span>
            </div>
            <div class="mt-4 pt-3 border-t border-gray-800 text-xs text-blue-400 font-semibold">
                Servicios en funcionamiento
            </div>
        </div>

        <!-- Cuentas por Vencer (Alerta de cobro) -->
        <div class="p-6 rounded-2xl bg-[#111827] border border-amber-500/30 shadow-lg {{ $myExpiringSoon > 0 ? 'bg-amber-950/10' : '' }}">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-amber-400">Por Vencer (<= 3 Días)</span>
                <i data-lucide="clock" class="w-6 h-6 text-amber-400"></i>
            </div>
            <div class="mt-4">
                <span class="text-3xl font-black text-amber-400">{{ $myExpiringSoon }}</span>
                <span class="text-xs text-gray-400 block mt-1">listas para cobrar renovación</span>
            </div>
            <div class="mt-4 pt-3 border-t border-gray-800 text-xs">
                <a href="{{ route('sales.index', ['status' => 'expiring_soon']) }}" class="text-amber-400 hover:underline flex items-center gap-1 font-semibold">
                    Ver y Avisar por WhatsApp <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                </a>
            </div>
        </div>

        <!-- Clientes Totales -->
        <div class="p-6 rounded-2xl bg-[#111827] border border-gray-800 shadow-lg">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-400">Mis Clientes CRM</span>
                <i data-lucide="users" class="w-6 h-6 text-purple-400"></i>
            </div>
            <div class="mt-4">
                <span class="text-3xl font-black text-white">{{ $myCustomersCount }}</span>
                <span class="text-xs text-gray-400 block mt-1">contactos guardados</span>
            </div>
            <div class="mt-4 pt-3 border-t border-gray-800 text-xs">
                <a href="{{ route('customers.index') }}" class="text-purple-400 hover:underline flex items-center gap-1 font-semibold">
                    Abrir Agenda CRM <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                </a>
            </div>
        </div>
    </div>

    @if(!$user->isSuperReseller())
    <!-- Venta y Ascenso a Panel Administrador / Proveedor para Revendedores -->
    <div class="p-6 rounded-3xl bg-gradient-to-r from-purple-950/60 via-slate-900 to-indigo-950/40 border-2 border-purple-500/40 shadow-2xl space-y-4 relative overflow-hidden">
        <div class="absolute -top-12 -right-12 w-48 h-48 bg-purple-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="flex flex-col md:flex-row md:items-center justify-between gap-5 relative z-10">
            <div class="flex items-start gap-4">
                <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-purple-600 to-indigo-600 flex items-center justify-center text-white shadow-xl shadow-purple-600/30 text-2xl font-black shrink-0">
                    <i data-lucide="crown" class="w-7 h-7"></i>
                </div>
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-gradient-to-r from-purple-500 to-indigo-500 text-white font-mono">
                            💎 ADQUIRIR PANEL ADMINISTRADOR
                        </span>
                        <span class="text-xs font-mono font-bold text-purple-300">$15.000 / 30 Días</span>
                    </div>
                    <h3 class="text-xl font-black text-white">¿Quieres Vender Paneles y Tener Tus Propios Revendedores?</h3>
                    <p class="text-xs text-slate-300 max-w-2xl leading-relaxed">
                        Conviértete en <strong>Proveedor / Administrador (Super Reseller)</strong>. Podrás afiliar revendedores con tu propio formulario, clonarles tu catálogo ajustando tus precios mayoristas de costo y ganar un <strong>25% de comisión neta automática</strong> por cada cuenta que vendan.
                    </p>
                </div>
            </div>

            <div class="flex flex-col sm:items-end justify-center shrink-0 space-y-2">
                <form method="POST" action="{{ route('resellers.upgrade_admin_panel') }}" class="w-full sm:w-auto">
                    @csrf
                    <button type="submit" onclick="return confirm('¿Confirmas adquirir tu Panel Administrador / Proveedor por $15.000 descontados de tu saldo de billetera?')" class="w-full sm:w-auto px-6 py-3.5 rounded-2xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-black text-xs uppercase tracking-wider flex items-center justify-center gap-2 shadow-xl shadow-purple-600/30 transition-all cursor-pointer">
                        <i data-lucide="zap" class="w-4 h-4"></i>
                        <span>Activar con Mi Saldo ($15.000)</span>
                    </button>
                </form>

                <div class="flex items-center gap-2 text-xs">
                    <button type="button" @click="rechargeModal = true" class="text-sky-400 hover:underline font-bold cursor-pointer">
                        Cargar Saldo para Activar
                    </button>
                    @if($user->parent && $user->parent->support_whatsapp)
                    <span class="text-slate-600">•</span>
                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $user->parent->support_whatsapp) }}?text=Hola,%20quiero%20adquirir%20mi%20Panel%20Administrador%20/%20Proveedor%20para%20crear%20mis%20propios%20revendedores." target="_blank" class="text-emerald-400 hover:underline font-bold flex items-center gap-1">
                        <i data-lucide="message-circle" class="w-3 h-3"></i>
                        Pedir a mi Proveedor
                    </a>
                    @endif
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-3 border-t border-purple-500/20 text-xs text-slate-300">
            <div class="flex items-center gap-2">
                <i data-lucide="check-circle" class="w-4 h-4 text-emerald-400 shrink-0"></i>
                <span>Formulario público propio para registrar revendedores</span>
            </div>
            <div class="flex items-center gap-2">
                <i data-lucide="check-circle" class="w-4 h-4 text-emerald-400 shrink-0"></i>
                <span>Ajuste individual de tus costos mayoristas</span>
            </div>
            <div class="flex items-center gap-2">
                <i data-lucide="check-circle" class="w-4 h-4 text-emerald-400 shrink-0"></i>
                <span>25% comisión acreditada de inmediato</span>
            </div>
        </div>
    </div>
    @endif

    <!-- Módulo Proveedor: Sub-Revendedores, Tiendas Clonadas y Ganancias 25% -->
    @if($user->isSuperReseller())
    <div class="p-5 rounded-2xl bg-gradient-to-r from-purple-950/40 via-indigo-950/20 to-gray-900 border border-purple-500/40 shadow-xl space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-gray-800 pb-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-purple-500/20 text-purple-400 border border-purple-500/40 flex items-center justify-center font-bold text-lg">
                    <i data-lucide="network" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-base font-black text-white flex items-center gap-2">
                        <span>Panel de Proveedor o Revendedor de Cuentas</span>
                        <span class="text-xs px-2.5 py-0.5 rounded-full bg-purple-500/20 text-purple-300 border border-purple-500/30 font-mono font-bold">
                            Catálogo & Tiendas Clonadas
                        </span>
                    </h3>
                    <p class="text-xs text-gray-400">Control de tus revendedores asignados, cobro de copias y comisiones del 25% acumuladas</p>
                </div>
            </div>
            <a href="{{ route('resellers.index') }}" class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs flex items-center gap-1.5 shadow-lg shadow-purple-600/30 w-max">
                <i data-lucide="sliders" class="w-3.5 h-3.5"></i>
                <span>Gestionar Mis Revendedores</span>
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-1">
            <div class="p-3.5 rounded-xl bg-[#0b0f19] border border-gray-800">
                <span class="text-[11px] text-gray-400 uppercase font-bold block">Revendedores Patrocinados</span>
                <span class="text-xl font-black text-white mt-1 block">{{ $subResellersCount }} usuarios</span>
            </div>
            <div class="p-3.5 rounded-xl bg-[#0b0f19] border border-gray-800">
                <span class="text-[11px] text-gray-400 uppercase font-bold block">Tiendas Clonadas Activas</span>
                <span class="text-xl font-black text-sky-400 mt-1 block">{{ $clonedPanelsCount }} tiendas ($15k/mes)</span>
            </div>
            <div class="p-3.5 rounded-xl bg-[#0b0f19] border border-gray-800">
                <span class="text-[11px] text-gray-400 uppercase font-bold block">Comisiones Ganadas (25% Patrocinio)</span>
                <span class="text-xl font-black text-emerald-400 mt-1 block">${{ number_format($sponsorshipEarnings, 2) }}</span>
            </div>
        </div>

        <!-- Visualizador de Créditos PRO y Básicos para Proveedor -->
        <div class="p-4 rounded-2xl bg-[#0b0f19] border border-purple-500/40 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-purple-300 flex items-center gap-2">
                    <i data-lucide="gem" class="w-4 h-4 text-purple-400"></i>
                    Billetera de Créditos de Activación de Paneles
                </span>
                <a href="{{ route('resellers.index') }}" class="text-[11px] font-mono text-purple-300 hover:underline font-bold flex items-center gap-1">
                    Activar Solicitudes <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="p-3 rounded-xl bg-slate-900/90 border border-amber-500/30 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-bold uppercase text-amber-400 font-mono">📦 Créditos BÁSICO</span>
                        <div class="flex items-baseline gap-2 mt-0.5">
                            <span class="text-2xl font-black text-white font-mono">{{ $user->credits_basic ?? 0 }}</span>
                            <span class="text-[11px] text-amber-300 font-mono font-bold">disponibles</span>
                        </div>
                        <span class="text-[10px] text-slate-400 block mt-0.5">Paneles SPRO PLAY (10 serv)</span>
                    </div>
                    <div class="w-9 h-9 rounded-lg bg-amber-500/10 text-amber-400 flex items-center justify-center font-bold">
                        <i data-lucide="package" class="w-4 h-4"></i>
                    </div>
                </div>

                <div class="p-3 rounded-xl bg-slate-900/90 border border-purple-500/30 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-bold uppercase text-purple-400 font-mono">💎 Créditos PRO</span>
                        <div class="flex items-baseline gap-2 mt-0.5">
                            <span class="text-2xl font-black text-white font-mono">{{ $user->credits_pro ?? 0 }}</span>
                            <span class="text-[11px] text-purple-300 font-mono font-bold">disponibles</span>
                        </div>
                        <span class="text-[10px] text-slate-400 block mt-0.5">Paneles PRO Marca Blanca</span>
                    </div>
                    <div class="w-9 h-9 rounded-lg bg-purple-500/10 text-purple-400 flex items-center justify-center font-bold">
                        <i data-lucide="crown" class="w-4 h-4"></i>
                    </div>
                </div>

                <div class="p-3 rounded-xl bg-slate-900/90 border border-emerald-500/30 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-bold uppercase text-emerald-400 font-mono">🌟 Total Créditos</span>
                        <div class="flex items-baseline gap-2 mt-0.5">
                            <span class="text-2xl font-black text-emerald-400 font-mono">{{ ($user->credits_basic ?? 0) + ($user->credits_pro ?? 0) }}</span>
                            <span class="text-[11px] text-slate-400 font-mono">en billetera</span>
                        </div>
                        <span class="text-[10px] text-slate-400 block mt-0.5">Listos para activar</span>
                    </div>
                    <div class="w-9 h-9 rounded-lg bg-emerald-500/10 text-emerald-400 flex items-center justify-center font-bold">
                        <i data-lucide="zap" class="w-4 h-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Caja de Afiliación con Formulario de Registro para Revendedores -->
        <div class="p-4 rounded-xl bg-[#0b0f19] border border-purple-500/30 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <span class="text-[10px] uppercase font-bold text-purple-400 block tracking-wider">Tu Enlace de Afiliación / Registro de Revendedores:</span>
                <div class="mt-1 flex items-center gap-2">
                    <code class="px-2.5 py-1 rounded bg-black/60 border border-gray-800 text-xs text-purple-300 font-mono select-all">
                        {{ route('affiliate.register', ['code' => $user->referral_code]) }}
                    </code>
                </div>
                <p class="text-[11px] text-gray-400 mt-1">
                    Envía este formulario a tus revendedores para que se registren automáticamente y tengan copia de tu catálogo con tus precios de proveedor.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2 shrink-0">
                <button onclick="navigator.clipboard.writeText('{{ route('affiliate.register', ['code' => $user->referral_code]) }}'); alert('¡Enlace de registro copiado!');" class="px-3 py-1.5 rounded-xl bg-gray-800 hover:bg-gray-700 text-white font-medium text-xs flex items-center gap-1.5 transition-all border border-gray-700 cursor-pointer">
                    <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                    <span>Copiar Enlace</span>
                </button>
                <a href="https://wa.me/?text={{ urlencode('¡Hola! Regístrate como revendedor con panel propio en nuestra plataforma aquí: ' . route('affiliate.register', ['code' => $user->referral_code])) }}" target="_blank" class="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs flex items-center gap-1.5 transition-all shadow cursor-pointer">
                    <i data-lucide="message-circle" class="w-3.5 h-3.5"></i>
                    <span>WhatsApp</span>
                </a>
                <a href="{{ route('affiliate.register', ['code' => $user->referral_code]) }}" target="_blank" class="px-3 py-1.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs flex items-center gap-1.5 transition-all shadow shadow-purple-600/30">
                    <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                    <span>Abrir Formulario</span>
                </a>
            </div>
        </div>
    </div>
    @endif

    <!-- Alert Box: Solicitudes de Renovación Pendientes de Clientes -->
    @if(isset($pendingRenewalsCount) && $pendingRenewalsCount > 0)
    <div class="p-4 rounded-2xl bg-gradient-to-r from-cyan-950/40 via-blue-950/20 to-gray-900 border border-cyan-500/30 shadow-xl flex items-center justify-between">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-cyan-500/20 text-cyan-400 border border-cyan-500/40 flex items-center justify-center font-bold text-xl shrink-0 animate-pulse">
                <i data-lucide="refresh-cw" class="w-6 h-6"></i>
            </div>
            <div>
                <h3 class="text-base font-bold text-white">Tienes {{ $pendingRenewalsCount }} solicitud(es) de renovación pendientes de clientes</h3>
                <p class="text-xs text-gray-400">Tus clientes solicitaron renovar su cuenta desde la Bóveda. Revisa el comprobante y aprueba la extensión.</p>
            </div>
        </div>
        <a href="{{ route('sales.index') }}" class="px-4 py-2 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white font-semibold text-xs transition-all shadow-lg shadow-cyan-600/30 shrink-0 flex items-center gap-2">
            <span>Aprobar Renovación</span>
            <i data-lucide="arrow-right" class="w-4 h-4"></i>
        </a>
    </div>
    @endif

    <!-- Alert Box: Cuentas en Consignación 48hs -->
    @if(isset($consignmentsActive) && $consignmentsActive->isNotEmpty())
    <div class="p-5 rounded-2xl bg-[#111827] border border-amber-500/40 shadow-xl space-y-3">
        <div class="flex items-center justify-between border-b border-gray-800 pb-2">
            <div class="flex items-center gap-2 text-amber-400">
                <i data-lucide="clock" class="w-5 h-5 animate-pulse"></i>
                <h3 class="text-sm font-bold text-white uppercase tracking-wider">Tus Cuentas en Consignación (48 Horas de Plazo)</h3>
            </div>
            <span class="text-xs font-mono font-bold text-amber-400 bg-amber-500/10 px-2.5 py-0.5 rounded-full border border-amber-500/20">
                {{ $consignmentsActive->count() }} activas por liquidar
            </span>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
            @foreach($consignmentsActive as $cSale)
            @php
                $hrs = $cSale->consignment_hours_remaining ?? 48;
            @endphp
            <div class="p-3.5 rounded-xl bg-[#0b0f19] border border-gray-800 flex items-center justify-between">
                <div>
                    <span class="font-bold text-white text-xs block">{{ $cSale->service ? $cSale->service->name : 'Streaming' }}</span>
                    <span class="text-[11px] text-gray-400">Cliente: {{ $cSale->customer ? $cSale->customer->name : 'Consignatario' }}</span>
                    <span class="text-[10px] text-amber-400 font-mono block">Costo: ${{ number_format($cSale->cost_price, 2) }}</span>
                </div>
                <div class="text-right">
                    <span class="text-xs font-mono font-bold {{ $hrs <= 12 ? 'text-rose-400 animate-pulse' : 'text-amber-400' }} block">
                        ⏳ {{ $hrs }}h restantes
                    </span>
                    <form method="POST" action="{{ route('sales.pay_consignment', $cSale) }}" class="mt-1">
                        @csrf
                        <button type="submit" class="px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-[10px] font-bold shadow cursor-pointer">
                            Liquidar Ahora
                        </button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Banner Mi Tienda Personal con Marca Blanca -->
    <div class="p-5 rounded-2xl bg-gradient-to-r from-red-950/40 via-purple-950/20 to-gray-900 border border-red-500/30 shadow-xl flex flex-col md:flex-row items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-red-600/20 text-red-400 border border-red-500/40 flex items-center justify-center font-bold text-xl shrink-0">
                <i data-lucide="store" class="w-6 h-6"></i>
            </div>
            <div>
                <h3 class="text-base font-bold text-white">Tu Enlace de Tienda Personal con Marca Blanca</h3>
                <p class="text-xs text-gray-400">Los clientes que compren desde este enlace verán tu marca, logo y recibirás sus pedidos en tu módulo de Tesorería.</p>
                <div class="mt-2 flex items-center gap-2">
                    <code class="px-2.5 py-1 rounded bg-black/60 border border-gray-800 text-xs text-red-400 font-mono select-all">
                        {{ route('catalog.referral', ['code' => $user->referral_code]) }}
                    </code>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <button onclick="navigator.clipboard.writeText('{{ route('catalog.referral', ['code' => $user->referral_code]) }}'); alert('¡Enlace de tu tienda copiado al portapapeles!');" class="px-4 py-2.5 rounded-xl bg-gray-800 hover:bg-gray-700 text-white font-medium text-xs flex items-center gap-1.5 transition-all border border-gray-700">
                <i data-lucide="copy" class="w-4 h-4"></i>
                <span>Copiar Enlace</span>
            </button>
            <a href="{{ route('catalog.referral', ['code' => $user->referral_code]) }}" target="_blank" class="px-4 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold text-xs flex items-center gap-1.5 transition-all shadow-lg shadow-red-600/30">
                <i data-lucide="external-link" class="w-4 h-4"></i>
                <span>Abrir Mi Tienda</span>
            </a>
        </div>
    </div>

    <!-- Catálogo de Cuentas Listas para Emitir -->
    <div class="p-5 rounded-2xl bg-[#111827] border border-gray-800 shadow-lg">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <i data-lucide="shopping-cart" class="w-5 h-5 text-emerald-400"></i>
                <h3 class="text-sm font-bold text-white uppercase tracking-wider">Cuentas Disponibles para Vender (Precios Mayoristas)</h3>
            </div>
            <a href="{{ route('sales.index') }}" class="text-xs text-red-400 hover:text-red-300 font-semibold flex items-center gap-1">
                Ir al Panel de Ventas <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($services as $serv)
            @foreach($serv->plans as $plan)
            <div class="p-4 rounded-xl bg-[#0b0f19] border border-gray-800 hover:border-gray-700 transition-all flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs px-2 py-0.5 rounded text-white font-bold" style="background-color: {{ $serv->color_hex }}">
                            {{ $serv->name }}
                        </span>
                        <span class="text-xs font-semibold text-emerald-400">
                            {{ $serv->available_profiles }} disp.
                        </span>
                    </div>
                    <h4 class="text-sm font-bold text-white mt-2">{{ $plan->name }}</h4>
                    <p class="text-[11px] text-gray-400 mt-1">Duración: {{ $plan->duration_days }} días · Modalidad: {{ $plan->modality === 'profile' ? 'Pantalla con PIN' : 'Cuenta Completa' }}</p>
                </div>
                <div class="mt-4 pt-3 border-t border-gray-800/80 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] text-gray-400 uppercase block">Costo Mayorista</span>
                        <span class="text-base font-black text-cyan-400">${{ number_format($plan->reseller_price, 2) }}</span>
                    </div>
                    <div>
                        <span class="text-[10px] text-gray-400 uppercase block">Sugerido Venta</span>
                        <span class="text-base font-black text-emerald-400">${{ number_format($plan->price, 2) }}</span>
                    </div>
                </div>
            </div>
            @endforeach
            @endforeach
        </div>
    </div>

    <!-- Mis Ventas Recientes -->
    <div class="p-5 rounded-2xl bg-[#111827] border border-gray-800 shadow-lg">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <i data-lucide="history" class="w-5 h-5 text-blue-400"></i>
                <h3 class="text-sm font-bold text-white uppercase tracking-wider">Mis Últimas Ventas Emitidas</h3>
            </div>
            <a href="{{ route('sales.index') }}" class="text-xs text-blue-400 hover:underline">Ver Todas las Ventas</a>
        </div>

        @if($recentSales->isEmpty())
        <div class="text-center py-8 text-gray-400 text-xs">
            Aún no has registrado ventas. ¡Emite tu primera cuenta desde el botón superior o comparte tu tienda!
        </div>
        @else
        <div class="space-y-2.5">
            @foreach($recentSales as $sale)
            <div class="p-3.5 rounded-xl bg-[#0b0f19] border border-gray-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-white">{{ $sale->sale_code }}</span>
                        <span class="text-[10px] px-1.5 py-0.5 rounded text-white font-medium" style="background-color: {{ $sale->service->color_hex ?? '#333' }}">
                            {{ $sale->service->name }}
                        </span>
                        <span class="text-[10px] px-1.5 py-0.5 rounded bg-gray-800 text-gray-300">
                            {{ $sale->profile_name_snapshot ?? 'Cuenta Completa' }}
                        </span>
                    </div>
                    <div class="text-[11px] text-gray-400 mt-1">
                        Cliente: <strong class="text-white">{{ $sale->customer->name }}</strong> · Vence: <strong class="{{ $sale->days_remaining <= 3 ? 'text-amber-400' : 'text-gray-300' }}">{{ $sale->end_date->format('d/m/Y') }}</strong> ({{ $sale->days_remaining }} días rest.)
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    @if($sale->customer->clean_phone)
                    <a href="https://wa.me/{{ $sale->customer->clean_phone }}?text={{ urlencode($sale->whats_app_delivery_message) }}" target="_blank" class="px-2.5 py-1.5 rounded-lg bg-emerald-600/20 text-emerald-400 border border-emerald-500/30 hover:bg-emerald-600 hover:text-white font-semibold text-xs flex items-center gap-1 transition-all">
                        <i data-lucide="message-circle" class="w-3.5 h-3.5"></i>
                        <span>WhatsApp</span>
                    </a>
                    @endif
                    <a href="{{ route('sales.index', ['search' => $sale->sale_code]) }}" class="px-2.5 py-1.5 rounded-lg bg-gray-800 hover:bg-gray-700 text-gray-300 font-semibold text-xs">
                        Ver Datos
                    </a>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    @if(isset($myTransfers) && $myTransfers->isNotEmpty())
    <!-- Historial de Transferencias / Recargas Enviadas al Proveedor -->
    <div class="p-5 rounded-2xl bg-[#111827] border border-gray-800 shadow-lg space-y-3">
        <div class="flex items-center justify-between border-b border-gray-800 pb-2">
            <div class="flex items-center gap-2 text-sky-400">
                <i data-lucide="arrow-up-right" class="w-5 h-5"></i>
                <h3 class="text-sm font-bold text-white uppercase tracking-wider">Tus Notificaciones de Saldo al Proveedor</h3>
            </div>
            <button @click="rechargeModal = true" class="text-xs text-emerald-400 hover:underline font-semibold cursor-pointer">
                + Nueva Recarga
            </button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-gray-200">
                <thead>
                    <tr class="border-b border-gray-800 text-[10px] uppercase font-bold text-gray-400 bg-black/20">
                        <th class="py-2 px-3">Fecha</th>
                        <th class="py-2 px-3">Monto</th>
                        <th class="py-2 px-3">Método & Ref</th>
                        <th class="py-2 px-3">Estado</th>
                        <th class="py-2 px-3">Comprobante</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800/60 font-mono">
                    @foreach($myTransfers as $tr)
                    <tr>
                        <td class="py-2 px-3 text-gray-400">{{ $tr->created_at->format('d/m/Y H:i') }}</td>
                        <td class="py-2 px-3 font-black text-emerald-400">${{ number_format($tr->amount, 2) }}</td>
                        <td class="py-2 px-3 text-gray-300 font-sans">
                            <span class="font-bold uppercase">{{ $tr->payment_method }}</span>
                            @if($tr->reference_code)
                            <span class="text-[10px] text-gray-500 block">Ref: {{ $tr->reference_code }}</span>
                            @endif
                        </td>
                        <td class="py-2 px-3">
                            @if($tr->status === 'approved')
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                                ✓ Acreditado
                            </span>
                            @elseif($tr->status === 'rejected')
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/20 text-rose-400 border border-rose-500/30">
                                ✕ Rechazado
                            </span>
                            @else
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-400 border border-amber-500/30 animate-pulse">
                                ⏳ Pendiente
                            </span>
                            @endif
                        </td>
                        <td class="py-2 px-3">
                            @if($tr->receipt_path)
                            <a href="{{ asset('storage/' . $tr->receipt_path) }}" target="_blank" class="text-sky-400 hover:underline flex items-center gap-1 font-sans text-[11px]">
                                <i data-lucide="file-text" class="w-3.5 h-3.5"></i> Ver
                            </a>
                            @else
                            <span class="text-gray-500 font-sans text-[11px]">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- ========================================== -->
    <!-- MODAL: NOTIFICAR RECARGA / TRANSFERENCIA AL PROVEEDOR -->
    <!-- ========================================== -->
    <div x-show="rechargeModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-md" @click.self="rechargeModal = false">
        <div class="w-full max-w-md bg-[#111827] border border-gray-800 rounded-3xl shadow-2xl p-6 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-gray-800">
                <div class="flex items-center gap-2.5 text-emerald-400">
                    <i data-lucide="wallet" class="w-6 h-6"></i>
                    <div>
                        <h3 class="font-black text-white text-base">Cargar Saldo / Transferir a Proveedor</h3>
                        <span class="text-xs text-gray-400">Notifica tu pago para que tu proveedor te acredite saldo de inmediato</span>
                    </div>
                </div>
                <button @click="rechargeModal = false" class="text-gray-400 hover:text-white text-lg font-bold cursor-pointer">✕</button>
            </div>

            <form action="{{ route('resellers.balance_transfers.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Monto Transferido ($) *</label>
                    <input type="number" step="0.01" min="1" name="amount" required placeholder="Ej: 25000" class="w-full px-4 py-2.5 rounded-xl bg-gray-950 border border-gray-800 text-sm font-mono text-emerald-400 font-bold focus:border-emerald-500 outline-none">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Medio de Pago *</label>
                        <select name="payment_method" required class="w-full px-3 py-2.5 rounded-xl bg-gray-950 border border-gray-800 text-xs text-white focus:border-emerald-500 outline-none">
                            <option value="transferencia">Transferencia Bancaria</option>
                            <option value="mercadopago">Mercado Pago</option>
                            <option value="binance_usdt">Binance (USDT)</option>
                            <option value="efectivo">Efectivo</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Nº Comprobante / Ref</label>
                        <input type="text" name="reference_code" placeholder="Ej: MP-982344" class="w-full px-3 py-2.5 rounded-xl bg-gray-950 border border-gray-800 text-xs text-white focus:border-emerald-500 outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Foto o PDF del Comprobante (Opcional)</label>
                    <input type="file" name="receipt" accept="image/*,application/pdf" class="w-full text-xs text-gray-400 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-gray-800 file:text-sky-300 hover:file:bg-gray-700 cursor-pointer">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Mensaje / Notas para el Proveedor</label>
                    <textarea name="reseller_notes" rows="2" placeholder="Ej: Pago de cuentas para recargar billetera..." class="w-full px-3 py-2 rounded-xl bg-gray-950 border border-gray-800 text-xs text-white focus:border-emerald-500 outline-none"></textarea>
                </div>

                <div class="p-3 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-300 text-xs">
                    ⚡ Al recibir y verificar la transferencia, tu proveedor o el Super Admin acreditarán tu saldo al instante para que tus entregas sean automáticas.
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="rechargeModal = false" class="px-4 py-2 text-xs text-gray-400 hover:text-white cursor-pointer">Cancelar</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-lg shadow-emerald-600/30 cursor-pointer">
                        Enviar Notificación de Saldo
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
