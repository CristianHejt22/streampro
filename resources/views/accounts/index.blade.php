@extends('layouts.app')

@section('title', 'Inventario de Cuentas y Pantallas')
@section('page_title', 'Inventario / Stock de Cuentas')
@section('page_subtitle', 'Almacén de cuentas maestras, proveedores, contraseñas y perfiles individuales con PIN')

@section('content')
<div class="space-y-6" x-data="{
    newAccountModal: false,
    editAccountModal: false,
    selectedAccount: null,
    totalProfiles: 5,
    showPasswords: {},
    togglePassword(id) {
        this.showPasswords[id] = !this.showPasswords[id];
    },
    copyText(text) {
        navigator.clipboard.writeText(text);
        alert('Copiado al portapapeles: ' + text);
    }
}">

    <!-- Top KPI Bar -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="p-4 rounded-2xl bg-[#111827] border border-gray-800 shadow-md">
            <span class="text-xs font-semibold uppercase tracking-wider text-gray-400 block">Cuentas Maestras</span>
            <span class="text-2xl font-black text-white mt-1 block">{{ $totalMasterAccounts }}</span>
            <span class="text-[11px] text-gray-400">Total en almacén</span>
        </div>

        <div class="p-4 rounded-2xl bg-[#111827] border border-gray-800 shadow-md">
            <span class="text-xs font-semibold uppercase tracking-wider text-emerald-400 block">Cuentas Activas</span>
            <span class="text-2xl font-black text-emerald-400 mt-1 block">{{ $activeAccounts }}</span>
            <span class="text-[11px] text-emerald-400/80">Estables en servicio</span>
        </div>

        <div class="p-4 rounded-2xl bg-[#111827] border border-gray-800 shadow-md">
            <span class="text-xs font-semibold uppercase tracking-wider text-cyan-400 block">Pantallas Libres</span>
            <span class="text-2xl font-black text-cyan-400 mt-1 block">{{ $totalAvailableProfiles }}</span>
            <span class="text-[11px] text-cyan-400/80">Disponibles para venta inmediata</span>
        </div>

        <div class="p-4 rounded-2xl bg-[#111827] border border-gray-800 shadow-md">
            <span class="text-xs font-semibold uppercase tracking-wider text-rose-400 block">Caídas / En Revisión</span>
            <span class="text-2xl font-black text-rose-400 mt-1 block">{{ $droppedAccounts }}</span>
            <span class="text-[11px] text-rose-400/80">Requieren reemplazo o reclamo</span>
        </div>
    </div>

    <!-- Action Bar & Filters -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <!-- Search & Filter Form -->
        <form method="GET" action="{{ route('accounts.index') }}" class="flex flex-wrap items-center gap-2.5 flex-1">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por correo o notas..." class="px-3.5 py-2 rounded-xl bg-[#111827] border border-gray-800 text-xs text-white placeholder-gray-400 focus:outline-none focus:border-red-500 w-48 sm:w-64">

            <select name="service_id" onchange="this.form.submit()" class="px-3 py-2 rounded-xl bg-[#111827] border border-gray-800 text-xs text-white focus:outline-none focus:border-red-500">
                <option value="">Todas las plataformas</option>
                @foreach($services as $serv)
                <option value="{{ $serv->id }}" {{ request('service_id') == $serv->id ? 'selected' : '' }}>{{ $serv->name }}</option>
                @endforeach
            </select>

            <select name="status" onchange="this.form.submit()" class="px-3 py-2 rounded-xl bg-[#111827] border border-gray-800 text-xs text-white focus:outline-none focus:border-red-500">
                <option value="">Todos los estados</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Activas</option>
                <option value="in_review" {{ request('status') === 'in_review' ? 'selected' : '' }}>En Revisión</option>
                <option value="dropped" {{ request('status') === 'dropped' ? 'selected' : '' }}>Caídas</option>
                <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Vencidas</option>
            </select>

            @if(request()->anyFilled(['search', 'service_id', 'status']))
            <a href="{{ route('accounts.index') }}" class="text-xs text-gray-400 hover:text-white px-2">Limpiar</a>
            @endif
        </form>

        <button @click="newAccountModal = true" class="px-4 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold text-xs flex items-center gap-2 transition-all shadow-lg shadow-red-600/30 shrink-0">
            <i data-lucide="plus-circle" class="w-4 h-4"></i>
            <span>Cargar Cuenta al Stock</span>
        </button>
    </div>

    <!-- Accounts Cards List -->
    <div class="space-y-4">
        @forelse($accounts as $acc)
        @php
            $availCount = $acc->available_profiles_count;
            $totalCount = $acc->profiles->count();
            $occCount = $acc->occupied_profiles_count;
            $percent = $totalCount > 0 ? round(($availCount / $totalCount) * 100) : 0;
            $daysToSup = $acc->days_until_supplier_expiration;
        @endphp
        <div class="p-5 rounded-2xl bg-[#111827] border border-gray-800 shadow-xl space-y-4 hover:border-gray-700 transition-all">
            <!-- Header de la Cuenta Maestra -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 pb-3 border-b border-gray-800/80">
                <div class="flex items-center gap-3">
                    <span class="w-3.5 h-3.5 rounded-full shrink-0" style="background-color: {{ $acc->service->color_hex ?? '#E50914' }}"></span>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-bold text-white">{{ $acc->service->name }}</span>
                            <span class="text-[10px] px-2 py-0.5 rounded-full font-bold {{ $acc->status === 'active' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : ($acc->status === 'dropped' ? 'bg-rose-500/20 text-rose-400 border border-rose-500/30' : 'bg-amber-500/20 text-amber-400 border border-amber-500/30') }}">
                                {{ strtoupper($acc->status) }}
                            </span>
                            <span class="text-[10px] px-2 py-0.5 rounded bg-gray-800 text-gray-300 font-medium">
                                {{ $acc->modality === 'profile' ? 'Perfiles / Pantallas' : 'Cuenta Completa' }}
                            </span>
                        </div>
                        <div class="flex items-center gap-3 mt-1 text-xs text-gray-400">
                            <span>Proveedor: <strong class="text-gray-300">{{ $acc->supplier ? $acc->supplier->name : 'Directo' }}</strong></span>
                            @if($acc->supplier_cost > 0)
                            <span>Costo: <strong class="text-cyan-400">${{ number_format($acc->supplier_cost, 2) }}</strong></span>
                            @endif
                            @if($acc->supplier_expiration_date)
                            <span>Vence Proveedor: <strong class="{{ $daysToSup <= 3 ? 'text-amber-400' : 'text-gray-300' }}">{{ $acc->supplier_expiration_date->format('d/m/Y') }}</strong> ({{ $daysToSup }} días)</span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Credenciales Acceso Maestra -->
                <div class="flex items-center gap-2 self-start md:self-auto">
                    <!-- Email Copiar -->
                    <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-[#0b0f19] border border-gray-800">
                        <i data-lucide="mail" class="w-3.5 h-3.5 text-gray-400"></i>
                        <span class="font-mono text-xs text-white">{{ $acc->account_email }}</span>
                        <button @click="copyText('{{ $acc->account_email }}')" class="text-gray-400 hover:text-white ml-1">
                            <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                        </button>
                    </div>

                    <!-- Password Copiar / Reveal -->
                    <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-[#0b0f19] border border-gray-800" x-data="{ reveal: false }">
                        <i data-lucide="lock" class="w-3.5 h-3.5 text-gray-400"></i>
                        <span class="font-mono text-xs text-white" x-text="reveal ? '{{ $acc->account_password }}' : '••••••••'"></span>
                        <button @click="reveal = !reveal" class="text-gray-400 hover:text-white">
                            <i data-lucide="eye" class="w-3.5 h-3.5" x-show="!reveal"></i>
                            <i data-lucide="eye-off" class="w-3.5 h-3.5" x-show="reveal"></i>
                        </button>
                        <button @click="copyText('{{ $acc->account_password }}')" class="text-gray-400 hover:text-white ml-1">
                            <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                        </button>
                    </div>

                    <!-- Acciones -->
                    <button @click="selectedAccount = {{ json_encode($acc->load('profiles')) }}; editAccountModal = true;" title="Editar Cuenta" class="p-2 rounded-xl bg-gray-800 hover:bg-gray-700 text-gray-300">
                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                    </button>

                    <form method="POST" action="{{ route('accounts.toggle', $acc) }}" class="inline">
                        @csrf
                        <button type="submit" title="{{ $acc->status === 'active' ? 'Marcar como caída' : 'Marcar como activa' }}" class="p-2 rounded-xl {{ $acc->status === 'active' ? 'bg-amber-600/20 text-amber-400 hover:bg-amber-600 hover:text-white' : 'bg-emerald-600/20 text-emerald-400 hover:bg-emerald-600 hover:text-white' }} transition-all">
                            <i data-lucide="power" class="w-4 h-4"></i>
                        </button>
                    </form>

                    <form method="POST" action="{{ route('accounts.destroy', $acc) }}" onsubmit="return confirm('¿Seguro que deseas eliminar esta cuenta del inventario?');" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" title="Eliminar cuenta" class="p-2 rounded-xl text-gray-400 hover:text-rose-400 hover:bg-rose-500/10 transition-all">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Desglose de Perfiles / Pantallas -->
            @if($acc->modality === 'profile')
            <div>
                <div class="flex items-center justify-between text-xs text-gray-400 mb-2">
                    <span class="font-bold uppercase tracking-wider text-[10px]">Ocupación de Pantallas:</span>
                    <span><strong class="text-emerald-400">{{ $availCount }} libres</strong> de {{ $totalCount }} perfiles ({{ $percent }}% disponible)</span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 lg:grid-cols-7 gap-2">
                    @foreach($acc->profiles as $prof)
                    @php
                        $activeSale = $prof->activeSale;
                    @endphp
                    <div class="p-2.5 rounded-xl border text-center transition-all {{ $prof->status === 'available' ? 'bg-[#0b0f19] border-emerald-500/30' : ($prof->status === 'assigned' ? 'bg-gray-900 border-gray-800' : 'bg-rose-950/20 border-rose-500/30') }}">
                        <div class="text-[11px] font-bold text-white truncate">{{ $prof->profile_name }}</div>
                        @if($prof->pin)
                        <div class="text-[10px] font-mono text-amber-400 font-bold mt-0.5">PIN: {{ $prof->pin }}</div>
                        @endif
                        <div class="mt-1.5">
                            @if($prof->status === 'available')
                            <span class="text-[9px] px-1.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 font-bold">
                                LIBRE
                            </span>
                            @elseif($prof->status === 'assigned')
                            <span class="text-[9px] px-1.5 py-0.5 rounded-full bg-blue-500/20 text-blue-400 font-bold" title="{{ $activeSale ? 'Vendido a: ' . $activeSale->customer->name : 'Ocupado' }}">
                                OCUPADO
                            </span>
                            @else
                            <span class="text-[9px] px-1.5 py-0.5 rounded-full bg-rose-500/20 text-rose-400 font-bold">
                                {{ strtoupper($prof->status) }}
                            </span>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            @if($acc->notes)
            <div class="text-[11px] text-gray-400 pt-1">
                <strong>Notas:</strong> {{ $acc->notes }}
            </div>
            @endif
        </div>
        @empty
        <div class="p-12 text-center text-gray-400 rounded-2xl bg-[#111827] border border-gray-800">
            <i data-lucide="server" class="w-12 h-12 text-gray-600 mx-auto mb-3"></i>
            <div class="text-sm font-semibold text-gray-300">No hay cuentas maestras en el inventario.</div>
            <p class="text-xs text-gray-400 mt-1">Haz clic en "Cargar Cuenta al Stock" para comenzar a abastecer tu catálogo.</p>
        </div>
        @endforelse
    </div>

    <!-- ========================================== -->
    <!-- MODAL: CARGAR CUENTA MAESTRA AL STOCK -->
    <!-- ========================================== -->
    <div x-show="newAccountModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
        <div @click.away="newAccountModal = false" class="w-full max-w-2xl bg-[#111827] border border-gray-800 rounded-3xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
            <div class="p-6 border-b border-gray-800 flex items-center justify-between bg-[#0e1424]">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-red-600/20 text-red-500 flex items-center justify-center font-bold">
                        <i data-lucide="server" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white">Cargar Cuenta Maestra al Stock</h3>
                        <p class="text-xs text-gray-400">Ingresa las credenciales y define los perfiles con PIN</p>
                    </div>
                </div>
                <button @click="newAccountModal = false" class="text-gray-400 hover:text-white">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('accounts.store') }}" class="p-6 overflow-y-auto space-y-4">
                @csrf

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Plataforma / Servicio *</label>
                        <select name="service_id" required class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white focus:border-red-500 font-semibold">
                            @foreach($services as $s)
                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Proveedor (Opcional)</label>
                        <select name="supplier_id" class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white focus:border-red-500">
                            <option value="">-- Sin proveedor asignado --</option>
                            @foreach($suppliers as $sup)
                            <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Correo / Usuario de Acceso *</label>
                        <input type="text" name="account_email" placeholder="cuenta.streaming@gmail.com" required class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white focus:border-red-500 font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Contraseña de la Cuenta *</label>
                        <input type="text" name="account_password" placeholder="ClaveSegura2026!" required class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white focus:border-red-500 font-mono">
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Modalidad</label>
                        <select name="modality" x-model="modality" class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white focus:border-red-500 font-semibold">
                            <option value="profile">Perfiles / Pantallas</option>
                            <option value="full_account">Cuenta Completa</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Cant. Perfiles</label>
                        <input type="number" name="total_profiles" min="1" max="10" x-model.number="totalProfiles" class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white focus:border-red-500 font-bold">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Costo Proveedor ($)</label>
                        <input type="number" step="0.01" name="supplier_cost" placeholder="0.00" class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white focus:border-red-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Vence con el Proveedor (Opcional)</label>
                        <input type="date" name="supplier_expiration_date" class="w-full px-3.5 py-2 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white focus:border-red-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Correo Recuperación / PIN Parental</label>
                        <input type="text" name="recovery_email" placeholder="Datos extra de recuperación" class="w-full px-3.5 py-2 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white focus:border-red-500">
                    </div>
                </div>

                <!-- Generador de Perfiles y PINs -->
                <div class="p-4 rounded-2xl bg-[#0b0f19] border border-gray-800 space-y-2">
                    <span class="text-xs font-bold text-white block">Configuración de Perfiles y PINs</span>
                    <p class="text-[11px] text-gray-400">El sistema creará <span class="text-white font-bold" x-text="totalProfiles"></span> perfiles con PINs aleatorios o puedes personalizarlos.</p>
                    
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 mt-2">
                        <template x-for="i in totalProfiles" :key="i">
                            <div class="p-2 rounded-lg bg-gray-900 border border-gray-800 text-xs">
                                <label class="text-[10px] text-gray-400 block" x-text="'Perfil ' + i"></label>
                                <input type="text" :name="'profile_names[' + i + ']'" :value="'Pantalla ' + i" class="w-full px-2 py-1 rounded bg-black/40 border border-gray-700 text-xs text-white mb-1">
                                <input type="text" :name="'profile_pins[' + i + ']'" :value="Math.floor(1000 + Math.random() * 9000)" class="w-full px-2 py-1 rounded bg-black/40 border border-amber-500/30 text-amber-400 font-mono font-bold text-xs" placeholder="PIN 4 dígitos">
                            </div>
                        </template>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Notas Internas</label>
                    <textarea name="notes" rows="2" placeholder="Observaciones de la cuenta..." class="w-full px-3.5 py-2 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white focus:border-red-500"></textarea>
                </div>

                <div class="pt-4 border-t border-gray-800 flex items-center justify-end gap-3">
                    <button type="button" @click="newAccountModal = false" class="px-4 py-2 text-xs font-semibold text-gray-400 hover:text-white">Cancelar</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold text-xs shadow-lg shadow-red-600/30">
                        Guardar Cuenta en Stock
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL: EDITAR CUENTA MAESTRA -->
    <!-- ========================================== -->
    <div x-show="editAccountModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
        <div @click.away="editAccountModal = false" class="w-full max-w-xl bg-[#111827] border border-gray-800 rounded-3xl shadow-2xl p-6 overflow-y-auto max-h-[90vh]">
            <h3 class="text-base font-bold text-white mb-1">Editar Cuenta de Streaming</h3>
            <p class="text-xs text-gray-400 mb-4">Actualizar credenciales, estado o fecha con el proveedor</p>

            <form :action="'/accounts/' + selectedAccount?.id" method="POST" class="space-y-4">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs text-gray-400 font-semibold mb-1">Correo / Usuario</label>
                        <input type="text" name="account_email" :value="selectedAccount?.account_email" required class="w-full px-3 py-2 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white font-mono">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-400 font-semibold mb-1">Contraseña</label>
                        <input type="text" name="account_password" :value="selectedAccount?.account_password" required class="w-full px-3 py-2 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white font-mono">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs text-gray-400 font-semibold mb-1">Estado de la Cuenta</label>
                        <select name="status" class="w-full px-3 py-2 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white">
                            <option value="active" :selected="selectedAccount?.status === 'active'">Activa (Operativa)</option>
                            <option value="in_review" :selected="selectedAccount?.status === 'in_review'">En Revisión / Caída</option>
                            <option value="dropped" :selected="selectedAccount?.status === 'dropped'">Caída (Reclamar Proveedor)</option>
                            <option value="expired" :selected="selectedAccount?.status === 'expired'">Vencida</option>
                            <option value="paused" :selected="selectedAccount?.status === 'paused'">Pausada</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-400 font-semibold mb-1">Vence con Proveedor</label>
                        <input type="date" name="supplier_expiration_date" :value="selectedAccount?.supplier_expiration_date ? selectedAccount.supplier_expiration_date.split('T')[0] : ''" class="w-full px-3 py-2 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white">
                    </div>
                </div>

                <div>
                    <label class="block text-xs text-gray-400 font-semibold mb-1">Notas Internas</label>
                    <textarea name="notes" rows="2" :value="selectedAccount?.notes" class="w-full px-3 py-2 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-800">
                    <button type="button" @click="editAccountModal = false" class="px-4 py-2 text-xs text-gray-400 hover:text-white">Cancelar</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold text-xs">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
