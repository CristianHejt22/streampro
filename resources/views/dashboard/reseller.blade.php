@extends('layouts.app')

@section('title', 'Panel de Revendedor')
@section('page_title', 'Escritorio de Revendedor')
@section('page_subtitle', 'Panel personal de ventas de cuentas streaming y billetera prepago')

@section('content')
<div class="space-y-6">

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
            <div class="mt-4 pt-3 border-t border-gray-800 text-xs">
                <a href="{{ route('sales.index') }}" class="text-cyan-400 hover:text-cyan-300 font-bold flex items-center gap-1">
                    Emitir Cuenta Ahora <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                </a>
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

    <!-- Módulo Proveedor: Sub-Revendedores, Tiendas Clonadas y Ganancias 25% -->
    @if($user->isSuperReseller())
    <div class="p-5 rounded-2xl bg-gradient-to-r from-purple-950/40 via-indigo-950/20 to-gray-900 border border-purple-500/40 shadow-xl space-y-3">
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

</div>
@endsection
