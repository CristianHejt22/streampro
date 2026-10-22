@extends('layouts.app')

@section('title', 'Control de Ventas de Cuentas')
@section('page_title', 'Control de Ventas & Credenciales')
@section('page_subtitle', 'Gestión de cuentas streaming vendidas, usuario, contraseña, perfiles, PINs y vencimientos')

@section('content')
<div class="space-y-6" x-data="{
    newSaleModal: false,
    viewCredsModal: false,
    renewModal: false,
    warrantyModal: false,
    providerReviewModal: false,
    selectedRenewal: null,
    selectedSale: null,
    showPassword: false,
    copyFeedback: false,
    copyText(text) {
        navigator.clipboard.writeText(text);
        this.copyFeedback = true;
        setTimeout(() => this.copyFeedback = false, 2000);
    }
}">

    <!-- Top Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <!-- Status Tabs / Filter Pills -->
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('sales.index') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all {{ !request('status') ? 'bg-red-600 text-white shadow-lg shadow-red-600/30' : 'bg-gray-900 border border-gray-800 text-gray-400 hover:text-white' }}">
                Todas las Ventas
            </a>
            <a href="{{ route('sales.index', ['status' => 'active']) }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all {{ request('status') === 'active' ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/30' : 'bg-gray-900 border border-gray-800 text-emerald-400 hover:bg-emerald-500/10' }}">
                Vigentes ({{ $countActive }})
            </a>
            <a href="{{ route('sales.index', ['status' => 'expiring_soon']) }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all {{ request('status') === 'expiring_soon' ? 'bg-amber-600 text-white shadow-lg shadow-amber-600/30' : 'bg-gray-900 border border-gray-800 text-amber-400 hover:bg-amber-500/10' }}">
                Por Vencer en <= 3 Días ({{ $countExpiring }})
            </a>
            <a href="{{ route('sales.index', ['status' => 'expired']) }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all {{ request('status') === 'expired' ? 'bg-rose-600 text-white shadow-lg shadow-rose-600/30' : 'bg-gray-900 border border-gray-800 text-rose-400 hover:bg-rose-500/10' }}">
                Vencidas ({{ $countExpired }})
            </a>
        </div>

        <!-- Buttons -->
        <div class="flex items-center gap-2.5">
            <a href="{{ route('sales.export') }}" class="px-3.5 py-2 rounded-xl bg-gray-900 border border-gray-800 hover:bg-gray-800 text-gray-300 font-semibold text-xs flex items-center gap-2 transition-all">
                <i data-lucide="download" class="w-4 h-4"></i>
                <span class="hidden sm:inline">Exportar Excel</span>
            </a>

            <button @click="newSaleModal = true" class="px-4 py-2 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold text-xs flex items-center gap-2 transition-all shadow-lg shadow-red-600/30">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                <span>Nueva Venta</span>
            </button>
        </div>
    </div>

    <!-- Filters Search Bar -->
    <div class="p-4 rounded-2xl bg-[#111827] border border-gray-800 shadow-md">
        <form method="GET" action="{{ route('sales.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
            @if(request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif

            <!-- Buscador general -->
            <div>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar cliente, WhatsApp, código..." class="w-full px-3.5 py-2 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white placeholder-gray-400 focus:outline-none focus:border-red-500">
            </div>

            <!-- Filtro Servicio -->
            <div>
                <select name="service_id" onchange="this.form.submit()" class="w-full px-3.5 py-2 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white focus:outline-none focus:border-red-500">
                    <option value="">Todas las plataformas</option>
                    @foreach($services as $serv)
                        <option value="{{ $serv->id }}" {{ request('service_id') == $serv->id ? 'selected' : '' }}>
                            {{ $serv->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Filtro Vendedor (Admin) -->
            @if(Auth::user()->isAdmin() && $sellers->isNotEmpty())
            <div>
                <select name="seller_id" onchange="this.form.submit()" class="w-full px-3.5 py-2 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white focus:outline-none focus:border-red-500">
                    <option value="">Todos los vendedores</option>
                    <option value="{{ Auth::id() }}" {{ request('seller_id') == Auth::id() ? 'selected' : '' }}>Solo mis ventas (Admin)</option>
                    @foreach($sellers as $sel)
                        <option value="{{ $sel->id }}" {{ request('seller_id') == $sel->id ? 'selected' : '' }}>
                            {{ $sel->name }} (Revendedor)
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            <div class="flex items-center gap-2">
                <button type="submit" class="px-4 py-2 rounded-xl bg-gray-800 hover:bg-gray-700 text-white text-xs font-semibold transition-all">
                    Filtrar
                </button>
                @if(request()->anyFilled(['search', 'service_id', 'seller_id', 'status']))
                <a href="{{ route('sales.index') }}" class="px-3 py-2 rounded-xl text-xs text-gray-400 hover:text-white">
                    Limpiar
                </a>
                @endif
            </div>
        </form>
    </div>

    <!-- SECCIÓN DE SOLICITUDES DE RENOVACIÓN DESDE BÓVEDA -->
    @if(isset($pendingRenewals) && $pendingRenewals->isNotEmpty())
    <div class="p-5 rounded-2xl bg-gradient-to-r from-cyan-950/60 via-[#111827] to-[#111827] border-2 border-cyan-500/40 shadow-xl space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-gray-800 pb-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-cyan-500/20 text-cyan-400 border border-cyan-500/40 flex items-center justify-center font-bold text-lg animate-pulse">
                    <i data-lucide="refresh-cw" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-base font-black text-white flex items-center gap-2">
                        <span>Solicitudes de Renovación desde Bóveda de Clientes</span>
                        <span class="text-xs px-2.5 py-0.5 rounded-full bg-cyan-500/20 text-cyan-300 border border-cyan-500/30 font-mono font-bold">
                            {{ $pendingRenewals->count() }} Pendiente(s)
                        </span>
                    </h3>
                    <p class="text-xs text-gray-400">Solicitudes enviadas directamente por clientes desde su Bóveda. Llegan a tu panel y puedes gestionarlas directamente o solicitar revisión a tu proveedor.</p>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="text-[11px] text-gray-400 uppercase bg-gray-900/80 border-b border-gray-800">
                    <tr>
                        <th class="px-4 py-3">Cliente</th>
                        <th class="px-4 py-3">Servicio / Cuenta</th>
                        <th class="px-4 py-3">Duración</th>
                        <th class="px-4 py-3">Monto & Método</th>
                        <th class="px-4 py-3">Comprobante / Bóveda</th>
                        <th class="px-4 py-3">Estado / Derivación</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800/80">
                    @foreach($pendingRenewals as $req)
                    @php
                        $rSale = $req->sale;
                        $rSrv = $req->service ?: ($rSale ? $rSale->service : null);
                        $rCustomer = $req->customer ?: ($rSale ? $rSale->customer : null);
                        $isOwner = $req->user_id === Auth::id();
                        $isProviderViewing = (Auth::user()->isSuperReseller() || Auth::user()->isAdmin()) && !$isOwner;
                    @endphp
                    <tr class="hover:bg-gray-800/40 transition-colors">
                        <td class="px-4 py-3 font-semibold text-white">
                            <div class="flex items-center gap-2">
                                <span class="w-7 h-7 rounded-lg bg-cyan-500/10 text-cyan-400 border border-cyan-500/20 flex items-center justify-center font-bold text-xs">
                                    {{ strtoupper(substr($rCustomer ? $rCustomer->name : 'CLI', 0, 2)) }}
                                </span>
                                <div>
                                    <div class="font-bold">{{ $rCustomer ? $rCustomer->name : 'Cliente' }}</div>
                                    <div class="text-[10px] text-gray-400 font-mono">{{ $rCustomer ? $rCustomer->phone : '' }}</div>
                                    @if($isProviderViewing && $req->user)
                                    <div class="text-[10px] text-amber-300 font-mono mt-0.5">Revendedor: {{ $req->user->name }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-bold text-cyan-300">{{ $rSrv ? $rSrv->name : 'Streaming' }}</div>
                            <div class="text-[10px] text-gray-400 font-mono">{{ $rSale ? $rSale->sale_code : '' }} ({{ $rSale ? $rSale->profile_name_snapshot : 'Perfil' }})</div>
                        </td>
                        <td class="px-4 py-3 font-mono">
                            <span class="px-2 py-0.5 rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-bold">
                                {{ $req->months_duration }} Mes(es)
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-black text-sm text-emerald-400 font-mono">${{ number_format($req->amount, 2) }}</div>
                            <div class="text-[10px] text-gray-400 uppercase font-bold">{{ str_replace('_', ' ', $req->payment_method) }}</div>
                        </td>
                        <td class="px-4 py-3 max-w-xs">
                            @if($req->receipt_path)
                            <a href="{{ asset('storage/' . $req->receipt_path) }}" target="_blank" class="inline-flex items-center gap-1 text-[11px] font-bold text-sky-400 hover:text-sky-300 underline mb-1">
                                <i data-lucide="image" class="w-3.5 h-3.5"></i>
                                <span>Ver Comprobante</span>
                            </a>
                            @else
                            <span class="text-[10px] text-gray-500 italic block">Sin comprobante</span>
                            @endif
                            @if($req->customer_notes)
                            <p class="text-[11px] text-gray-300 truncate" title="{{ $req->customer_notes }}">"{{ $req->customer_notes }}"</p>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if($req->review_requested_to_provider)
                                @if($isOwner)
                                    <div class="space-y-1">
                                        <span class="px-2 py-0.5 rounded-lg bg-amber-500/20 text-amber-300 border border-amber-500/30 text-[10px] font-bold inline-flex items-center gap-1">
                                            <i data-lucide="clock" class="w-3 h-3"></i>
                                            <span>En Revisión con Proveedor</span>
                                        </span>
                                        @if($req->provider)
                                            <div class="text-[10px] text-gray-400">Proveedor: <strong class="text-amber-200">{{ $req->provider->name }}</strong></div>
                                        @endif
                                        @if($req->provider_review_notes)
                                            <div class="text-[10px] text-gray-400 italic truncate max-w-[180px]" title="{{ $req->provider_review_notes }}">"{{ $req->provider_review_notes }}"</div>
                                        @endif
                                    </div>
                                @else
                                    <div class="space-y-1">
                                        <span class="px-2 py-0.5 rounded-lg bg-cyan-500/20 text-cyan-300 border border-cyan-500/30 text-[10px] font-bold inline-flex items-center gap-1">
                                            <i data-lucide="bell" class="w-3 h-3"></i>
                                            <span>Revisión Solicitada</span>
                                        </span>
                                        <div class="text-[10px] text-gray-400">Revendedor: <strong class="text-cyan-200">{{ optional($req->user)->name }}</strong></div>
                                        @if($req->provider_review_notes)
                                            <div class="text-[10px] text-gray-300 italic truncate max-w-[200px]" title="{{ $req->provider_review_notes }}">"{{ $req->provider_review_notes }}"</div>
                                        @endif
                                    </div>
                                @endif
                            @else
                                <span class="px-2 py-0.5 rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-[10px] font-bold inline-flex items-center gap-1">
                                    <i data-lucide="inbox" class="w-3 h-3"></i>
                                    <span>Recibido en tu Panel</span>
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-1.5 flex-wrap">
                                <!-- Botón Solicitar Revisión al Proveedor (Si es revendedor patrocinado) -->
                                @if($isOwner && (Auth::user()->parent_id || Auth::user()->parent) && !$req->review_requested_to_provider)
                                <button type="button" 
                                    @click="selectedRenewal = {
                                        id: {{ $req->id }},
                                        customer: '{{ addslashes($rCustomer ? $rCustomer->name : 'Cliente') }}',
                                        service: '{{ addslashes($rSrv ? $rSrv->name : 'Streaming') }}',
                                        amount: '{{ number_format($req->amount, 2) }}',
                                        provider_name: '{{ addslashes(Auth::user()->parent ? Auth::user()->parent->name : 'Proveedor Patrocinador') }}'
                                    }; providerReviewModal = true;"
                                    class="px-2.5 py-1.5 rounded-xl bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 font-bold text-xs flex items-center gap-1 border border-amber-500/30 transition cursor-pointer" 
                                    title="Solicitar revisión y soporte a mi proveedor patrocinador">
                                    <i data-lucide="share-2" class="w-3.5 h-3.5"></i>
                                    <span class="hidden md:inline">Solicitar al Proveedor</span>
                                </button>
                                @endif

                                <form method="POST" action="{{ route('sales.renewals.approve', $req) }}" onsubmit="return confirm('¿Aprobar y extender la cuenta por {{ $req->months_duration }} mes(es)?');">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs flex items-center gap-1 shadow cursor-pointer" title="Aprobar y extender vigencia">
                                        <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                        <span>Aprobar</span>
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('sales.renewals.reject', $req) }}" onsubmit="return confirm('¿Rechazar esta solicitud de renovación?');">
                                    @csrf
                                    <button type="submit" class="px-2.5 py-1.5 rounded-xl bg-rose-500/20 hover:bg-rose-500/30 text-rose-400 font-bold text-xs flex items-center gap-1 border border-rose-500/30 cursor-pointer" title="Rechazar">
                                        <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                        <span>Rechazar</span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Sales Table -->
    <div class="rounded-2xl bg-[#111827] border border-gray-800 shadow-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-gray-800 text-[11px] font-bold text-gray-400 uppercase tracking-wider bg-[#0d1322]">
                        <th class="py-3.5 px-4">Venta / Código</th>
                        <th class="py-3.5 px-4">Plataforma & Perfil</th>
                        <th class="py-3.5 px-4">Cliente & Contacto</th>
                        <th class="py-3.5 px-4">Credenciales</th>
                        <th class="py-3.5 px-4">Vigencia / Vencimiento</th>
                        <th class="py-3.5 px-4 text-right">Precio / Costo</th>
                        <th class="py-3.5 px-4 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800/60 text-xs text-gray-200">
                    @forelse($sales as $s)
                    @php
                        $daysLeft = $s->days_remaining;
                        $calcStatus = $s->calculated_status;
                    @endphp
                    <tr class="hover:bg-gray-800/30 transition-colors">
                        <!-- Código -->
                        <td class="py-3 px-4">
                            <span class="font-mono font-bold text-white text-xs block">{{ $s->sale_code }}</span>
                            <span class="text-[10px] text-gray-400">{{ $s->start_date->format('d/m/Y') }}</span>
                            @if(Auth::user()->isAdmin() && $s->user && $s->user_id !== Auth::id())
                            <span class="text-[10px] block text-cyan-400">Por: {{ $s->user->name }}</span>
                            @endif
                        </td>

                        <!-- Plataforma & Modalidad -->
                        <td class="py-3 px-4">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: {{ $s->service->color_hex ?? '#E50914' }}"></span>
                                <span class="font-bold text-white">{{ $s->service->name }}</span>
                            </div>
                            <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                @if($s->streamingAccount && $s->streamingAccount->account_name)
                                <span class="text-[9px] px-1.5 py-0.5 rounded bg-amber-500/10 text-amber-300 border border-amber-500/20 font-sans font-semibold" title="Nombre de Cuenta / Lote">
                                    {{ $s->streamingAccount->account_name }}
                                </span>
                                @endif
                                <span class="text-[10px] px-2 py-0.5 rounded bg-gray-800 text-gray-300 font-medium">
                                    {{ $s->profile_name_snapshot ?: 'Cuenta Completa' }}
                                </span>
                                @if($s->profile_pin_snapshot)
                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-amber-500/10 text-amber-400 border border-amber-500/30 font-mono font-bold">
                                    PIN: {{ $s->profile_pin_snapshot }}
                                </span>
                                @endif
                            </div>
                        </td>

                        <!-- Cliente -->
                        <td class="py-3 px-4">
                            <div class="font-bold text-white">{{ $s->customer->name }}</div>
                            @if($s->customer->phone)
                            <a href="https://wa.me/{{ $s->customer->clean_phone }}" target="_blank" class="text-[11px] text-emerald-400 hover:text-emerald-300 flex items-center gap-1 mt-0.5">
                                <i data-lucide="message-circle" class="w-3 h-3"></i>
                                <span>{{ $s->customer->phone }}</span>
                            </a>
                            @endif
                        </td>

                        <!-- Credenciales Snapshot -->
                        <td class="py-3 px-4">
                            <button @click="selectedSale = {{ json_encode($s) }}; viewCredsModal = true;" class="px-2.5 py-1.5 rounded-lg bg-gray-800 hover:bg-gray-700 text-gray-200 border border-gray-700 font-semibold text-[11px] flex items-center gap-1.5 transition-all">
                                <i data-lucide="eye" class="w-3.5 h-3.5 text-cyan-400"></i>
                                <span>Ver Credenciales</span>
                            </button>
                        </td>

                        <!-- Vigencia -->
                        <td class="py-3 px-4">
                            <div class="font-semibold text-white">{{ $s->end_date->format('d/m/Y') }} ({{ $s->months_duration ?: 1 }}m)</div>
                            <div class="mt-0.5">
                                @if(!$s->is_consignment_paid)
                                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/40 font-bold block w-max animate-pulse">
                                        🏦 Transferencia Pendiente
                                    </span>
                                    <span class="text-[9px] text-amber-400 font-mono block mt-0.5">🔒 Retenida hasta Aceptar</span>
                                    @if($s->payment_proof)
                                    <a href="{{ asset('storage/' . $s->payment_proof) }}" target="_blank" class="inline-flex items-center gap-1 text-[10px] text-sky-400 hover:text-sky-300 underline mt-0.5">
                                        <i data-lucide="image" class="w-3 h-3"></i>
                                        <span>Comprobante</span>
                                    </a>
                                    @endif
                                    @if(Auth::user()->isAdmin() || (Auth::user()->isSuperReseller() && optional($s->user)->parent_id === Auth::id()) || $s->user_id === Auth::id())
                                    <form method="POST" action="{{ route('sales.accept_transfer', $s) }}" class="inline mt-1 block">
                                        @csrf
                                        <button type="submit" onclick="return confirm('¿Confirmas que la transferencia fue aceptada para entregar la(s) cuenta(s) al cliente?');" class="text-[10px] text-emerald-400 hover:text-emerald-300 font-bold bg-emerald-500/10 hover:bg-emerald-500/20 px-2 py-1 rounded-lg border border-emerald-500/30 block cursor-pointer transition">
                                            ✓ Aceptar Transferencia & Entregar
                                        </button>
                                    </form>
                                    @endif
                                @elseif($calcStatus === 'active')
                                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 font-bold">
                                        Vence en {{ $daysLeft }} días
                                    </span>
                                @elseif($calcStatus === 'expiring_soon')
                                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-400 border border-amber-500/30 font-bold animate-pulse">
                                        {{ $daysLeft == 0 ? '¡VENCE HOY!' : '¡Vence en ' . $daysLeft . ' días!' }}
                                    </span>
                                @elseif($calcStatus === 'expired')
                                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-rose-500/20 text-rose-400 border border-rose-500/30 font-bold">
                                        ¡Vencida hace {{ abs($daysLeft) }} d!
                                    </span>
                                @else
                                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-blue-500/20 text-blue-400 font-bold">
                                        {{ strtoupper($calcStatus) }}
                                    </span>
                                @endif
                            </div>
                        </td>

                        <!-- Precio -->
                        <td class="py-3 px-4 text-right">
                            <div class="font-black text-emerald-400 text-xs">${{ number_format($s->sale_price, 2) }}</div>
                            <div class="text-[10px] text-gray-400">Ganancia: +${{ number_format($s->profit, 2) }}</div>
                        </td>

                        <!-- Acciones -->
                        <td class="py-3 px-4 text-center">
                            <div class="flex items-center justify-center gap-1.5">
                                <!-- Enviar WhatsApp Ficha -->
                                @if($s->customer->clean_phone)
                                    @if(!$s->canDeliverCredentials())
                                    <span title="Credenciales Retenidas: No se entregarán sin liquidar el costo en billetera o sin pago del proveedor" class="p-2 rounded-lg bg-gray-800 text-gray-500 cursor-not-allowed">
                                        <i data-lucide="lock" class="w-3.5 h-3.5"></i>
                                    </span>
                                    @else
                                    <a href="https://wa.me/{{ $s->customer->clean_phone }}?text={{ urlencode($s->whats_app_delivery_message) }}" target="_blank" title="Enviar credenciales completas por WhatsApp" class="p-2 rounded-lg bg-emerald-600/20 text-emerald-400 hover:bg-emerald-600 hover:text-white border border-emerald-500/30 transition-all">
                                        <i data-lucide="send" class="w-3.5 h-3.5"></i>
                                    </a>
                                    @endif
                                @endif

                                <!-- Recordatorio de Cobro (si vence pronto o venció) -->
                                @if($calcStatus === 'expiring_soon' || $calcStatus === 'expired')
                                    @if($s->customer->clean_phone)
                                    <a href="https://wa.me/{{ $s->customer->clean_phone }}?text={{ urlencode($s->whats_app_reminder_message) }}" target="_blank" title="Enviar aviso de cobro por WhatsApp" class="p-2 rounded-lg bg-amber-500/20 text-amber-400 hover:bg-amber-500 hover:text-black border border-amber-500/30 transition-all">
                                        <i data-lucide="bell-ring" class="w-3.5 h-3.5"></i>
                                    </a>
                                    @endif
                                @endif

                                <!-- Renovar Venta -->
                                <button @click="selectedSale = {{ json_encode($s) }}; renewModal = true;" title="Renovar +30 días" class="p-2 rounded-lg bg-blue-600/20 text-blue-400 hover:bg-blue-600 hover:text-white border border-blue-500/30 transition-all">
                                    <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                                </button>

                                <!-- Garantía / Caída -->
                                @if(!$s->is_consignment_paid)
                                <span title="Transferencia Pendiente: Acepta o abona el pago para activar la garantía" class="p-2 rounded-lg bg-gray-800 text-gray-500 cursor-not-allowed">
                                    <i data-lucide="shield-alert" class="w-3.5 h-3.5"></i>
                                </span>
                                @else
                                <button @click="selectedSale = {{ json_encode($s) }}; warrantyModal = true;" title="Reportar caída o cambiar credenciales por garantía" class="p-2 rounded-lg bg-purple-600/20 text-purple-400 hover:bg-purple-600 hover:text-white border border-purple-500/30 transition-all">
                                    <i data-lucide="shield-alert" class="w-3.5 h-3.5"></i>
                                </button>
                                @endif

                                <!-- Eliminar / Cancelar -->
                                <form method="POST" action="{{ route('sales.destroy', $s) }}" onsubmit="return confirm('¿Seguro que deseas eliminar esta venta? El perfil se liberará de nuevo al stock.');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" title="Cancelar venta y liberar perfil" class="p-2 rounded-lg text-gray-400 hover:text-rose-400 hover:bg-rose-500/10 transition-all">
                                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-12 text-center text-gray-400">
                            <i data-lucide="film" class="w-12 h-12 text-gray-600 mx-auto mb-3"></i>
                            <div class="text-sm font-semibold text-gray-300">No se encontraron ventas de cuentas registradas.</div>
                            <p class="text-xs text-gray-400 mt-1">Haz clic en "Nueva Venta" para emitir la primera cuenta de streaming.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($sales->hasPages())
        <div class="p-4 border-t border-gray-800 bg-[#0d1322]">
            {{ $sales->links() }}
        </div>
        @endif
    </div>

    <!-- ========================================== -->
    <!-- MODAL 1: NUEVA VENTA DE CUENTA STREAMING -->
    <!-- ========================================== -->
    <div x-show="newSaleModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
        <div @click.away="newSaleModal = false" class="w-full max-w-xl bg-[#111827] border border-gray-800 rounded-3xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
            <div class="p-6 border-b border-gray-800 flex items-center justify-between bg-[#0e1424]">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-red-600/20 text-red-500 flex items-center justify-center font-bold">
                        <i data-lucide="key" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white">Nueva Venta de Cuenta Streaming</h3>
                        <p class="text-xs text-gray-400">Asigna automáticamente usuario, contraseña y perfil desde el stock</p>
                    </div>
                </div>
                <button @click="newSaleModal = false" class="text-gray-400 hover:text-white">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('sales.store') }}" enctype="multipart/form-data" class="p-6 overflow-y-auto space-y-4" x-data="{ payMethod: 'wallet' }">
                @csrf

                <!-- Selección de Plan y Plataforma -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-2">Seleccionar Plan / Plataforma *</label>
                    <select name="service_plan_id" required class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-sm text-white focus:outline-none focus:border-red-500 font-semibold">
                        <option value="">-- Elige qué cuenta vender --</option>
                        @foreach($availablePlans as $p)
                        <option value="{{ $p->id }}">
                            {{ $p->service->name }} · {{ $p->name }} (Público: ${{ number_format($p->price, 2) }} | Mayorista: ${{ number_format($p->reseller_price, 2) }})
                        </option>
                        @endforeach
                    </select>
                </div>

                <!-- Modo Cliente: Nuevo o Existente -->
                <div x-data="{ clientMode: 'new' }" class="pt-2">
                    <div class="flex items-center gap-4 mb-3">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="customer_mode" value="new" x-model="clientMode" class="text-red-600 focus:ring-red-500">
                            <span class="text-xs font-bold text-white">Nuevo Cliente</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="customer_mode" value="existing" x-model="clientMode" class="text-red-600 focus:ring-red-500">
                            <span class="text-xs font-bold text-white">Cliente Existente</span>
                        </label>
                    </div>

                    <!-- Campos Nuevo Cliente -->
                    <div x-show="clientMode === 'new'" class="space-y-3 p-4 rounded-2xl bg-[#0b0f19] border border-gray-800">
                        <div>
                            <label class="block text-xs text-gray-400 font-medium mb-1">Nombre Completo del Cliente *</label>
                            <input type="text" name="customer_name" placeholder="Ej: Carlos Alberto Fernández" class="w-full px-3 py-2 rounded-xl bg-gray-900 border border-gray-800 text-xs text-white focus:border-red-500">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs text-gray-400 font-medium mb-1">WhatsApp / Teléfono *</label>
                                <input type="text" name="customer_phone" placeholder="Ej: +5491144556677" class="w-full px-3 py-2 rounded-xl bg-gray-900 border border-gray-800 text-xs text-white focus:border-red-500">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-400 font-medium mb-1">Email (Opcional)</label>
                                <input type="email" name="customer_email" placeholder="cliente@gmail.com" class="w-full px-3 py-2 rounded-xl bg-gray-900 border border-gray-800 text-xs text-white focus:border-red-500">
                            </div>
                        </div>
                    </div>

                    <!-- Selección Cliente Existente -->
                    <div x-show="clientMode === 'existing'" class="p-4 rounded-2xl bg-[#0b0f19] border border-gray-800">
                        <label class="block text-xs text-gray-400 font-medium mb-1">Seleccionar Cliente de la Agenda</label>
                        <select name="customer_id" class="w-full px-3 py-2 rounded-xl bg-gray-900 border border-gray-800 text-xs text-white focus:border-red-500">
                            <option value="">-- Elige un cliente existente --</option>
                            @foreach($customersList as $cli)
                            <option value="{{ $cli->id }}">{{ $cli->name }} ({{ $cli->phone ?: 'Sin teléfono' }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Precio Personalizado y Notas -->
                <div class="grid grid-cols-2 gap-3 pt-2">
                    <div>
                        <label class="block text-xs text-gray-400 font-medium mb-1">Precio Cobrado ($) (Opcional)</label>
                        <input type="number" step="0.01" name="custom_price" placeholder="Por defecto según plan" class="w-full px-3 py-2 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white focus:border-red-500">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-400 font-medium mb-1">Notas Internas</label>
                        <input type="text" name="notes" placeholder="Ej: Pago en efectivo, amigo" class="w-full px-3 py-2 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white focus:border-red-500">
                    </div>
                </div>

                <!-- Duración y Forma de Pago (Sin Consignación) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                    <div>
                        <label class="block text-xs text-gray-400 font-bold uppercase mb-1">Duración (Meses) *</label>
                        <select name="months_duration" class="w-full px-3 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white">
                            <option value="1" selected>1 Mes (30 días)</option>
                            <option value="2">2 Meses (60 días)</option>
                            <option value="3">3 Meses (90 días)</option>
                            <option value="6">6 Meses (180 días)</option>
                            <option value="12">12 Meses (1 Año - 360 días)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-400 font-bold uppercase mb-1">Forma de Pago del Lote / Cuenta *</label>
                        <select name="payment_method" x-model="payMethod" class="w-full px-3 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white">
                            <option value="wallet">💳 Billetera (Entrega Automática)</option>
                            <option value="bank_transfer">🏦 Transferencia Bancaria (Aceptación Requerida)</option>
                        </select>
                    </div>
                </div>

                <!-- Campo de Comprobante si abona por Transferencia -->
                <div x-show="payMethod === 'bank_transfer'" x-collapse class="p-3.5 rounded-2xl bg-slate-900 border border-slate-800 space-y-1">
                    <label class="block text-xs font-bold text-sky-400 uppercase">Adjuntar Comprobante de Transferencia (Opcional)</label>
                    <input type="file" name="payment_proof" accept="image/*,application/pdf" class="w-full text-xs text-gray-400 file:mr-2 file:py-1 file:px-3 file:rounded-lg file:border-0 file:bg-sky-500/20 file:text-sky-300 file:font-bold">
                    <span class="text-[11px] text-gray-500 block">La transferencia será aceptada por tu proveedor para habilitar la entrega de la cuenta.</span>
                </div>

                <!-- Banner Informativo Oficial -->
                <div class="p-4 rounded-2xl bg-[#0b0f19] border border-cyan-500/30 text-xs space-y-2">
                    <div class="flex items-center gap-2 font-bold text-cyan-400">
                        <i data-lucide="shield-check" class="w-4 h-4 text-cyan-400"></i>
                        <span>Condiciones de Pago & Entrega:</span>
                    </div>
                    <ul class="space-y-1 text-[11px] text-gray-300">
                        <li class="flex items-start gap-2">
                            <span class="text-emerald-400 font-bold">✓</span>
                            <span><strong>Revendedor con saldo en billetera:</strong> Entrega 100% automática e inmediata de credenciales en la Bóveda del cliente.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-amber-400 font-bold">✓</span>
                            <span><strong>Abono por transferencia bancaria:</strong> La transferencia será aceptada por el proveedor o administración para liberar y entregar la(s) cuenta(s).</span>
                        </li>
                        <li class="flex items-start gap-2 text-rose-400 font-semibold border-t border-gray-800 pt-1.5">
                            <span class="font-bold">⚠️</span>
                            <span><strong>Sin consignación:</strong> Consignación de gracia (NO ES UNA OPCIÓN). No se entregan cuentas sin saldo en billetera o transferencia aceptada.</span>
                        </li>
                    </ul>
                </div>

                <div class="pt-4 border-t border-gray-800 flex items-center justify-end gap-3">
                    <button type="button" @click="newSaleModal = false" class="px-4 py-2.5 rounded-xl text-xs font-semibold text-gray-400 hover:text-white">
                        Cancelar
                    </button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold text-xs shadow-lg shadow-red-600/30">
                        Confirmar Venta y Asignar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL 2: VER CREDENCIALES (USUARIO / CLAVE / PIN) -->
    <!-- ========================================== -->
    <div x-show="viewCredsModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
        <div @click.away="viewCredsModal = false" class="w-full max-w-lg bg-[#111827] border border-gray-800 rounded-3xl shadow-2xl overflow-hidden flex flex-col">
            <div class="p-5 border-b border-gray-800 flex items-center justify-between bg-[#0e1424]">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-red-600/20 text-red-500 flex items-center justify-center">
                        <i data-lucide="key" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-white">Credenciales de Acceso Streaming</h3>
                        <p class="text-[11px] text-gray-400">Venta: <span class="font-mono text-cyan-400" x-text="selectedSale?.sale_code"></span></p>
                    </div>
                </div>
                <button @click="viewCredsModal = false" class="text-gray-400 hover:text-white">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <div class="p-6 space-y-4" x-show="selectedSale">
                <!-- Servicio y Perfil -->
                <div class="p-3.5 rounded-xl bg-[#0b0f19] border border-gray-800 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] text-gray-400 uppercase block font-semibold">Servicio</span>
                        <span class="text-sm font-bold text-white" x-text="selectedSale?.service?.name"></span>
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] text-gray-400 uppercase block font-semibold">Modalidad</span>
                        <span class="text-xs px-2 py-0.5 rounded bg-red-600/20 text-red-400 border border-red-500/30 font-bold" x-text="selectedSale?.profile_name_snapshot || 'Cuenta Completa'"></span>
                    </div>
                </div>

                <!-- Correo / Usuario -->
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-400 mb-1">Correo / Usuario de Acceso</label>
                    <div class="flex items-center gap-2">
                        <input type="text" readonly :value="selectedSale?.account_email_snapshot" class="flex-1 px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 font-mono text-xs text-white focus:outline-none">
                        <button @click="copyText(selectedSale?.account_email_snapshot)" class="px-3 py-2.5 rounded-xl bg-gray-800 hover:bg-gray-700 text-gray-200 text-xs font-semibold flex items-center gap-1">
                            <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                            <span>Copiar</span>
                        </button>
                    </div>
                </div>

                <!-- Contraseña -->
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-400 mb-1">Contraseña</label>
                    <div class="flex items-center gap-2">
                        <input :type="showPassword ? 'text' : 'password'" readonly :value="selectedSale?.account_password_snapshot" class="flex-1 px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 font-mono text-xs text-white focus:outline-none">
                        <button @click="showPassword = !showPassword" class="p-2.5 rounded-xl bg-gray-800 hover:bg-gray-700 text-gray-300">
                            <i data-lucide="eye" class="w-4 h-4" x-show="!showPassword"></i>
                            <i data-lucide="eye-off" class="w-4 h-4" x-show="showPassword"></i>
                        </button>
                        <button @click="copyText(selectedSale?.account_password_snapshot)" class="px-3 py-2.5 rounded-xl bg-gray-800 hover:bg-gray-700 text-gray-200 text-xs font-semibold flex items-center gap-1">
                            <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                            <span>Copiar</span>
                        </button>
                    </div>
                </div>

                <!-- Perfil y PIN -->
                <div class="grid grid-cols-2 gap-3" x-show="selectedSale?.profile_pin_snapshot">
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-400 mb-1">Perfil Asignado</label>
                        <div class="px-3.5 py-2 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs font-bold text-white" x-text="selectedSale?.profile_name_snapshot"></div>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-400 mb-1">PIN del Perfil</label>
                        <div class="flex items-center gap-2">
                            <div class="flex-1 px-3.5 py-2 rounded-xl bg-[#0b0f19] border border-amber-500/30 font-mono font-bold text-amber-400 text-sm" x-text="selectedSale?.profile_pin_snapshot"></div>
                            <button @click="copyText(selectedSale?.profile_pin_snapshot)" class="p-2 rounded-xl bg-gray-800 text-gray-300 hover:text-white">
                                <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Reglas de Uso -->
                <div x-show="selectedSale?.usage_rules_snapshot" class="p-3.5 rounded-xl bg-amber-950/20 border border-amber-500/20 text-xs text-amber-200/90">
                    <span class="font-bold block mb-1">⚠️ Reglas de uso para el cliente:</span>
                    <pre class="font-sans whitespace-pre-line text-[11px]" x-text="selectedSale?.usage_rules_snapshot"></pre>
                </div>

                <div x-show="copyFeedback" class="p-2 rounded-lg bg-emerald-500/20 border border-emerald-500/40 text-emerald-400 text-xs font-bold text-center">
                    ¡Copiado al portapapeles exitosamente!
                </div>

                <!-- Acciones Footer -->
                <div class="pt-3 border-t border-gray-800 flex items-center justify-between">
                    <button type="button" @click="viewCredsModal = false" class="text-xs text-gray-400 hover:text-white">
                        Cerrar
                    </button>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="copyText(selectedSale?.whats_app_delivery_message)" class="px-4 py-2.5 rounded-xl bg-gray-800 hover:bg-gray-700 text-white font-bold text-xs flex items-center gap-1.5 transition-all">
                            <i data-lucide="copy-check" class="w-4 h-4 text-emerald-400"></i>
                            <span>Copiar Mensaje WhatsApp</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL 3: RENOVAR VENTA (+30 DÍAS) -->
    <!-- ========================================== -->
    <div x-show="renewModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
        <div @click.away="renewModal = false" class="w-full max-w-md bg-[#111827] border border-gray-800 rounded-3xl shadow-2xl p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-blue-600/20 text-blue-400 flex items-center justify-center">
                    <i data-lucide="refresh-cw" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-white">Renovación de Cuenta</h3>
                    <p class="text-xs text-gray-400">Extiende la fecha de vigencia para el cliente</p>
                </div>
            </div>

            <form :action="'/sales/' + selectedSale?.id + '/renew'" method="POST" class="space-y-4">
                @csrf
                <div class="p-4 rounded-xl bg-[#0b0f19] border border-gray-800">
                    <div class="text-xs text-gray-400">Venta: <strong class="text-white" x-text="selectedSale?.sale_code"></strong></div>
                    <div class="text-xs text-gray-400 mt-1">Cliente: <strong class="text-white" x-text="selectedSale?.customer?.name"></strong></div>
                    <div class="text-xs text-gray-400 mt-1">Servicio: <strong class="text-cyan-400" x-text="selectedSale?.service?.name"></strong></div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Días a Extender</label>
                    <select name="days" class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-sm text-white focus:outline-none focus:border-blue-500 font-semibold">
                        <option value="30" selected>+30 días (1 Mes)</option>
                        <option value="60">+60 días (2 Meses)</option>
                        <option value="90">+90 días (3 Meses)</option>
                        <option value="180">+180 días (6 Meses)</option>
                        <option value="365">+365 días (1 Año)</option>
                    </select>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-800">
                    <button type="button" @click="renewModal = false" class="px-4 py-2 text-xs text-gray-400 hover:text-white">Cancelar</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs shadow-lg shadow-blue-600/30">
                        Confirmar Renovación
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL 4: GARANTÍA / REEMPLAZAR POR CAÍDA -->
    <!-- ========================================== -->
    <div x-show="warrantyModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
        <div @click.away="warrantyModal = false" class="w-full max-w-md bg-[#111827] border border-gray-800 rounded-3xl shadow-2xl p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-purple-600/20 text-purple-400 flex items-center justify-center">
                    <i data-lucide="shield-alert" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-white">Reposición por Garantía</h3>
                    <p class="text-xs text-gray-400">Reemplaza automáticamente las credenciales desde el stock</p>
                </div>
            </div>

            <form :action="'/sales/' + selectedSale?.id + '/warranty'" method="POST" class="space-y-4">
                @csrf
                <div class="p-4 rounded-xl bg-[#0b0f19] border border-gray-800">
                    <div class="text-xs text-gray-400">Venta: <strong class="text-white" x-text="selectedSale?.sale_code"></strong></div>
                    <div class="text-xs text-gray-400 mt-1">Servicio: <strong class="text-purple-400" x-text="selectedSale?.service?.name"></strong></div>
                    <div class="text-xs text-gray-400 mt-1">Cuenta actual: <strong class="font-mono text-gray-300" x-text="selectedSale?.account_email_snapshot"></strong></div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Motivo del Reclamo / Caída *</label>
                    <select name="issue_type" required class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white focus:border-purple-500">
                        <option value="password_changed">Contraseña incorrecta / Modificada</option>
                        <option value="account_disabled">Cuenta caída / Suspensión de membresía</option>
                        <option value="screen_occupied">Pantalla ocupada por otro usuario</option>
                        <option value="geo_blocked">Bloqueo geográfico / Hogar Netflix</option>
                        <option value="other">Otro problema técnico</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Detalle del Reclamo</label>
                    <textarea name="reason" rows="2" placeholder="Ej: Cliente informa que la clave no entra desde hoy..." required class="w-full px-3.5 py-2 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white focus:border-purple-500"></textarea>
                </div>

                <div class="p-3 rounded-xl bg-purple-950/20 border border-purple-500/20 text-purple-300 text-[11px]">
                    El sistema tomará automáticamente la siguiente pantalla disponible de este servicio en el stock, marcará la anterior en revisión y actualizará la ficha.
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-800">
                    <button type="button" @click="warrantyModal = false" class="px-4 py-2 text-xs text-gray-400 hover:text-white">Cancelar</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-lg shadow-purple-600/30">
                        Reemplazar y Asignar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL 5: SOLICITAR REVISIÓN AL PROVEEDOR PATROCINADOR -->
    <!-- ======================================================== -->
    <div x-show="providerReviewModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
        <div @click.away="providerReviewModal = false" class="w-full max-w-md bg-[#111827] border border-amber-500/30 rounded-3xl shadow-2xl p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-amber-500/20 text-amber-400 flex items-center justify-center border border-amber-500/30">
                    <i data-lucide="share-2" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-white">Solicitar Revisión al Proveedor</h3>
                    <p class="text-xs text-gray-400">Elevar renovación de Bóveda para revisión y soporte</p>
                </div>
            </div>

            <form :action="'/sales/renewal-requests/' + selectedRenewal?.id + '/request-provider-review'" method="POST" class="space-y-4">
                @csrf
                <div class="p-4 rounded-xl bg-[#0b0f19] border border-gray-800 space-y-1.5 text-xs">
                    <div class="text-gray-400">Cliente: <strong class="text-white" x-text="selectedRenewal?.customer"></strong></div>
                    <div class="text-gray-400">Servicio: <strong class="text-cyan-400" x-text="selectedRenewal?.service"></strong></div>
                    <div class="text-gray-400">Monto informado: <strong class="text-emerald-400 font-mono" x-text="'$' + selectedRenewal?.amount"></strong></div>
                    <div class="text-gray-400 pt-1 border-t border-gray-800">
                        Proveedor Patrocinador: <strong class="text-amber-300" x-text="selectedRenewal?.provider_name"></strong>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">
                        Mensaje / Observaciones para tu Proveedor
                    </label>
                    <textarea name="notes" rows="3" placeholder="Ej: Hola, el cliente envió comprobante pero solicito tu revisión o acreditación directa..." class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white focus:border-amber-500 focus:outline-none"></textarea>
                    <span class="text-[11px] text-gray-500 mt-1 block">El proveedor recibirá aviso en su panel y podrá revisar y aprobar la extensión.</span>
                </div>

                <div class="p-3 rounded-xl bg-amber-950/20 border border-amber-500/20 text-amber-300 text-[11px] leading-relaxed">
                    Al solicitar revisión, la renovación aparecerá en el panel de tu proveedor con registro de auditoría mutuo y el mensaje indicado.
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-800">
                    <button type="button" @click="providerReviewModal = false" class="px-4 py-2 text-xs text-gray-400 hover:text-white">Cancelar</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-amber-600 hover:bg-amber-500 text-white font-bold text-xs shadow-lg shadow-amber-600/30 flex items-center gap-1.5">
                        <i data-lucide="send" class="w-3.5 h-3.5"></i>
                        <span>Enviar Solicitud al Proveedor</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
