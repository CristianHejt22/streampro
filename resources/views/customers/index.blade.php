@extends('layouts.app')

@section('title', 'Clientes CRM')
@section('page_title', 'Agenda de Clientes CRM')
@section('page_subtitle', 'Directorio de compradores, historial de compras, WhatsApp directo y notas')

@section('content')
<div class="space-y-6" x-data="{
    newCustomerModal: false,
    editCustomerModal: false,
    selectedCustomer: null
}">

    <!-- Top Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <!-- Search -->
        <form method="GET" action="{{ route('customers.index') }}" class="flex items-center gap-2 flex-1 max-w-md">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por nombre, teléfono o email..." class="w-full px-3.5 py-2 rounded-xl bg-[#111827] border border-gray-800 text-xs text-white placeholder-gray-400 focus:outline-none focus:border-red-500">
            <button type="submit" class="px-4 py-2 rounded-xl bg-gray-800 hover:bg-gray-700 text-white text-xs font-semibold">Buscar</button>
            @if(request('search'))
            <a href="{{ route('customers.index') }}" class="text-xs text-gray-400 hover:text-white">Limpiar</a>
            @endif
        </form>

        <button @click="newCustomerModal = true" class="px-4 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold text-xs flex items-center gap-2 transition-all shadow-lg shadow-red-600/30 shrink-0">
            <i data-lucide="user-plus" class="w-4 h-4"></i>
            <span>Nuevo Cliente</span>
        </button>
    </div>

    <!-- Customers Table -->
    <div class="rounded-2xl bg-[#111827] border border-gray-800 shadow-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-gray-800 text-[11px] font-bold text-gray-400 uppercase tracking-wider bg-[#0d1322]">
                        <th class="py-3.5 px-4">Cliente</th>
                        <th class="py-3.5 px-4">WhatsApp / Teléfono</th>
                        <th class="py-3.5 px-4">Email</th>
                        <th class="py-3.5 px-4 text-center">Compras Totales</th>
                        <th class="py-3.5 px-4 text-center">Servicios Activos</th>
                        <th class="py-3.5 px-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800/60 text-xs text-gray-200">
                    @forelse($customers as $c)
                    <tr class="hover:bg-gray-800/30 transition-colors">
                        <td class="py-3 px-4 font-bold text-white">
                            {{ $c->name }}
                            @if($c->notes)
                            <span class="block text-[10px] text-gray-400 font-normal truncate max-w-xs">{{ $c->notes }}</span>
                            @endif
                        </td>
                        <td class="py-3 px-4">
                            @if($c->phone)
                            <a href="https://wa.me/{{ $c->clean_phone }}" target="_blank" class="text-emerald-400 hover:text-emerald-300 font-semibold flex items-center gap-1.5">
                                <i data-lucide="message-circle" class="w-3.5 h-3.5"></i>
                                <span>{{ $c->phone }}</span>
                            </a>
                            @else
                            <span class="text-gray-400">Sin teléfono</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-gray-300">{{ $c->email ?: 'N/A' }}</td>
                        <td class="py-3 px-4 text-center font-bold text-white">{{ $c->sales_count }}</td>
                        <td class="py-3 px-4 text-center">
                            <span class="px-2 py-0.5 rounded-full {{ $c->active_sales_count > 0 ? 'bg-emerald-500/20 text-emerald-400 font-bold border border-emerald-500/30' : 'bg-gray-800 text-gray-400' }}">
                                {{ $c->active_sales_count }} activas
                            </span>
                        </td>
                        <td class="py-3 px-4 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                <button @click="selectedCustomer = {{ json_encode($c) }}; editCustomerModal = true;" class="p-2 rounded-lg bg-gray-800 hover:bg-gray-700 text-gray-300">
                                    <i data-lucide="edit-2" class="w-3.5 h-3.5"></i>
                                </button>
                                <form method="POST" action="{{ route('customers.destroy', $c) }}" onsubmit="return confirm('¿Eliminar este cliente?');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 rounded-lg text-gray-400 hover:text-rose-400 hover:bg-rose-500/10">
                                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-12 text-center text-gray-400">
                            No hay clientes registrados en la agenda CRM.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($customers->hasPages())
        <div class="p-4 border-t border-gray-800 bg-[#0d1322]">
            {{ $customers->links() }}
        </div>
        @endif
    </div>

    <!-- ========================================== -->
    <!-- MODAL: NUEVO CLIENTE -->
    <!-- ========================================== -->
    <div x-show="newCustomerModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
        <div @click.away="newCustomerModal = false" class="w-full max-w-md bg-[#111827] border border-gray-800 rounded-3xl shadow-2xl p-6">
            <h3 class="text-base font-bold text-white mb-1">Registrar Nuevo Cliente</h3>
            <p class="text-xs text-gray-400 mb-4">Guarda los datos de contacto para envíos de WhatsApp y avisos</p>

            <form method="POST" action="{{ route('customers.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Nombre Completo *</label>
                    <input type="text" name="name" placeholder="Ej: Fernando López" required class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white focus:border-red-500">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">WhatsApp / Teléfono *</label>
                    <input type="text" name="phone" placeholder="Ej: +5491144556677 (con código de país)" required class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white focus:border-red-500">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Correo Electrónico (Opcional)</label>
                    <input type="email" name="email" placeholder="cliente@ejemplo.com" class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white focus:border-red-500">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Notas / Preferencias</label>
                    <textarea name="notes" rows="2" placeholder="Ej: Paga los días 10 por transferencia..." class="w-full px-3.5 py-2 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white focus:border-red-500"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-800">
                    <button type="button" @click="newCustomerModal = false" class="px-4 py-2 text-xs text-gray-400 hover:text-white">Cancelar</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold text-xs shadow-lg shadow-red-600/30">Guardar Cliente</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL: EDITAR CLIENTE -->
    <!-- ========================================== -->
    <div x-show="editCustomerModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
        <div @click.away="editCustomerModal = false" class="w-full max-w-md bg-[#111827] border border-gray-800 rounded-3xl shadow-2xl p-6">
            <h3 class="text-base font-bold text-white mb-1">Editar Cliente</h3>
            <p class="text-xs text-gray-400 mb-4">Actualizar datos de contacto y notas</p>

            <form :action="'/customers/' + selectedCustomer?.id" method="POST" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Nombre</label>
                    <input type="text" name="name" :value="selectedCustomer?.name" required class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white focus:border-red-500">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">WhatsApp / Teléfono</label>
                    <input type="text" name="phone" :value="selectedCustomer?.phone" required class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white focus:border-red-500">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Email</label>
                    <input type="email" name="email" :value="selectedCustomer?.email" class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white focus:border-red-500">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Notas</label>
                    <textarea name="notes" rows="2" :value="selectedCustomer?.notes" class="w-full px-3.5 py-2 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white focus:border-red-500"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-800">
                    <button type="button" @click="editCustomerModal = false" class="px-4 py-2 text-xs text-gray-400 hover:text-white">Cancelar</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold text-xs">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
