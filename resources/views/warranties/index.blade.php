@extends('layouts.app')

@section('title', 'Garantías & Caídas')
@section('page_title', 'Control de Garantías & Caídas')
@section('page_subtitle', 'Historial de reclamos, reposiciones de cuentas y auditoría de garantías')

@section('content')
<div class="space-y-6" x-data="{
    resolveModal: false,
    selectedWarranty: null
}">

    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-sm font-bold text-white uppercase tracking-wider">Tickets de Garantía y Reposición</h3>
            <p class="text-xs text-gray-400">Control de caídas de cuentas, modificaciones de contraseñas y soluciones</p>
        </div>
    </div>

    <!-- Warranties Table -->
    <div class="rounded-2xl bg-[#111827] border border-gray-800 shadow-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-gray-800 text-[11px] font-bold text-gray-400 uppercase tracking-wider bg-[#0d1322]">
                        <th class="py-3.5 px-4">Fecha</th>
                        <th class="py-3.5 px-4">Venta Afectada</th>
                        <th class="py-3.5 px-4">Cliente</th>
                        <th class="py-3.5 px-4">Motivo / Problema</th>
                        <th class="py-3.5 px-4">Estado</th>
                        <th class="py-3.5 px-4">Resolución</th>
                        <th class="py-3.5 px-4 text-right">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800/60 text-xs text-gray-200">
                    @forelse($warranties as $w)
                    <tr class="hover:bg-gray-800/30 transition-colors">
                        <td class="py-3 px-4 text-gray-400 font-mono text-[11px]">
                            {{ $w->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="py-3 px-4">
                            <span class="font-bold text-white">{{ $w->sale->sale_code }}</span>
                            <span class="text-[10px] block text-cyan-400">{{ $w->sale->service->name }}</span>
                        </td>
                        <td class="py-3 px-4 font-bold text-white">
                            {{ $w->sale->customer->name }}
                        </td>
                        <td class="py-3 px-4">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/20 text-amber-400 border border-amber-500/30 uppercase">
                                {{ str_replace('_', ' ', $w->issue_type) }}
                            </span>
                            <p class="text-[11px] text-gray-300 mt-1 max-w-xs">{{ $w->description }}</p>
                        </td>
                        <td class="py-3 px-4">
                            @if($w->status === 'pending')
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-400 border border-amber-500/30 animate-pulse">
                                PENDIENTE
                            </span>
                            @elseif($w->status === 'resolved')
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                                RESUELTO
                            </span>
                            @else
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/20 text-rose-400 border border-rose-500/30">
                                RECHAZADO
                            </span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-[11px] text-gray-400">
                            {{ $w->resolution_notes ?: 'Aún sin resolución' }}
                        </td>
                        <td class="py-3 px-4 text-right">
                            @if($w->status === 'pending')
                            <button @click="selectedWarranty = {{ json_encode($w->load(['sale.service'])) }}; resolveModal = true;" class="px-3 py-1.5 rounded-lg bg-red-600 hover:bg-red-500 text-white font-bold text-xs shadow-md shadow-red-600/20">
                                Resolver
                            </button>
                            @else
                            <span class="text-[11px] text-gray-400">Completado</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-12 text-center text-gray-400">
                            No hay tickets de garantía registrados. ¡Cuentas operando con normalidad!
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($warranties->hasPages())
        <div class="p-4 border-t border-gray-800 bg-[#0d1322]">
            {{ $warranties->links() }}
        </div>
        @endif
    </div>

    <!-- ========================================== -->
    <!-- MODAL: RESOLVER GARANTÍA -->
    <!-- ========================================== -->
    <div x-show="resolveModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
        <div @click.away="resolveModal = false" class="w-full max-w-md bg-[#111827] border border-gray-800 rounded-3xl shadow-2xl p-6">
            <h3 class="text-base font-bold text-white mb-1">Resolver Garantía</h3>
            <p class="text-xs text-gray-400 mb-4">Venta: <span class="font-mono text-cyan-400" x-text="selectedWarranty?.sale?.sale_code"></span></p>

            <form :action="'/warranties/' + selectedWarranty?.id + '/resolve'" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Acción a Tomar *</label>
                    <select name="action_type" required class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white focus:border-red-500 font-semibold">
                        <option value="replace_stock">Reemplazar automáticamente con nueva cuenta del stock libre</option>
                        <option value="manual_credentials">Se restableció la contraseña / Solución manual</option>
                        <option value="reject">Rechazar ticket (No corresponde garantía)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Notas de la Resolución *</label>
                    <textarea name="resolution_notes" rows="3" placeholder="Ej: Se asignó nueva pantalla del stock..." required class="w-full px-3.5 py-2 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white focus:border-red-500"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-800">
                    <button type="button" @click="resolveModal = false" class="px-4 py-2 text-xs text-gray-400 hover:text-white">Cancelar</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold text-xs shadow-lg shadow-red-600/30">Aplicar Resolución</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
