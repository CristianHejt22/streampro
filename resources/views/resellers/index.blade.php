@extends('layouts.app')

@section('title', 'Revendedores & Billetera')
@section('page_title', 'Generador de Revendedores & Saldo')
@section('page_subtitle', 'Administración de red de distribuidores, billetera prepago y enlaces referidos')

@section('content')
<div class="space-y-6" x-data="{
    newResellerModal: false,
    rechargeModal: false,
    resetPassModal: false,
    selectedReseller: null
}">

    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-sm font-bold text-white uppercase tracking-wider">Red de Distribuidores Activos</h3>
            <p class="text-xs text-gray-400">Control de saldo en billetera para emisión autónoma de cuentas streaming</p>
        </div>

        <button @click="newResellerModal = true" class="px-4 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold text-xs flex items-center gap-2 transition-all shadow-lg shadow-red-600/30">
            <i data-lucide="user-plus" class="w-4 h-4"></i>
            <span>Nuevo Revendedor</span>
        </button>
    </div>

    <!-- Resellers Table -->
    <div class="rounded-2xl bg-[#111827] border border-gray-800 shadow-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-gray-800 text-[11px] font-bold text-gray-400 uppercase tracking-wider bg-[#0d1322]">
                        <th class="py-3.5 px-4">Revendedor / Marca</th>
                        <th class="py-3.5 px-4">Contacto</th>
                        <th class="py-3.5 px-4">Plan / Nivel</th>
                        <th class="py-3.5 px-4">Saldo en Billetera</th>
                        <th class="py-3.5 px-4 text-center">Ventas Emitidas</th>
                        <th class="py-3.5 px-4">Código Referido</th>
                        <th class="py-3.5 px-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800/60 text-xs text-gray-200">
                    @forelse($resellers as $res)
                    <tr class="hover:bg-gray-800/30 transition-colors">
                        <td class="py-3 px-4">
                            <span class="font-bold text-white text-sm block">{{ $res->name }}</span>
                            <span class="text-[11px] text-gray-400">{{ $res->brand_name }}</span>
                        </td>
                        <td class="py-3 px-4">
                            <div class="text-gray-300">{{ $res->email }}</div>
                            @if($res->phone)
                            <div class="text-[11px] text-emerald-400 font-mono">{{ $res->phone }}</div>
                            @endif
                        </td>
                        <td class="py-3 px-4">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-cyan-500/20 text-cyan-400 border border-cyan-500/30 uppercase">
                                {{ $res->reseller_plan }}
                            </span>
                        </td>
                        <td class="py-3 px-4">
                            <span class="text-base font-black text-emerald-400">${{ number_format($res->balance, 2) }}</span>
                            <button @click="selectedReseller = {{ json_encode($res) }}; rechargeModal = true;" class="text-[10px] text-cyan-400 hover:underline block font-semibold">
                                + Recargar Saldo
                            </button>
                        </td>
                        <td class="py-3 px-4 text-center font-bold text-white">
                            {{ $res->sales_count }}
                        </td>
                        <td class="py-3 px-4">
                            <code class="px-2 py-0.5 rounded bg-black/50 border border-gray-800 text-red-400 font-mono text-[11px]">
                                {{ $res->referral_code }}
                            </code>
                        </td>
                        <td class="py-3 px-4 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                <button @click="selectedReseller = {{ json_encode($res) }}; rechargeModal = true;" title="Acreditar Saldo" class="p-2 rounded-lg bg-emerald-600/20 text-emerald-400 hover:bg-emerald-600 hover:text-white transition-all">
                                    <i data-lucide="plus-circle" class="w-3.5 h-3.5"></i>
                                </button>
                                <button @click="selectedReseller = {{ json_encode($res) }}; resetPassModal = true;" title="Resetear Contraseña" class="p-2 rounded-lg bg-gray-800 text-gray-300 hover:bg-gray-700">
                                    <i data-lucide="key" class="w-3.5 h-3.5"></i>
                                </button>
                                <form method="POST" action="{{ route('resellers.toggle', $res) }}" class="inline">
                                    @csrf
                                    <button type="submit" title="{{ $res->status === 'active' ? 'Suspender revendedor' : 'Reactivar revendedor' }}" class="p-2 rounded-lg {{ $res->status === 'active' ? 'text-amber-400 hover:bg-amber-500/10' : 'text-emerald-400 hover:bg-emerald-500/10' }}">
                                        <i data-lucide="power" class="w-3.5 h-3.5"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-12 text-center text-gray-400">
                            No hay revendedores registrados todavía.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($resellers->hasPages())
        <div class="p-4 border-t border-gray-800 bg-[#0d1322]">
            {{ $resellers->links() }}
        </div>
        @endif
    </div>

    <!-- ========================================== -->
    <!-- MODAL: NUEVO REVENDEDOR -->
    <!-- ========================================== -->
    <div x-show="newResellerModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
        <div @click.away="newResellerModal = false" class="w-full max-w-md bg-[#111827] border border-gray-800 rounded-3xl shadow-2xl p-6">
            <h3 class="text-base font-bold text-white mb-1">Registrar Nuevo Revendedor</h3>
            <p class="text-xs text-gray-400 mb-4">Crea una cuenta con acceso al panel y asigna saldo inicial</p>

            <form method="POST" action="{{ route('resellers.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Nombre Completo *</label>
                    <input type="text" name="name" placeholder="Ej: Marcos Gabriel Torres" required class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white focus:border-red-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Email de Acceso *</label>
                        <input type="email" name="email" placeholder="revendedor@gmail.com" required class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white focus:border-red-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Contraseña *</label>
                        <input type="password" name="password" placeholder="Mínimo 6 caracteres" required class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white focus:border-red-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Plan Revendedor</label>
                        <select name="reseller_plan" class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white">
                            <option value="standard">Standard</option>
                            <option value="vip" selected>VIP (Marca Blanca)</option>
                            <option value="mayorista">Mayorista Pro</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Saldo Inicial ($)</label>
                        <input type="number" step="0.01" name="initial_balance" value="50.00" class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white font-bold text-emerald-400">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Nombre Comercial de Marca Blanca</label>
                    <input type="text" name="brand_name" placeholder="Ej: Marcos Streaming VIP" class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white focus:border-red-500">
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-800">
                    <button type="button" @click="newResellerModal = false" class="px-4 py-2 text-xs text-gray-400 hover:text-white">Cancelar</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold text-xs shadow-lg shadow-red-600/30">Crear Revendedor</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL: RECARGAR SALDO -->
    <!-- ========================================== -->
    <div x-show="rechargeModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
        <div @click.away="rechargeModal = false" class="w-full max-w-md bg-[#111827] border border-gray-800 rounded-3xl shadow-2xl p-6">
            <h3 class="text-base font-bold text-white mb-1">Recargar Saldo de Billetera</h3>
            <p class="text-xs text-gray-400 mb-4">Acreditar saldo prepago a: <span class="text-white font-bold" x-text="selectedReseller?.name"></span></p>

            <form :action="'/resellers/' + selectedReseller?.id + '/recharge'" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-emerald-400 mb-1">Monto a Acreditar ($) *</label>
                    <input type="number" step="0.01" name="amount" placeholder="100.00" required class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-base text-white font-black text-emerald-400">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Motivo / Comprobante de Pago</label>
                    <input type="text" name="notes" placeholder="Ej: Transferencia bancaria recibida #9944" class="w-full px-3.5 py-2 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white">
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-800">
                    <button type="button" @click="rechargeModal = false" class="px-4 py-2 text-xs text-gray-400 hover:text-white">Cancelar</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-lg shadow-emerald-600/30">Acreditar Saldo</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL: RESET PASSWORD -->
    <!-- ========================================== -->
    <div x-show="resetPassModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
        <div @click.away="resetPassModal = false" class="w-full max-w-md bg-[#111827] border border-gray-800 rounded-3xl shadow-2xl p-6">
            <h3 class="text-base font-bold text-white mb-1">Resetear Contraseña</h3>
            <p class="text-xs text-gray-400 mb-4">Revendedor: <span class="text-white font-bold" x-text="selectedReseller?.name"></span></p>

            <form :action="'/resellers/' + selectedReseller?.id + '/reset-password'" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Nueva Contraseña *</label>
                    <input type="password" name="new_password" placeholder="Mínimo 6 caracteres" required class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white">
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-800">
                    <button type="button" @click="resetPassModal = false" class="px-4 py-2 text-xs text-gray-400 hover:text-white">Cancelar</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold text-xs">Actualizar Clave</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
