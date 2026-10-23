@extends('layouts.app')

@section('title', 'Proveedores de Cuentas')
@section('page_title', 'Proveedores de Cuentas Streaming')
@section('page_subtitle', 'Control de mayoristas, calificaciones, canales de contacto y métodos de pago')

@section('content')
<div class="space-y-6" x-data="{
    newSupplierModal: false,
    editSupplierModal: false,
    selectedSupplier: null
}">

    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-sm font-bold text-white uppercase tracking-wider">Mayoristas & Proveedores Registrados</h3>
            <p class="text-xs text-gray-400">Controla la confiabilidad y calidad de las cuentas compradas</p>
        </div>

        <button @click="newSupplierModal = true" class="px-4 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold text-xs flex items-center gap-2 transition-all shadow-lg shadow-red-600/30">
            <i data-lucide="plus-circle" class="w-4 h-4"></i>
            <span>Nuevo Proveedor</span>
        </button>
    </div>

    <!-- Suppliers Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($suppliers as $sup)
        <div class="rounded-2xl bg-[#111827] border border-gray-800 shadow-xl overflow-hidden flex flex-col justify-between hover:border-gray-700 transition-all p-5 space-y-4">
            <div class="flex items-start justify-between">
                <div>
                    <h4 class="text-base font-bold text-white">{{ $sup->name }}</h4>
                    <div class="flex items-center gap-1 mt-1">
                        @for($i = 1; $i <= 5; $i++)
                            <i data-lucide="star" class="w-3.5 h-3.5 {{ $i <= $sup->rating ? 'text-amber-400 fill-amber-400' : 'text-gray-600' }}"></i>
                        @endfor
                        <span class="text-[11px] text-gray-400 ml-1">({{ $sup->rating }}/5)</span>
                    </div>
                </div>

                <div class="flex items-center gap-1">
                    <button @click="selectedSupplier = {{ json_encode($sup) }}; editSupplierModal = true;" class="p-1.5 rounded-lg bg-gray-800 hover:bg-gray-700 text-gray-300">
                        <i data-lucide="edit-2" class="w-3.5 h-3.5"></i>
                    </button>
                    <form method="POST" action="{{ route('suppliers.destroy', $sup) }}" onsubmit="return confirm('¿Eliminar este proveedor?');" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="p-1.5 rounded-lg text-gray-400 hover:text-rose-400">
                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Datos de Contacto -->
            <div class="space-y-1.5 text-xs text-gray-300 pt-2 border-t border-gray-800">
                @if($sup->whatsapp)
                <div class="flex items-center gap-2">
                    <i data-lucide="message-circle" class="w-4 h-4 text-emerald-400 shrink-0"></i>
                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $sup->whatsapp) }}" target="_blank" class="hover:text-emerald-400 font-mono">{{ $sup->whatsapp }}</a>
                </div>
                @endif
                @if($sup->telegram)
                <div class="flex items-center gap-2">
                    <i data-lucide="send" class="w-4 h-4 text-sky-400 shrink-0"></i>
                    <span class="font-mono text-sky-400">{{ $sup->telegram }}</span>
                </div>
                @endif
                @if($sup->payment_info)
                <div class="text-[11px] text-gray-400 pt-1">
                    <strong class="text-gray-300">Pago:</strong> {{ $sup->payment_info }}
                </div>
                @endif
            </div>

            <!-- Métricas de Cuentas Suministradas -->
            <div class="pt-3 border-t border-gray-800/80 grid grid-cols-3 gap-2 text-center text-xs">
                <div class="bg-[#0b0f19] p-2 rounded-xl">
                    <span class="text-[10px] text-gray-400 block">Compradas</span>
                    <span class="font-bold text-white">{{ $sup->total_accounts_count }}</span>
                </div>
                <div class="bg-[#0b0f19] p-2 rounded-xl">
                    <span class="text-[10px] text-emerald-400 block">Activas</span>
                    <span class="font-bold text-emerald-400">{{ $sup->active_accounts_count }}</span>
                </div>
                <div class="bg-[#0b0f19] p-2 rounded-xl">
                    <span class="text-[10px] text-rose-400 block">Caídas</span>
                    <span class="font-bold text-rose-400">{{ $sup->dropped_accounts_count }}</span>
                </div>
            </div>

            <!-- Estado en Catálogo y Acciones de Convenio -->
            <div class="pt-3 border-t border-gray-800 space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] uppercase font-bold text-gray-400">Oferta en Catálogo:</span>
                    <form method="POST" action="{{ route('suppliers.toggle_status', $sup) }}" class="inline">
                        @csrf
                        <button type="submit" class="px-2 py-0.5 rounded-full text-[10px] font-bold border transition-all cursor-pointer {{ $sup->status === 'active' ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30 hover:bg-emerald-500/30' : 'bg-gray-800 text-gray-400 border-gray-700 hover:bg-gray-700' }}">
                            {{ $sup->status === 'active' ? '🟢 Activo en Catálogo' : '⚪ Desactivado' }}
                        </button>
                    </form>
                </div>

                @if($sup->whatsapp)
                @php
                    $supAgreement = "📋 *CONVENIO ADMINISTRATIVO - CSTREAME*\n\n"
                        . "Estimado proveedor *" . $sup->name . "*:\n"
                        . "Puntos puntuales del acuerdo administrativo vigente:\n\n"
                        . "1️⃣ *PANELES & TIENDAS:*\n"
                        . "• Activación y gestión de paneles y tiendas mediante créditos.\n\n"
                        . "2️⃣ *CERO COMISIÓN EN SERVICIOS:*\n"
                        . "• La administración NO cobra comisión ni porcentaje sobre tus cuentas o servicios (0%).\n\n"
                        . "3️⃣ *ENTREGAS & SALDO:*\n"
                        . "• Emisión contra saldo disponible o transferencia acreditada (sin cuentas fiadas).\n\n"
                        . "4️⃣ *INDEPENDENCIA COMERCIAL:*\n"
                        . "• Cada parte opera con total libertad en sus precios, servicios y catálogo.\n\n"
                        . "Confirmar recepción para mantener la operatividad activa.\n\n"
                        . "— Administración Central CStreame";
                @endphp
                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $sup->whatsapp) }}?text={{ urlencode($supAgreement) }}" target="_blank" class="w-full py-2 px-3 rounded-xl bg-emerald-600/20 hover:bg-emerald-600 text-emerald-300 hover:text-white border border-emerald-500/30 text-xs font-bold flex items-center justify-center gap-2 transition-all cursor-pointer shadow-sm">
                    <i data-lucide="message-circle" class="w-3.5 h-3.5"></i>
                    <span>Notificar Convenio por WhatsApp</span>
                </a>
                @endif
            </div>
        </div>
        @empty
        <div class="col-span-3 p-12 text-center text-gray-400 rounded-2xl bg-[#111827] border border-gray-800">
            No tienes proveedores registrados. Haz clic en "Nuevo Proveedor".
        </div>
        @endforelse
    </div>

    <!-- ========================================== -->
    <!-- MODAL: NUEVO PROVEEDOR -->
    <!-- ========================================== -->
    <div x-show="newSupplierModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
        <div @click.away="newSupplierModal = false" class="w-full max-w-md bg-[#111827] border border-gray-800 rounded-3xl shadow-2xl p-6">
            <h3 class="text-base font-bold text-white mb-1">Registrar Nuevo Proveedor</h3>
            <p class="text-xs text-gray-400 mb-4">Guarda contactos de mayoristas para reposición y compras</p>

            <form method="POST" action="{{ route('suppliers.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Nombre o Razón Social *</label>
                    <input type="text" name="name" placeholder="Ej: Mayorista Digital VIP" required class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white focus:border-red-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">WhatsApp</label>
                        <input type="text" name="whatsapp" placeholder="+54911..." class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white focus:border-red-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Telegram</label>
                        <input type="text" name="telegram" placeholder="@usuario_tg" class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white focus:border-red-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Email</label>
                        <input type="email" name="email" placeholder="ventas@mayorista.com" class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white focus:border-red-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Calificación</label>
                        <select name="rating" class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white font-bold text-amber-400">
                            <option value="5">⭐⭐⭐⭐⭐ (5 - Excelente)</option>
                            <option value="4">⭐⭐⭐⭐ (4 - Bueno)</option>
                            <option value="3">⭐⭐⭐ (3 - Regular)</option>
                            <option value="2">⭐⭐ (2 - Inestable)</option>
                            <option value="1">⭐ (1 - Malo)</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Métodos de Pago Aceptados</label>
                    <input type="text" name="payment_info" placeholder="Ej: Binance USDT, Transferencia Galicia, PayPal" class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white focus:border-red-500">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Notas Internas</label>
                    <textarea name="notes" rows="2" placeholder="Tiempos de garantía, horarios de atención..." class="w-full px-3.5 py-2 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white focus:border-red-500"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-800">
                    <button type="button" @click="newSupplierModal = false" class="px-4 py-2 text-xs text-gray-400 hover:text-white">Cancelar</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold text-xs shadow-lg shadow-red-600/30">Guardar Proveedor</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL: EDITAR PROVEEDOR -->
    <!-- ========================================== -->
    <div x-show="editSupplierModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
        <div @click.away="editSupplierModal = false" class="w-full max-w-md bg-[#111827] border border-gray-800 rounded-3xl shadow-2xl p-6">
            <h3 class="text-base font-bold text-white mb-1">Editar Proveedor</h3>
            <p class="text-xs text-gray-400 mb-4">Actualizar datos de contacto y calificación</p>

            <form :action="'/suppliers/' + selectedSupplier?.id" method="POST" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Nombre</label>
                    <input type="text" name="name" :value="selectedSupplier?.name" required class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white focus:border-red-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">WhatsApp</label>
                        <input type="text" name="whatsapp" :value="selectedSupplier?.whatsapp" class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white focus:border-red-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Calificación</label>
                        <select name="rating" class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white">
                            <option value="5" :selected="selectedSupplier?.rating == 5">5 Estrellas</option>
                            <option value="4" :selected="selectedSupplier?.rating == 4">4 Estrellas</option>
                            <option value="3" :selected="selectedSupplier?.rating == 3">3 Estrellas</option>
                            <option value="2" :selected="selectedSupplier?.rating == 2">2 Estrellas</option>
                            <option value="1" :selected="selectedSupplier?.rating == 1">1 Estrella</option>
                        </select>
                    </div>
                </div>

                <input type="hidden" name="status" value="active">

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-800">
                    <button type="button" @click="editSupplierModal = false" class="px-4 py-2 text-xs text-gray-400 hover:text-white">Cancelar</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold text-xs">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
