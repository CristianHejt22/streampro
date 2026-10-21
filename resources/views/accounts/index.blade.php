@extends('layouts.app')

@section('title', 'Inventario & Stock de Streaming')
@section('page_title', 'Inventario Mayorista & Contraseñas Maestras')
@section('page_subtitle', 'Gestión de cuentas oficiales, perfiles disponibles, perfiles vendidos y PINs de seguridad (Netflix, HBO, Disney)')

@section('content')
<div class="space-y-8" x-data="{
    newAccountModal: false,
    editAccountModal: false,
    privatePasswordModal: false,
    selectedAccount: null,
    totalProfiles: 5,
    showPasswords: {},
    editForm: {
        id: null,
        service_id: '',
        service_name: '',
        supplier_id: '',
        account_email: '',
        account_password: '',
        recovery_email: '',
        supplier_cost: 0,
        supplier_expiration_date: '',
        status: 'active',
        notes: '',
        sync_clients: true,
        showPassword: false,
        profiles: []
    },
    togglePassword(id) {
        this.showPasswords[id] = !this.showPasswords[id];
    },
    copyText(text, label) {
        navigator.clipboard.writeText(text);
        alert((label || 'Dato') + ' copiado al portapapeles: ' + text);
    },
    openPasswordModal(acc) {
        this.selectedAccount = acc;
        this.privatePasswordModal = true;
    },
    openEditModal(acc) {
        this.selectedAccount = acc;
        this.editForm = {
            id: acc.id,
            service_id: acc.service_id || '',
            service_name: acc.service ? acc.service.name : 'Streaming',
            supplier_id: acc.supplier_id || '',
            account_email: acc.account_email || '',
            account_password: acc.account_password || '',
            recovery_email: acc.recovery_email || '',
            supplier_cost: acc.supplier_cost ? parseFloat(acc.supplier_cost) : 0,
            supplier_expiration_date: acc.supplier_expiration_date ? (typeof acc.supplier_expiration_date === 'string' ? acc.supplier_expiration_date.substring(0, 10) : '') : '',
            status: acc.status || 'active',
            notes: acc.notes || '',
            sync_clients: true,
            showPassword: false,
            profiles: acc.profiles ? JSON.parse(JSON.stringify(acc.profiles)) : []
        };
        this.editAccountModal = true;
    },
    addProfileToEditForm() {
        let nextNum = this.editForm.profiles.length + 1;
        let randPin = Math.floor(1000 + Math.random() * 9000).toString();
        this.editForm.profiles.push({
            id: 'new_' + Date.now(),
            is_new: true,
            profile_number: nextNum,
            profile_name: 'Pantalla ' + nextNum,
            pin: randPin,
            status: 'available'
        });
    }
}">

    <!-- ======================================================== -->
    <!-- PANEL DINÁMICO DE STOCK EN VIVO POR PLATAFORMA (STREAMPRO) -->
    <!-- ======================================================== -->
    <div class="space-y-4">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 bg-[#0f172a] p-4 rounded-2xl border border-slate-800">
            <div>
                <h3 class="text-sm font-black text-white flex items-center gap-2">
                    <span>📊</span>
                    <span>Disponibilidad de Stock por Plataforma (Tiempo Real)</span>
                </h3>
                <p class="text-xs text-slate-400">Capacidad total y perfiles libres listos para entrega inmediata a clientes y revendedores.</p>
            </div>
            <div class="text-xs font-mono text-slate-400">
                Total Perfiles Libres en Stock: <strong class="text-emerald-400 font-black text-sm">{{ $totalAvailableProfiles }}</strong>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach($platformStockSummary as $srvId => $stk)
            @php
                $s = $stk['service'];
            @endphp
            <div class="bg-[#0f172a] p-4 rounded-2xl border border-slate-800 shadow-lg flex flex-col justify-between space-y-3 hover:border-slate-700 transition-all">
                <div class="flex justify-between items-start">
                    <div>
                        <div class="flex items-center gap-1.5 mb-1">
                            <span class="w-3 h-3 rounded-full shrink-0" style="background-color: {{ $s->color_hex ?? '#E50914' }}"></span>
                            <span class="text-xs font-mono font-bold text-slate-300 block truncate">{{ $s->name }}</span>
                        </div>
                        <span class="text-2xl font-black text-white">
                            {{ $stk['free'] }}
                            <span class="text-xs text-slate-500 font-normal">/ {{ $stk['total'] }} perfiles</span>
                        </span>
                    </div>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full border font-mono {{ $stk['statusBadge'] === 'in_stock' ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30' : ($stk['statusBadge'] === 'low_stock' ? 'bg-amber-500/20 text-amber-300 border-amber-500/30' : 'bg-red-500/20 text-red-400 border-red-500/30') }}">
                        {{ $stk['statusBadge'] === 'in_stock' ? '🟢 Stock Óptimo' : ($stk['statusBadge'] === 'low_stock' ? '🟡 Stock Bajo' : '🔴 Agotado') }}
                    </span>
                </div>

                <div class="flex items-center justify-between text-xs pt-2 border-t border-slate-800/80">
                    <span class="text-[11px] text-slate-400 font-mono">{{ $stk['accountsWithCupo'] }} cuenta(s) con cupo</span>
                    <form method="POST" action="{{ route('accounts.quick_replenish') }}" class="inline">
                        @csrf
                        <input type="hidden" name="service_id" value="{{ $s->id }}">
                        <button type="submit" class="text-[11px] font-bold text-sky-400 hover:text-white bg-sky-500/10 hover:bg-sky-500/30 px-2.5 py-1 rounded-lg border border-sky-500/20 transition-all flex items-center gap-1 cursor-pointer" title="Generar automáticamente +1 cuenta máster con perfiles y PINs">
                            <span>⚡ +1 Cuenta</span>
                        </button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- FORMULARIO DE CARGA RÁPIDA DE LOTE AL INVENTARIO -->
    <!-- ======================================================== -->
    <form method="POST" action="{{ route('accounts.store') }}" class="bg-[#0f172a] p-6 rounded-3xl border border-slate-800 shadow-xl space-y-4">
        @csrf
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <h3 class="text-sm font-black text-white flex items-center gap-2">
                <span>➕</span>
                <span>Cargar Nueva Cuenta Máster al Inventario Mayorista</span>
            </h3>
            <span class="text-xs text-slate-400">Genera perfiles individuales con PINs automáticos</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Plataforma *</label>
                <select name="service_id" required class="w-full bg-slate-900 border border-slate-800 focus:border-red-500 rounded-xl p-3 text-white font-semibold outline-none text-xs">
                    @foreach($services as $srv)
                    <option value="{{ $srv->id }}">{{ $srv->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Correo Máster *</label>
                <input type="text" name="account_email" placeholder="aaaa@gmail.com" required class="w-full bg-slate-900 border border-slate-800 focus:border-red-500 rounded-xl p-3 text-white font-mono outline-none text-xs">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Contraseña Máster *</label>
                <input type="text" name="account_password" placeholder="123456789" required class="w-full bg-slate-900 border border-slate-800 focus:border-red-500 rounded-xl p-3 text-white font-mono outline-none text-xs">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Capacidad Pantallas</label>
                <select name="total_profiles" class="w-full bg-slate-900 border border-slate-800 focus:border-red-500 rounded-xl p-3 text-white font-semibold outline-none text-xs">
                    <option value="1">1 Pantalla</option>
                    <option value="2">2 Pantallas</option>
                    <option value="4">4 Pantallas</option>
                    <option value="5" selected>5 Pantallas</option>
                    <option value="7">7 Pantallas</option>
                </select>
                <input type="hidden" name="modality" value="profile">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Costo Lote (ARS)</label>
                <input type="number" step="0.01" name="supplier_cost" placeholder="10000" class="w-full bg-slate-900 border border-slate-800 focus:border-red-500 rounded-xl p-3 text-white font-mono outline-none text-xs">
            </div>
        </div>

        <div class="flex justify-between items-center pt-2">
            <span class="text-[11px] text-slate-500 font-mono">Los PINs de 4 dígitos de cada pantalla se crearán de forma aleatoria y única.</span>
            <button type="submit" class="bg-red-600 hover:bg-red-500 text-white font-black px-6 py-2.5 rounded-xl transition-all shadow-lg shadow-red-600/30 text-xs flex items-center gap-2 cursor-pointer">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span>Cargar al Inventario Mayorista</span>
            </button>
        </div>
    </form>

    <!-- ======================================================== -->
    <!-- FILTROS Y BÚSQUEDA DE CUENTAS -->
    <!-- ======================================================== -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <form method="GET" action="{{ route('accounts.index') }}" class="flex flex-wrap items-center gap-2.5 flex-1">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por correo o notas..." class="px-3.5 py-2 rounded-xl bg-[#0f172a] border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-red-500 w-60">

            <select name="service_id" onchange="this.form.submit()" class="px-3 py-2 rounded-xl bg-[#0f172a] border border-slate-800 text-xs text-white focus:outline-none focus:border-red-500">
                <option value="">Todas las plataformas</option>
                @foreach($services as $serv)
                <option value="{{ $serv->id }}" {{ request('service_id') == $serv->id ? 'selected' : '' }}>{{ $serv->name }}</option>
                @endforeach
            </select>

            <select name="status" onchange="this.form.submit()" class="px-3 py-2 rounded-xl bg-[#0f172a] border border-slate-800 text-xs text-white focus:outline-none focus:border-red-500">
                <option value="">Todos los estados</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Activas</option>
                <option value="in_review" {{ request('status') === 'in_review' ? 'selected' : '' }}>En Revisión</option>
                <option value="dropped" {{ request('status') === 'dropped' ? 'selected' : '' }}>Caídas</option>
            </select>

            @if(request()->anyFilled(['search', 'service_id', 'status']))
            <a href="{{ route('accounts.index') }}" class="text-xs text-slate-400 hover:text-white px-2">Limpiar</a>
            @endif
        </form>

        <span class="text-xs font-mono text-slate-400">Total en almacén: <strong class="text-white">{{ $accounts->total() }}</strong> cuentas</span>
    </div>

    <!-- ======================================================== -->
    <!-- LISTA DE CUENTAS MAESTRAS CON DESGLOSE DE PERFILES -->
    <!-- ======================================================== -->
    <div class="space-y-4">
        @forelse($accounts as $acc)
        @php
            $availProfiles = $acc->profiles->where('status', 'available');
            $occupiedProfiles = $acc->profiles->where('status', 'assigned');
            $reviewProfiles = $acc->profiles->where('status', 'in_review');
            $totalCount = $acc->profiles->count();
            $availCount = $availProfiles->count();
            $occCount = $occupiedProfiles->count();
        @endphp
        <div class="p-6 rounded-3xl bg-[#0f172a] border border-slate-800 shadow-xl space-y-4 hover:border-slate-700 transition-all">
            <!-- Header de la Cuenta Maestra -->
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 border-b border-slate-800/80">
                <div class="flex items-center gap-3">
                    <span class="w-3.5 h-3.5 rounded-full shrink-0 shadow" style="background-color: {{ $acc->service->color_hex ?? '#E50914' }}"></span>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-black text-white">{{ $acc->service->name }}</span>
                            <span class="text-[10px] px-2 py-0.5 rounded-full font-bold {{ $acc->status === 'active' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-red-500/20 text-red-400 border border-red-500/30' }}">
                                {{ strtoupper($acc->status) }}
                            </span>
                            <span class="text-[10px] font-mono text-slate-500">ID LOTE: #{{ $acc->id }}</span>
                        </div>
                        <div class="flex flex-wrap items-center gap-3 mt-1 text-xs text-slate-400">
                            <span>Proveedor: <strong class="text-slate-300">{{ $acc->supplier ? $acc->supplier->name : 'Mayorista Directo' }}</strong></span>
                            @if($acc->supplier_cost > 0)
                            <span>Costo: <strong class="text-sky-400 font-mono">${{ number_format($acc->supplier_cost, 2) }}</strong></span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Credenciales Máster (Correo & Contraseña Privada) -->
                <div class="flex flex-wrap items-center gap-2.5">
                    <!-- Correo Máster -->
                    <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-800 text-xs">
                        <i data-lucide="mail" class="w-3.5 h-3.5 text-slate-400"></i>
                        <span class="font-mono text-white font-bold">{{ $acc->account_email }}</span>
                        <button @click="copyText('{{ $acc->account_email }}', 'Correo')" class="text-slate-400 hover:text-white" title="Copiar correo">
                            <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                        </button>
                    </div>

                    <!-- Contraseña Máster (con botón Ver/Ocultar y Modificar) -->
                    <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-800 text-xs">
                        <i data-lucide="lock" class="w-3.5 h-3.5 text-slate-400"></i>
                        <span class="font-mono text-amber-400 font-bold" x-text="showPasswords[{{ $acc->id }}] ? '{{ $acc->account_password }}' : '••••••••'"></span>
                        <button type="button" @click="togglePassword({{ $acc->id }})" class="text-slate-400 hover:text-white text-[11px]" title="Ver/Ocultar">
                            <i data-lucide="eye" class="w-3.5 h-3.5" x-show="!showPasswords[{{ $acc->id }}]"></i>
                            <i data-lucide="eye-off" class="w-3.5 h-3.5" x-show="showPasswords[{{ $acc->id }}]"></i>
                        </button>
                        <button @click="copyText('{{ $acc->account_password }}', 'Contraseña')" class="text-slate-400 hover:text-white" title="Copiar contraseña">
                            <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                        </button>
                    </div>

                    <!-- Botón Modificar Clave Privada (estilo StreamPro) -->
                    <button @click="openPasswordModal({{ json_encode($acc) }})" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold border border-slate-700 transition-colors flex items-center gap-1.5 cursor-pointer" title="Modificar la contraseña maestra">
                        <i data-lucide="key" class="w-3.5 h-3.5 text-amber-400"></i>
                        <span>🔒 Clave Privada</span>
                    </button>

                    <!-- Editar Cuenta Completa / Lote de Streaming -->
                    <button type="button" @click="openEditModal({{ json_encode($acc->load(['profiles', 'service', 'supplier'])) }})" class="p-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 transition-colors cursor-pointer" title="Editar Lote Completo">
                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                    </button>

                    <!-- Eliminar -->
                    <form method="POST" action="{{ route('accounts.destroy', $acc) }}" onsubmit="return confirm('¿Seguro que deseas eliminar esta cuenta del inventario?');" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="p-2 rounded-xl text-slate-500 hover:text-rose-400 hover:bg-rose-500/10 transition-all" title="Eliminar cuenta">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </form>
                </div>
            </div>

            <!-- DESGLOSE DE PANTALLAS: PERFILES DISPONIBLES & PERFILES VENDIDOS -->
            <div class="space-y-2.5">
                <div class="flex items-center justify-between text-xs text-slate-400">
                    <div class="flex items-center gap-3">
                        <span class="font-bold uppercase tracking-wider text-[10px]">Estado de Pantallas:</span>
                        <span class="text-emerald-400 font-bold font-mono">🟢 {{ $availCount }} Disponibles</span>
                        <span class="text-sky-400 font-bold font-mono">🔵 {{ $occCount }} Vendidos</span>
                        @if($reviewProfiles->count() > 0)
                        <span class="text-amber-400 font-bold font-mono">🟡 {{ $reviewProfiles->count() }} En Revisión</span>
                        @endif
                    </div>
                    <span class="font-mono text-slate-500">{{ $totalCount }} pantallas totales</span>
                </div>

                <!-- Chips Visuales de Perfiles -->
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-2.5">
                    @foreach($acc->profiles as $prof)
                    @php
                        $sale = $prof->activeSale;
                        $customer = $sale ? $sale->customer : null;
                    @endphp

                    @if($prof->status === 'available')
                    <!-- Perfil Disponible -->
                    <div class="p-2.5 rounded-2xl bg-emerald-950/20 border border-emerald-500/30 text-xs flex items-center justify-between gap-2">
                        <div>
                            <span class="font-bold text-white block">{{ $prof->profile_name }}</span>
                            <span class="text-amber-400 font-mono text-[11px] font-black">PIN: {{ $prof->pin ?: '1234' }}</span>
                        </div>
                        <span class="text-[9px] font-black uppercase px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                            LIBRE
                        </span>
                    </div>

                    @elseif($prof->status === 'assigned')
                    <!-- Perfil Vendido / Ocupado -->
                    <div class="p-2.5 rounded-2xl bg-slate-900 border border-slate-800 text-xs space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-slate-200 truncate">{{ $prof->profile_name }}</span>
                            <span class="text-[9px] font-black uppercase px-1.5 py-0.5 rounded-full bg-sky-500/20 text-sky-400 border border-sky-500/30">
                                VENDIDO
                            </span>
                        </div>
                        <div class="text-[11px] font-mono text-slate-400 flex items-center justify-between">
                            <span>PIN: <strong class="text-amber-400">{{ $prof->pin }}</strong></span>
                            @if($sale)
                            <span class="text-slate-500 text-[10px]">Vence: {{ \Carbon\Carbon::parse($sale->end_date)->format('d/m') }}</span>
                            @endif
                        </div>
                        @if($customer)
                        <div class="text-[10px] text-slate-400 truncate pt-0.5 border-t border-slate-800/80">
                            Cliente: <strong class="text-white">{{ $customer->name }}</strong>
                        </div>
                        @endif
                    </div>

                    @else
                    <!-- Perfil en Revisión -->
                    <div class="p-2.5 rounded-2xl bg-amber-950/20 border border-amber-500/30 text-xs flex items-center justify-between gap-2">
                        <div>
                            <span class="font-bold text-amber-200 block">{{ $prof->profile_name }}</span>
                            <span class="text-slate-400 font-mono text-[10px]">PIN: {{ $prof->pin }}</span>
                        </div>
                        <span class="text-[9px] font-black uppercase px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-400 border border-amber-500/30">
                            REVISIÓN
                        </span>
                    </div>
                    @endif
                    @endforeach
                </div>
            </div>
        </div>
        @empty
        <div class="p-12 text-center text-slate-500 rounded-3xl bg-[#0f172a] border border-slate-800 border-dashed">
            <i data-lucide="server" class="w-12 h-12 text-slate-600 mx-auto mb-3"></i>
            <h4 class="text-base font-bold text-white">Inventario Mayorista Vacío</h4>
            <p class="text-xs text-slate-400 mt-1">Usa el formulario superior o el botón de Reabastecimiento Rápido para cargar tus cuentas.</p>
        </div>
        @endforelse

        <div class="mt-4">
            {{ $accounts->links() }}
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL: CAMBIO PRIVADO DE CONTRASEÑA (STREAMPRO) -->
    <!-- ======================================================== -->
    <div x-show="privatePasswordModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm" @click.self="privatePasswordModal = false">
        <div class="bg-[#0f172a] p-6 rounded-3xl border border-slate-800 shadow-2xl max-w-md w-full space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <div class="flex items-center gap-2 text-amber-400">
                    <i data-lucide="key" class="w-5 h-5"></i>
                    <h3 class="font-black text-white text-base">Modificar Contraseña Maestra</h3>
                </div>
                <button @click="privatePasswordModal = false" class="text-slate-400 hover:text-white">✕</button>
            </div>

            <p class="text-xs text-slate-400">
                Actualiza la contraseña de la cuenta <strong class="text-white" x-text="selectedAccount?.account_email"></strong>. Puedes guardarla privadamente solo para el proveedor o sincronizarla con los clientes que tienen perfiles activos.
            </p>

            <form x-bind:action="'/accounts/' + (selectedAccount ? selectedAccount.id : '') + '/private-password'" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Nueva Contraseña de Acceso *</label>
                    <input type="text" name="account_password" required x-bind:value="selectedAccount?.account_password" class="w-full p-3 rounded-xl bg-slate-900 border border-slate-700 text-sm font-mono text-white outline-none focus:border-red-500">
                </div>

                <div class="p-3 rounded-xl bg-slate-900 border border-slate-800">
                    <label class="flex items-start gap-2.5 cursor-pointer">
                        <input type="checkbox" name="notify_active_clients" value="1" class="mt-0.5 rounded bg-slate-800 border-slate-700 text-red-600 focus:ring-0">
                        <div class="text-xs">
                            <span class="font-bold text-slate-200 block">Sincronizar en Bóveda de Clientes Activos</span>
                            <span class="text-[11px] text-slate-400">Actualiza automáticamente el snapshot de contraseña para los clientes con pantallas activas.</span>
                        </div>
                    </label>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="privatePasswordModal = false" class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 text-xs font-bold hover:bg-slate-700">
                        Cancelar
                    </button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white text-xs font-black shadow-lg">
                        Guardar Contraseña
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL: EDITAR LOTE DE STREAMING COMPLETO -->
    <!-- ======================================================== -->
    <div x-show="editAccountModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm" @click.self="editAccountModal = false">
        <div class="bg-[#0f172a] p-6 rounded-3xl border border-slate-700 shadow-2xl max-w-2xl w-full max-h-[90vh] flex flex-col space-y-4">
            <!-- Header Modal -->
            <div class="flex items-center justify-between pb-3 border-b border-slate-800 shrink-0">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-red-600/20 text-red-400 border border-red-500/30 flex items-center justify-center">
                        <i data-lucide="edit-3" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="font-black text-white text-base flex items-center gap-2">
                            <span>Editar Lote de Streaming</span>
                            <span class="text-xs px-2 py-0.5 rounded-full bg-slate-800 font-mono text-slate-300 border border-slate-700" x-text="'Lote #' + editForm.id"></span>
                        </h3>
                        <p class="text-xs text-slate-400">Modifica credenciales máster, proveedor, fechas y perfiles/PINs individuales</p>
                    </div>
                </div>
                <button type="button" @click="editAccountModal = false" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Scrollable Form Body -->
            <form :action="'/accounts/' + editForm.id" method="POST" class="space-y-4 overflow-y-auto pr-1 flex-1">
                @csrf
                @method('PUT')

                <!-- Fila 1: Plataforma y Proveedor -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Plataforma / Servicio *</label>
                        <select name="service_id" x-model="editForm.service_id" required class="w-full p-2.5 rounded-xl bg-slate-900 border border-slate-800 text-xs font-semibold text-white outline-none focus:border-red-500">
                            @foreach($services as $srv)
                            <option value="{{ $srv->id }}">{{ $srv->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Proveedor / Mayorista</label>
                        <select name="supplier_id" x-model="editForm.supplier_id" class="w-full p-2.5 rounded-xl bg-slate-900 border border-slate-800 text-xs font-semibold text-white outline-none focus:border-red-500">
                            <option value="">Mayorista Directo / Sin Asignar</option>
                            @foreach($suppliers as $sup)
                            <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Fila 2: Correo Máster y Contraseña Máster -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Correo Máster *</label>
                        <input type="text" name="account_email" x-model="editForm.account_email" required class="w-full p-2.5 rounded-xl bg-slate-900 border border-slate-800 text-xs font-mono text-white outline-none focus:border-red-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-1 flex items-center justify-between">
                            <span>Contraseña Máster *</span>
                            <button type="button" @click="editForm.showPassword = !editForm.showPassword" class="text-[10px] text-amber-400 hover:underline">
                                <span x-text="editForm.showPassword ? 'Ocultar' : 'Mostrar'"></span>
                            </button>
                        </label>
                        <div class="relative">
                            <input :type="editForm.showPassword ? 'text' : 'password'" name="account_password" x-model="editForm.account_password" required class="w-full p-2.5 rounded-xl bg-slate-900 border border-slate-800 text-xs font-mono text-white outline-none focus:border-red-500">
                        </div>
                    </div>
                </div>

                <!-- Fila 3: Correo de Recuperación y Estado -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Correo de Recuperación (Opcional)</label>
                        <input type="text" name="recovery_email" x-model="editForm.recovery_email" placeholder="recov@gmail.com" class="w-full p-2.5 rounded-xl bg-slate-900 border border-slate-800 text-xs font-mono text-white outline-none focus:border-red-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Estado de la Cuenta *</label>
                        <select name="status" x-model="editForm.status" required class="w-full p-2.5 rounded-xl bg-slate-900 border border-slate-800 text-xs font-semibold text-white outline-none focus:border-red-500">
                            <option value="active">🟢 Activa (Operativa para venta)</option>
                            <option value="in_review">🟡 En Revisión (Bloqueada temporalmente)</option>
                            <option value="dropped">🔴 Caída (Requiere reposición)</option>
                            <option value="expired">⚪ Vencida</option>
                            <option value="paused">⏸️ Pausada</option>
                        </select>
                    </div>
                </div>

                <!-- Fila 4: Costo Proveedor y Fecha de Vencimiento -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Costo Proveedor / Lote ($ ARS)</label>
                        <input type="number" step="0.01" name="supplier_cost" x-model="editForm.supplier_cost" class="w-full p-2.5 rounded-xl bg-slate-900 border border-slate-800 text-xs font-mono text-white outline-none focus:border-red-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Fecha de Vencimiento Proveedor</label>
                        <input type="date" name="supplier_expiration_date" x-model="editForm.supplier_expiration_date" class="w-full p-2.5 rounded-xl bg-slate-900 border border-slate-800 text-xs font-mono text-white outline-none focus:border-red-500">
                    </div>
                </div>

                <!-- SECCIÓN: PANTALLAS / PERFILES DEL LOTE -->
                <div class="pt-3 border-t border-slate-800 space-y-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-xs font-black uppercase tracking-wider text-amber-400 flex items-center gap-1.5">
                                <i data-lucide="tv" class="w-4 h-4"></i>
                                <span>Pantallas & Perfiles del Lote</span>
                            </h4>
                            <p class="text-[11px] text-slate-400">Edita el nombre de pantalla, su PIN de 4 dígitos y su disponibilidad.</p>
                        </div>

                        <button type="button" @click="addProfileToEditForm()" class="px-2.5 py-1 rounded-xl bg-amber-500/10 hover:bg-amber-500/20 text-amber-300 border border-amber-500/30 text-[11px] font-bold flex items-center gap-1 transition-all cursor-pointer">
                            <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                            <span>+ Agregar Pantalla</span>
                        </button>
                    </div>

                    <div class="space-y-2.5">
                        <template x-for="(prof, idx) in editForm.profiles" :key="prof.id">
                            <div class="p-3 rounded-2xl bg-slate-900 border border-slate-800 grid grid-cols-1 sm:grid-cols-12 gap-2.5 items-center">
                                <div class="sm:col-span-1 text-center font-mono font-bold text-xs text-slate-400">
                                    <span class="w-6 h-6 rounded-full bg-slate-800 flex items-center justify-center mx-auto" x-text="prof.profile_number || (idx + 1)"></span>
                                </div>

                                <div class="sm:col-span-5">
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-0.5">Nombre Perfil</label>
                                    <input type="text" :name="'profiles[' + prof.id + '][name]'" x-model="prof.profile_name" required class="w-full p-2 rounded-xl bg-[#0b0f19] border border-slate-700 text-xs text-white outline-none focus:border-amber-500">
                                    <input type="hidden" :name="'profiles[' + prof.id + '][is_new]'" :value="prof.is_new ? 1 : 0">
                                </div>

                                <div class="sm:col-span-3">
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-0.5">PIN 4 Dígitos</label>
                                    <input type="text" maxlength="8" :name="'profiles[' + prof.id + '][pin]'" x-model="prof.pin" required class="w-full p-2 rounded-xl bg-[#0b0f19] border border-slate-700 text-xs font-mono font-bold text-amber-400 outline-none focus:border-amber-500 text-center">
                                </div>

                                <div class="sm:col-span-3">
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-0.5">Estado</label>
                                    <select :name="'profiles[' + prof.id + '][status]'" x-model="prof.status" class="w-full p-2 rounded-xl bg-[#0b0f19] border border-slate-700 text-xs font-semibold text-white outline-none focus:border-amber-500">
                                        <option value="available">🟢 Libre</option>
                                        <option value="assigned">🔵 Vendido</option>
                                        <option value="in_review">🟡 En Revisión</option>
                                    </select>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- SINCRONIZACIÓN CON BÓVEDA DE CLIENTES -->
                <div class="p-3.5 rounded-2xl bg-slate-900 border border-slate-800">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" name="sync_clients" value="1" x-model="editForm.sync_clients" class="mt-0.5 rounded bg-slate-800 border-slate-700 text-red-600 focus:ring-0">
                        <div class="text-xs space-y-0.5">
                            <span class="font-bold text-white block">Sincronizar cambios en Bóveda de Clientes Activos</span>
                            <span class="text-[11px] text-slate-400 block">Actualiza automáticamente el correo, la clave y los PINs en las compras activas de los clientes para que no tengan que pedir soporte.</span>
                        </div>
                    </label>
                </div>

                <!-- Notas Internas -->
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Notas Internas del Lote</label>
                    <textarea name="notes" rows="2" x-model="editForm.notes" placeholder="Detalles de compra, método de pago, datos del proveedor..." class="w-full p-2.5 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white outline-none focus:border-red-500"></textarea>
                </div>

                <!-- Footer Acciones -->
                <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-800 shrink-0">
                    <button type="button" @click="editAccountModal = false" class="px-4 py-2.5 rounded-xl bg-slate-800 text-slate-300 text-xs font-bold hover:bg-slate-700">
                        Cancelar
                    </button>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white text-xs font-black shadow-lg shadow-red-600/30 flex items-center gap-1.5 cursor-pointer">
                        <i data-lucide="check" class="w-4 h-4"></i>
                        <span>Guardar Lote Completo</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
