@extends('layouts.app')

@section('title', 'Escritorio General')
@section('page_title', 'Escritorio de Estadísticas')
@section('page_subtitle', 'Panel de control integral de ventas de streaming, stock y vencimientos')

@section('content')
<div class="space-y-6">

    <!-- KPI Metric Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Recaudado -->
        <div class="p-5 rounded-2xl bg-[#111827] border border-gray-800 shadow-lg relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-400">Total Facturado</span>
                <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center">
                    <i data-lucide="dollar-sign" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="mt-4">
                <span class="text-2xl font-black text-white">${{ number_format($totalRevenue, 2) }}</span>
                <span class="text-xs text-emerald-400 block mt-1">Ganancia neta: ${{ number_format($totalProfit, 2) }}</span>
            </div>
        </div>

        <!-- Ventas Activas -->
        <div class="p-5 rounded-2xl bg-[#111827] border border-gray-800 shadow-lg relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-400">Ventas Activas</span>
                <div class="w-10 h-10 rounded-xl bg-blue-500/10 border border-blue-500/20 text-blue-400 flex items-center justify-center">
                    <i data-lucide="key" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="mt-4 flex items-baseline gap-2">
                <span class="text-2xl font-black text-white">{{ $activeSalesCount }}</span>
                <span class="text-xs text-gray-400">de {{ $totalSalesCount }} totales</span>
            </div>
            <div class="mt-1 text-xs text-blue-400 font-medium">Clientes activos en servicio</div>
        </div>

        <!-- Cuentas por Vencer (Alerta) -->
        <div class="p-5 rounded-2xl bg-[#111827] border border-amber-500/30 shadow-lg relative overflow-hidden {{ $expiringSoonCount > 0 ? 'bg-amber-950/10' : '' }}">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-amber-400">Por Vencer (<= 3 Días)</span>
                <div class="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-400 flex items-center justify-center">
                    <i data-lucide="clock" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="mt-4 flex items-baseline gap-2">
                <span class="text-2xl font-black text-amber-400">{{ $expiringSoonCount }}</span>
                <span class="text-xs text-gray-400">requieren aviso</span>
            </div>
            <div class="mt-1 text-xs text-gray-400">
                <a href="{{ route('sales.index', ['status' => 'expiring_soon']) }}" class="text-amber-400 hover:underline flex items-center gap-1">
                    Enviar WhatsApp recordatorio <i data-lucide="arrow-right" class="w-3 h-3"></i>
                </a>
            </div>
        </div>

        <!-- Stock Disponible (Pantallas Libres) -->
        <div class="p-5 rounded-2xl bg-[#111827] border border-gray-800 shadow-lg relative overflow-hidden flex flex-col justify-between" x-data="{ showCount: false }">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-400">Stock Inventario</span>
                <div class="w-10 h-10 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 flex items-center justify-center">
                    <i data-lucide="tv" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="mt-3">
                <button type="button" @click="showCount = !showCount" class="py-1 px-2.5 rounded-lg bg-gray-900 hover:bg-gray-800 border border-gray-800 text-sky-400 hover:text-sky-300 text-[11px] font-bold flex items-center gap-1.5 transition cursor-pointer">
                    <i data-lucide="eye" class="w-3.5 h-3.5" x-show="!showCount"></i>
                    <i data-lucide="eye-off" class="w-3.5 h-3.5" x-show="showCount"></i>
                    <span x-text="showCount ? 'Ocultar' : 'Abrir Sección'"></span>
                </button>
                <div x-show="showCount" x-cloak x-collapse class="mt-2 flex items-baseline gap-2">
                    <span class="text-2xl font-black text-emerald-400">{{ $availableProfilesCount }}</span>
                    <span class="text-xs text-gray-400">pantallas libres</span>
                </div>
            </div>
            <div class="mt-2 text-xs text-gray-400">
                <a href="{{ route('accounts.index') }}" class="text-sky-400 hover:underline">Ver inventario de cuentas &rarr;</a>
            </div>
        </div>

        <!-- Tiendas Clonadas Activas ($15.000 / 30d: $8.000 Admin | $7.000 Proveedor) -->
        <div class="p-5 rounded-2xl bg-[#111827] border border-gray-800 shadow-lg relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-purple-400">Tiendas Clonadas</span>
                <div class="w-10 h-10 rounded-xl bg-purple-500/10 border border-purple-500/20 text-purple-400 flex items-center justify-center">
                    <i data-lucide="copy" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="mt-4 flex items-baseline gap-2">
                <span class="text-2xl font-black text-white">{{ $clonedPanelsCount }}</span>
                <span class="text-xs text-purple-400 font-mono font-bold">${{ number_format($clonedPanelsRevenue, 0) }} / mes</span>
            </div>
            <div class="mt-1 text-[11px] text-gray-400">
                $8.000 Administrador / $7.000 Proveedor por tienda
            </div>
            <div class="mt-1 text-xs text-gray-400">
                <a href="{{ route('resellers.index') }}" class="text-purple-400 hover:underline">Ver paneles de revendedores &rarr;</a>
            </div>
        </div>
    </div>

    <!-- Alert Box: Solicitudes de Renovación Pendientes desde Bóveda de Clientes -->
    @if(isset($pendingRenewalsCount) && $pendingRenewalsCount > 0)
    <div class="p-4 rounded-2xl bg-gradient-to-r from-cyan-950/40 via-blue-950/20 to-gray-900 border border-cyan-500/30 shadow-xl flex items-center justify-between">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-cyan-500/20 text-cyan-400 border border-cyan-500/40 flex items-center justify-center font-bold text-xl shrink-0 animate-pulse">
                <i data-lucide="refresh-cw" class="w-6 h-6"></i>
            </div>
            <div>
                <h3 class="text-base font-bold text-white">Tienes {{ $pendingRenewalsCount }} solicitud(es) de renovación pendientes desde la Bóveda</h3>
                <p class="text-xs text-gray-400">Los clientes solicitaron la extensión de sus cuentas de streaming y adjuntaron comprobante.</p>
            </div>
        </div>
        <a href="{{ route('sales.index') }}" class="px-4 py-2 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white font-semibold text-xs transition-all shadow-lg shadow-cyan-600/30 shrink-0 flex items-center gap-2">
            <span>Revisar y Aprobar</span>
            <i data-lucide="arrow-right" class="w-4 h-4"></i>
        </a>
    </div>
    @endif

    <!-- Alert Box: Ventas por Transferencia Pendientes de Aceptación -->
    @if(isset($consignmentsActive) && $consignmentsActive->isNotEmpty())
    <div class="p-5 rounded-2xl bg-[#111827] border border-amber-500/40 shadow-xl space-y-3">
        <div class="flex items-center justify-between border-b border-gray-800 pb-2">
            <div class="flex items-center gap-2 text-amber-400">
                <i data-lucide="credit-card" class="w-5 h-5 animate-pulse"></i>
                <h3 class="text-sm font-bold text-white uppercase tracking-wider">Ventas por Transferencia Bancaria (Pendientes de Aceptación)</h3>
            </div>
            <span class="text-xs font-mono font-bold text-amber-400 bg-amber-500/10 px-2.5 py-0.5 rounded-full border border-amber-500/20">
                {{ $consignmentsActive->count() }} por Aceptar & Entregar
            </span>
        </div>
        <p class="text-xs text-gray-400">
            Cuentas vendidas por transferencia bancaria. Al aceptar la transferencia, las credenciales se liberan de inmediato en la Bóveda del Cliente.
        </p>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
            @foreach($consignmentsActive as $cSale)
            <div class="p-3.5 rounded-xl bg-[#0b0f19] border border-gray-800 flex items-center justify-between">
                <div>
                    <span class="font-bold text-white text-xs block">{{ $cSale->service ? $cSale->service->name : 'Streaming' }}</span>
                    <span class="text-[11px] text-gray-400">Cliente: {{ $cSale->customer ? $cSale->customer->name : 'Cliente' }}</span>
                    <span class="text-[10px] text-sky-400 font-mono block">Vendedor: {{ $cSale->user ? $cSale->user->name : '' }}</span>
                    <span class="text-[10px] text-amber-400 font-mono block">Costo: ${{ number_format($cSale->cost_price, 2) }}</span>
                    @if($cSale->payment_proof)
                    <a href="{{ asset('storage/' . $cSale->payment_proof) }}" target="_blank" class="inline-flex items-center gap-1 text-[10px] text-purple-400 hover:text-purple-300 font-mono underline mt-1">
                        <i data-lucide="paperclip" class="w-3 h-3"></i> Ver Comprobante
                    </a>
                    @endif
                </div>
                <div class="text-right">
                    <span class="text-xs font-mono font-bold text-amber-400 block mb-1.5">
                        🏦 Por Aceptar
                    </span>
                    <form method="POST" action="{{ route('sales.accept_transfer', $cSale) }}" onsubmit="return confirm('¿Confirmas que recibiste la transferencia y deseas liberar las credenciales al cliente?')">
                        @csrf
                        <button type="submit" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-[11px] font-bold shadow cursor-pointer transition">
                            ✓ Aceptar & Entregar
                        </button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Alert Box: Comprobantes Pendientes en Mini Nube -->
    @if($pendingOrdersCount > 0)
    <div class="p-4 rounded-2xl bg-gradient-to-r from-rose-950/40 via-red-950/20 to-gray-900 border border-rose-500/30 shadow-xl flex items-center justify-between">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-rose-600/20 text-rose-400 border border-rose-500/40 flex items-center justify-center font-bold text-xl shrink-0 animate-pulse">
                <i data-lucide="inbox" class="w-6 h-6"></i>
            </div>
            <div>
                <h3 class="text-base font-bold text-white">Tienes {{ $pendingOrdersCount }} comprobante(s) pendiente(s) de auditoría en la Mini Nube</h3>
                <p class="text-xs text-gray-400">Los clientes subieron transferencias desde el catálogo. Audita y aprueba para asignar credenciales y auto-eliminar el comprobante.</p>
            </div>
        </div>
        <a href="{{ route('treasury.index', ['status' => 'pending']) }}" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-semibold text-xs transition-all shadow-lg shadow-rose-600/30 shrink-0 flex items-center gap-2">
            <span>Auditar Ahora</span>
            <i data-lucide="arrow-right" class="w-4 h-4"></i>
        </a>
    </div>
    @endif

    <!-- Stock por Plataforma de Streaming -->
    <div class="p-5 rounded-2xl bg-[#111827] border border-gray-800 shadow-lg">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <i data-lucide="film" class="w-5 h-5 text-red-500"></i>
                <h3 class="text-sm font-bold text-white uppercase tracking-wider">Disponibilidad de Stock por Plataforma</h3>
            </div>
            <a href="{{ route('accounts.index') }}" class="text-xs text-red-400 hover:text-red-300 font-medium flex items-center gap-1">
                Administrar Cuentas <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
            </a>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            @foreach($servicesStock as $s)
            <div class="p-3.5 rounded-xl bg-[#0b0f19] border border-gray-800 hover:border-gray-700 transition-all text-center flex flex-col justify-between" x-data="{ openStock: false }">
                <div>
                    <div class="w-8 h-8 mx-auto rounded-lg flex items-center justify-center font-bold text-xs text-white shadow-md mb-2" style="background-color: {{ $s->color_hex }}">
                        {{ substr($s->name, 0, 2) }}
                    </div>
                    <div class="text-xs font-bold text-white truncate">{{ $s->name }}</div>
                    <div class="my-1.5">
                        <span class="text-[9px] font-bold px-2 py-0.5 rounded-full border font-mono {{ $s->available_profiles > 0 ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30' : 'bg-rose-500/20 text-rose-400 border-rose-500/30' }}">
                            {{ $s->available_profiles > 0 ? '🟢 Con Stock' : '🔴 Agotado' }}
                        </span>
                    </div>
                </div>

                <div class="pt-1">
                    <button type="button" @click="openStock = !openStock" class="w-full py-1 px-1.5 rounded-lg bg-gray-900 hover:bg-gray-800 text-[10px] text-sky-400 hover:text-sky-300 font-bold border border-gray-800 flex items-center justify-center gap-1 transition cursor-pointer">
                        <span x-text="openStock ? 'Cerrar' : 'Ver Libres'"></span>
                        <i data-lucide="chevron-down" class="w-3 h-3 transition-transform" :class="openStock ? 'rotate-180 text-sky-400' : ''"></i>
                    </button>

                    <div x-show="openStock" x-cloak x-collapse class="mt-2 pt-1 border-t border-gray-800">
                        <span class="text-base font-black font-mono {{ $s->available_profiles > 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                            {{ $s->available_profiles }}
                        </span>
                        <span class="text-[10px] text-gray-400 block">pantallas libres</span>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- 7-Day Sales Chart & Action Hub -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Sales Chart -->
        <div class="lg:col-span-2 p-5 rounded-2xl bg-[#111827] border border-gray-800 shadow-lg">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <i data-lucide="trending-up" class="w-5 h-5 text-emerald-400"></i>
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider">Ventas de los Últimos 7 Días ($)</h3>
                </div>
                <span class="text-xs text-gray-400">Ingresos diarios</span>
            </div>
            <div class="h-64 relative">
                <canvas id="salesChart"></canvas>
            </div>
        </div>

        <!-- Acciones Rápidas & Dock -->
        <div class="p-5 rounded-2xl bg-[#111827] border border-gray-800 shadow-lg flex flex-col justify-between">
            <div>
                <h3 class="text-sm font-bold text-white uppercase tracking-wider mb-4 flex items-center gap-2">
                    <i data-lucide="zap" class="w-4 h-4 text-amber-400"></i>
                    <span>Operaciones Rápidas</span>
                </h3>
                <div class="space-y-2.5">
                    <a href="{{ route('sales.index') }}" class="w-full flex items-center justify-between p-3 rounded-xl bg-red-600/10 hover:bg-red-600/20 border border-red-500/30 text-red-400 font-semibold text-xs transition-all">
                        <div class="flex items-center gap-2.5">
                            <i data-lucide="plus-circle" class="w-4 h-4"></i>
                            <span>Nueva Venta de Cuenta</span>
                        </div>
                        <i data-lucide="chevron-right" class="w-4 h-4"></i>
                    </a>

                    <a href="{{ route('accounts.index') }}" class="w-full flex items-center justify-between p-3 rounded-xl bg-blue-600/10 hover:bg-blue-600/20 border border-blue-500/30 text-blue-400 font-semibold text-xs transition-all">
                        <div class="flex items-center gap-2.5">
                            <i data-lucide="server" class="w-4 h-4"></i>
                            <span>Cargar Stock / Cuentas Maestras</span>
                        </div>
                        <i data-lucide="chevron-right" class="w-4 h-4"></i>
                    </a>

                    <a href="{{ route('treasury.index') }}" class="w-full flex items-center justify-between p-3 rounded-xl bg-emerald-600/10 hover:bg-emerald-600/20 border border-emerald-500/30 text-emerald-400 font-semibold text-xs transition-all">
                        <div class="flex items-center gap-2.5">
                            <i data-lucide="credit-card" class="w-4 h-4"></i>
                            <span>Tesorería & Comprobantes</span>
                        </div>
                        <i data-lucide="chevron-right" class="w-4 h-4"></i>
                    </a>

                    <a href="{{ route('sales.export') }}" class="w-full flex items-center justify-between p-3 rounded-xl bg-purple-600/10 hover:bg-purple-600/20 border border-purple-500/30 text-purple-400 font-semibold text-xs transition-all">
                        <div class="flex items-center gap-2.5">
                            <i data-lucide="download" class="w-4 h-4"></i>
                            <span>Exportar Base de Clientes (Excel)</span>
                        </div>
                        <i data-lucide="chevron-right" class="w-4 h-4"></i>
                    </a>
                </div>
            </div>

            <div class="mt-4 pt-3 border-t border-gray-800 text-xs text-gray-400 flex items-center justify-between">
                <span>Total Clientes: <strong class="text-white">{{ $totalCustomersCount }}</strong></span>
                <a href="{{ route('customers.index') }}" class="text-red-400 hover:underline">Ver Agenda</a>
            </div>
        </div>
    </div>

    <!-- Tablas: Cuentas por Vencer (Alerta) & Ventas Recientes -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Cuentas por Vencer (Alertas de Cobro) -->
        <div class="p-5 rounded-2xl bg-[#111827] border border-gray-800 shadow-lg">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <i data-lucide="alert-circle" class="w-5 h-5 text-amber-400"></i>
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider">Por Vencer en <= 3 Días</h3>
                </div>
                <span class="text-xs px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-400 border border-amber-500/30 font-bold">
                    {{ $expiringSales->count() }}
                </span>
            </div>

            @if($expiringSales->isEmpty())
            <div class="text-center py-8 text-gray-400 text-xs">
                <i data-lucide="check-check" class="w-8 h-8 text-emerald-400 mx-auto mb-2 opacity-50"></i>
                No hay cuentas que venzan en los próximos 3 días. ¡Todo al día!
            </div>
            @else
            <div class="space-y-2.5">
                @foreach($expiringSales as $s)
                <div class="p-3 rounded-xl bg-[#0b0f19] border border-amber-500/20 flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-white">{{ $s->customer->name }}</span>
                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-gray-800 text-gray-300 font-medium">
                                {{ $s->service->name }}
                            </span>
                        </div>
                        <div class="text-[11px] text-gray-400 mt-0.5">
                            Vence: <strong class="text-amber-400">{{ $s->end_date->format('d/m/Y') }}</strong>
                            ({{ $s->days_remaining == 0 ? '¡Hoy!' : 'en ' . $s->days_remaining . ' días' }})
                        </div>
                    </div>
                    @if($s->customer->clean_phone)
                    <a href="https://wa.me/{{ $s->customer->clean_phone }}?text={{ urlencode($s->whats_app_reminder_message) }}" target="_blank" class="px-2.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-xs flex items-center gap-1.5 transition-all shadow-md shadow-emerald-600/20">
                        <i data-lucide="message-circle" class="w-3.5 h-3.5"></i>
                        <span>Avisar Cobro</span>
                    </a>
                    @endif
                </div>
                @endforeach
            </div>
            @endif
        </div>

        <!-- Ventas Recientes -->
        <div class="p-5 rounded-2xl bg-[#111827] border border-gray-800 shadow-lg">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <i data-lucide="history" class="w-5 h-5 text-blue-400"></i>
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider">Últimas Ventas Realizadas</h3>
                </div>
                <a href="{{ route('sales.index') }}" class="text-xs text-blue-400 hover:text-blue-300 font-medium">Ver Todas</a>
            </div>

            <div class="space-y-2.5">
                @foreach($recentSales as $sale)
                <div class="p-3 rounded-xl bg-[#0b0f19] border border-gray-800 flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-white">{{ $sale->sale_code }}</span>
                            <span class="text-[10px] px-1.5 py-0.5 rounded text-white" style="background-color: {{ $sale->service->color_hex ?? '#333' }}">
                                {{ $sale->service->name ?? 'Streaming' }}
                            </span>
                        </div>
                        <div class="text-[11px] text-gray-400 mt-0.5">
                            Cliente: <strong class="text-gray-300">{{ $sale->customer->name }}</strong> · Vence: {{ $sale->end_date->format('d/m/Y') }}
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-xs font-black text-emerald-400">${{ number_format($sale->sale_price, 2) }}</div>
                        <span class="text-[10px] px-1.5 py-0.5 rounded-full {{ $sale->calculated_status === 'active' ? 'bg-emerald-500/20 text-emerald-400' : ($sale->calculated_status === 'expiring_soon' ? 'bg-amber-500/20 text-amber-400' : 'bg-rose-500/20 text-rose-400') }} font-bold">
                            {{ strtoupper($sale->calculated_status) }}
                        </span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('salesChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: @json($chartLabels),
                datasets: [{
                    label: 'Ventas ($)',
                    data: @json($chartValues),
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.35,
                    pointBackgroundColor: '#ef4444',
                    pointRadius: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        grid: { color: 'rgba(255, 255, 255, 0.05)' },
                        ticks: { color: '#9ca3af', font: { size: 11 } }
                    },
                    y: {
                        grid: { color: 'rgba(255, 255, 255, 0.05)' },
                        ticks: {
                            color: '#9ca3af',
                            font: { size: 11 },
                            callback: function(val) { return '$' + val; }
                        }
                    }
                }
            }
        });
    });
</script>
@endpush
@endsection
