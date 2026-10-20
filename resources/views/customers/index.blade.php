@extends('layouts.app')

@section('title', 'Clientes & Bóvedas de Streaming')
@section('page_title', 'Clientes Registrados & Bóvedas Privadas')
@section('page_subtitle', 'Gestión de usuarios registrados, activación de cuentas por correo y contraseña, y soporte')

@section('content')
<div class="space-y-6" x-data="{
    newCustomerModal: false,
    editCustomerModal: false,
    activateModal: false,
    resetPasswordModal: false,
    selectedCustomer: null,
    registrationLink: '{{ Auth::user()->referral_code ? url('/ref/' . Auth::user()->referral_code . '/registro') : url('/registro') }}',
    copyRegistrationLink() {
        navigator.clipboard.writeText(this.registrationLink);
        alert('Enlace de registro copiado al portapapeles: ' + this.registrationLink);
    },
    openActivateModal(c) {
        this.selectedCustomer = c;
        this.activateModal = true;
    },
    openResetModal(c) {
        this.selectedCustomer = c;
        this.resetPasswordModal = true;
    }
}">

    <!-- Banner con Enlace de Registro para Clientes del Revendedor -->
    <div class="bg-gradient-to-r from-[#0f172a] via-[#1e293b] to-[#0f172a] p-5 rounded-3xl border border-slate-800 shadow-xl flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="space-y-1">
            <div class="flex items-center gap-2">
                <span class="text-xs font-mono font-bold text-amber-400 bg-amber-500/10 px-2 py-0.5 rounded border border-amber-500/20">
                    SISTEMA DE REGISTRO DE CLIENTES
                </span>
                <span class="text-xs text-slate-400">Activación por Correo y Contraseña</span>
            </div>
            <p class="text-xs text-slate-300">
                Comparte tu enlace de registro para que tus clientes creen su acceso a la Bóveda Privada:
            </p>
        </div>

        <div class="flex items-center gap-2">
            <div class="px-3.5 py-2 rounded-xl bg-slate-900 border border-slate-700 text-xs font-mono text-sky-400 truncate max-w-xs sm:max-w-md" x-text="registrationLink"></div>
            <button @click="copyRegistrationLink()" class="px-3.5 py-2 rounded-xl bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs flex items-center gap-1.5 transition-all shadow shrink-0 cursor-pointer">
                <i data-lucide="copy" class="w-4 h-4"></i>
                <span>Copiar Enlace</span>
            </button>
        </div>
    </div>

    <!-- Barra de Filtros y Registro Manual -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <form method="GET" action="{{ route('customers.index') }}" class="flex items-center gap-2 flex-1 max-w-md">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por Nombre, Correo, Código CLI o Teléfono..." class="w-full px-3.5 py-2 rounded-xl bg-[#0f172a] border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-red-500">
            <button type="submit" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-xs font-semibold cursor-pointer">Buscar</button>
            @if(request('search'))
            <a href="{{ route('customers.index') }}" class="text-xs text-slate-400 hover:text-white">Limpiar</a>
            @endif
        </form>

        <button @click="newCustomerModal = true" class="px-4 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold text-xs flex items-center gap-2 transition-all shadow-lg shadow-red-600/30 shrink-0 cursor-pointer">
            <i data-lucide="user-plus" class="w-4 h-4"></i>
            <span>Registrar Cliente Manualmente</span>
        </button>
    </div>

    <!-- Tabla de Clientes y Bóvedas -->
    <div class="rounded-3xl bg-[#0f172a] border border-slate-800 shadow-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-800 text-[11px] font-bold text-slate-400 uppercase tracking-wider bg-slate-950">
                        <th class="py-4 px-4">N° Cliente / Nombre</th>
                        <th class="py-4 px-4">Correo de Bóveda</th>
                        <th class="py-4 px-4">WhatsApp / Teléfono</th>
                        <th class="py-4 px-4">Vendedor Responsable</th>
                        <th class="py-4 px-4">Servicios Activos</th>
                        <th class="py-4 px-4 text-center">Estado</th>
                        <th class="py-4 px-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/80 text-slate-200">
                    @forelse($customers as $c)
                    <tr class="hover:bg-slate-800/30 transition-colors">
                        <td class="py-3.5 px-4">
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-xs font-bold px-2 py-0.5 rounded bg-slate-900 border border-slate-800 text-amber-400">
                                    {{ $c->customer_code ?: 'CLI-' . $c->id }}
                                </span>
                                <span class="font-bold text-white text-sm">{{ $c->name }}</span>
                            </div>
                            @if($c->notes)
                            <span class="text-[10px] text-slate-500 truncate block max-w-xs mt-0.5">{{ $c->notes }}</span>
                            @endif
                        </td>

                        <td class="py-3.5 px-4 font-mono text-slate-300">
                            {{ $c->email ?: 'Sin correo' }}
                        </td>

                        <td class="py-3.5 px-4">
                            @if($c->phone)
                            <a href="https://wa.me/{{ $c->clean_phone }}?text=Hola%20*{{ urlencode($c->name) }}*,%20te%20escribimos%20desde%20tu%20panel%20de%20streaming." target="_blank" class="text-emerald-400 hover:text-emerald-300 font-semibold flex items-center gap-1.5 font-mono">
                                <i data-lucide="message-circle" class="w-3.5 h-3.5"></i>
                                <span>{{ $c->phone }}</span>
                            </a>
                            @else
                            <span class="text-slate-500">Sin teléfono</span>
                            @endif
                        </td>

                        <td class="py-3.5 px-4">
                            <span class="text-xs font-mono font-bold text-sky-400">
                                {{ $c->user ? ($c->user->brand_name ?: $c->user->name) : 'Central' }}
                            </span>
                        </td>

                        <td class="py-3.5 px-4">
                            <div class="flex flex-wrap gap-1 max-w-xs">
                                @forelse($c->activeSales as $asale)
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full border bg-slate-900 border-slate-800 text-white flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full" style="background-color: {{ $asale->service->color_hex ?? '#E50914' }}"></span>
                                    <span>{{ $asale->service->name }}</span>
                                </span>
                                @empty
                                <span class="text-[11px] text-slate-500 italic">Sin cuentas activas</span>
                                @endforelse
                            </div>
                        </td>

                        <td class="py-3.5 px-4 text-center">
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $c->status === 'active' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-rose-500/20 text-rose-400 border border-rose-500/30' }}">
                                {{ $c->status === 'active' ? 'ACTIVO' : 'SUSPENDIDO' }}
                            </span>
                        </td>

                        <td class="py-3.5 px-4 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                <!-- Botón Activar Servicio -->
                                <button @click="openActivateModal({{ json_encode($c) }})" class="px-2.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs flex items-center gap-1 shadow transition-all cursor-pointer" title="Activarle una cuenta o pantalla de streaming">
                                    <i data-lucide="zap" class="w-3.5 h-3.5"></i>
                                    <span>Activar</span>
                                </button>

                                <!-- Botón Reset Clave Bóveda -->
                                <button @click="openResetModal({{ json_encode($c) }})" class="p-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-amber-400 transition-colors" title="Cambiar contraseña de su Bóveda">
                                    <i data-lucide="key" class="w-3.5 h-3.5"></i>
                                </button>

                                <!-- Editar Cliente -->
                                <button @click="selectedCustomer = {{ json_encode($c) }}; editCustomerModal = true;" class="p-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 transition-colors" title="Editar cliente">
                                    <i data-lucide="edit-2" class="w-3.5 h-3.5"></i>
                                </button>

                                <!-- Eliminar -->
                                <form method="POST" action="{{ route('customers.destroy', $c) }}" onsubmit="return confirm('¿Eliminar este cliente?');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 rounded-xl text-slate-500 hover:text-rose-400 hover:bg-rose-500/10 transition-colors" title="Eliminar cliente">
                                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-12 text-center text-slate-500">
                            No hay clientes registrados en la plataforma.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($customers->hasPages())
        <div class="p-4 border-t border-slate-800 bg-slate-950">
            {{ $customers->links() }}
        </div>
        @endif
    </div>

    <!-- ======================================================== -->
    <!-- MODAL: ACTIVAR SERVICIO DE STREAMING AL CLIENTE -->
    <!-- ======================================================== -->
    <div x-show="activateModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm" @click.self="activateModal = false">
        <div class="bg-[#0f172a] p-6 rounded-3xl border border-slate-800 shadow-2xl max-w-md w-full space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <div class="flex items-center gap-2 text-emerald-400">
                    <i data-lucide="zap" class="w-5 h-5"></i>
                    <h3 class="font-black text-white text-base">Activar Cuenta de Streaming</h3>
                </div>
                <button @click="activateModal = false" class="text-slate-400 hover:text-white">✕</button>
            </div>

            <p class="text-xs text-slate-400">
                Asigna una pantalla o cuenta oficial del inventario al cliente <strong class="text-white" x-text="selectedCustomer?.name"></strong> (<span class="text-amber-400" x-text="selectedCustomer?.email"></span>). Estará visible en su Bóveda de inmediato.
            </p>

            <form x-bind:action="'/customers/' + (selectedCustomer ? selectedCustomer.id : '') + '/activate'" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Selecciona el Plan / Servicio *</label>
                    <select name="service_plan_id" required class="w-full p-3 rounded-xl bg-slate-900 border border-slate-700 text-xs text-white outline-none focus:border-red-500">
                        @foreach($availablePlans as $p)
                        <option value="{{ $p->id }}">
                            {{ $p->service->name }} — {{ $p->name }} (${{ number_format($p->price, 2) }})
                        </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Precio Cobrado al Cliente ($)</label>
                    <input type="number" step="0.01" name="custom_price" placeholder="Monto convenido o dejar vacío para precio regular" class="w-full p-3 rounded-xl bg-slate-900 border border-slate-700 text-xs font-mono text-white outline-none focus:border-red-500">
                </div>

                <!-- Duración y Consignación -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Duración (Meses) *</label>
                        <select name="months_duration" class="w-full p-2.5 rounded-xl bg-slate-900 border border-slate-700 text-xs text-white outline-none">
                            <option value="1" selected>1 Mes (30d)</option>
                            <option value="2">2 Meses (60d)</option>
                            <option value="3">3 Meses (90d)</option>
                            <option value="6">6 Meses (180d)</option>
                            <option value="12">12 Meses (1 Año)</option>
                        </select>
                    </div>
                    <div class="flex items-center pt-5">
                        <label class="flex items-center gap-2 text-xs text-amber-300 font-bold cursor-pointer">
                            <input type="checkbox" name="is_consignment" value="1" class="rounded bg-slate-900 border-amber-500 text-amber-500 focus:ring-0">
                            <span>📦 Consignación 48h</span>
                        </label>
                    </div>
                </div>

                <div class="p-3 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-[11px] text-amber-300 leading-relaxed space-y-1">
                    <div class="flex items-center gap-1.5 font-bold text-amber-400">
                        <i data-lucide="shield-alert" class="w-3.5 h-3.5"></i>
                        <span>Regla de Entrega Estricta:</span>
                    </div>
                    <p>
                        <strong>No se entregará sin monto en billetera o si no se recibe el pago del proveedor.</strong>
                        Si se activa en consignación o sin saldo suficiente, las credenciales quedan retenidas y solo se desbloquearán en la Bóveda del cliente cuando liquides el costo desde tu saldo.
                    </p>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-slate-800">
                    <button type="button" @click="activateModal = false" class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 text-xs font-bold">Cancelar</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black shadow-lg">Confirmar Activación</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL: RESETEAR CONTRASEÑA DE BÓVEDA -->
    <!-- ======================================================== -->
    <div x-show="resetPasswordModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm" @click.self="resetPasswordModal = false">
        <div class="bg-[#0f172a] p-6 rounded-3xl border border-slate-800 shadow-2xl max-w-md w-full space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <div class="flex items-center gap-2 text-amber-400">
                    <i data-lucide="key" class="w-5 h-5"></i>
                    <h3 class="font-black text-white text-base">Cambiar Clave de Bóveda</h3>
                </div>
                <button @click="resetPasswordModal = false" class="text-slate-400 hover:text-white">✕</button>
            </div>

            <p class="text-xs text-slate-400">
                Establece una nueva clave para que <strong class="text-white" x-text="selectedCustomer?.name"></strong> ingrese a su Bóveda Privada.
            </p>

            <form x-bind:action="'/customers/' + (selectedCustomer ? selectedCustomer.id : '') + '/reset-password'" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Nueva Contraseña</label>
                    <input type="text" name="password" placeholder="Ej: ClaveNueva2026*" required class="w-full p-3 rounded-xl bg-slate-900 border border-slate-700 text-xs font-mono text-white outline-none focus:border-red-500">
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-slate-800">
                    <button type="button" @click="resetPasswordModal = false" class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 text-xs font-bold">Cancelar</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-500 text-white text-xs font-black shadow-lg">Actualizar Clave</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL: REGISTRAR NUEVO CLIENTE MANUAL -->
    <!-- ======================================================== -->
    <div x-show="newCustomerModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm" @click.self="newCustomerModal = false">
        <div class="bg-[#0f172a] p-6 rounded-3xl border border-slate-800 shadow-2xl max-w-lg w-full space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <h3 class="font-black text-white text-base">Registrar Nuevo Cliente</h3>
                <button @click="newCustomerModal = false" class="text-slate-400 hover:text-white">✕</button>
            </div>

            <form method="POST" action="{{ route('customers.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Nombre Completo *</label>
                    <input type="text" name="name" required placeholder="Carlos Gómez" class="w-full p-3 rounded-xl bg-slate-900 border border-slate-700 text-xs text-white outline-none">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Correo Electrónico *</label>
                        <input type="email" name="email" required placeholder="cliente@gmail.com" class="w-full p-3 rounded-xl bg-slate-900 border border-slate-700 text-xs text-white outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-1">WhatsApp / Teléfono *</label>
                        <input type="text" name="phone" required placeholder="+54 9 11 0000-0000" class="w-full p-3 rounded-xl bg-slate-900 border border-slate-700 text-xs text-white outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Contraseña Inicial para su Bóveda (Opcional)</label>
                    <input type="text" name="password" placeholder="Dejar en blanco para generar una automática" class="w-full p-3 rounded-xl bg-slate-900 border border-slate-700 text-xs font-mono text-white outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Notas Internas</label>
                    <textarea name="notes" rows="2" placeholder="Observaciones del cliente..." class="w-full p-3 rounded-xl bg-slate-900 border border-slate-700 text-xs text-white outline-none"></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-slate-800">
                    <button type="button" @click="newCustomerModal = false" class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 text-xs font-bold">Cancelar</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white text-xs font-black shadow-lg">Registrar Cliente</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
