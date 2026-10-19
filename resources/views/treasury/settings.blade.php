@extends('layouts.app')

@section('title', 'Configurar Métodos de Cobro')
@section('page_title', 'Configuración de Tesorería & Cobros')
@section('page_subtitle', 'Cada revendedor o administrador cobra directamente a sus propias cuentas bancarias, Binance y Mercado Pago')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <div class="bg-[#111827] border border-gray-800 rounded-3xl p-6 md:p-8 shadow-xl">
        <form method="POST" action="{{ route('treasury.settings.update') }}" class="space-y-8">
            @csrf

            <!-- Section 1: Mercado Pago -->
            <div>
                <div class="flex items-center gap-3 pb-3 border-b border-gray-800">
                    <div class="w-10 h-10 rounded-xl bg-cyan-500/20 text-cyan-400 flex items-center justify-center font-bold text-lg">
                        💳
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white">Opción A: Pasarelas Automáticas (Mercado Pago)</h3>
                        <p class="text-xs text-gray-400">Las compras por Mercado Pago asignan credenciales y entregan la cuenta inmediatamente sin intervención manual.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-300 uppercase tracking-wider mb-2">
                            Public Key de Mercado Pago
                        </label>
                        <input type="text" name="mp_public_key" value="{{ old('mp_public_key', $user->mp_public_key) }}" placeholder="APP_USR-..."
                            class="w-full px-4 py-2.5 bg-[#0b0f19] border border-gray-800 rounded-xl text-white text-xs font-mono focus:border-red-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-300 uppercase tracking-wider mb-2">
                            Access Token de Mercado Pago
                        </label>
                        <input type="password" name="mp_access_token" value="{{ old('mp_access_token', $user->mp_access_token) }}" placeholder="APP_USR-..."
                            class="w-full px-4 py-2.5 bg-[#0b0f19] border border-gray-800 rounded-xl text-white text-xs font-mono focus:border-red-500">
                    </div>
                </div>
            </div>

            <!-- Section 2: Manual Bank Transfer & Binance -->
            <div>
                <div class="flex items-center gap-3 pb-3 border-b border-gray-800">
                    <div class="w-10 h-10 rounded-xl bg-amber-500/20 text-amber-400 flex items-center justify-center font-bold text-lg">
                        🏦
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white">Opción B: Pagos Manuales (Transferencia Bancaria & Binance USDT)</h3>
                        <p class="text-xs text-gray-400">Tus clientes verán estos datos al seleccionar "Transferencia Bancaria" en el catálogo.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-300 uppercase tracking-wider mb-2">
                            Alias CBU / CVU *
                        </label>
                        <input type="text" name="bank_alias" value="{{ old('bank_alias', $user->bank_alias) }}" placeholder="Ej: TUEMPRESA.PAGOS.MP"
                            class="w-full px-4 py-2.5 bg-[#0b0f19] border border-gray-800 rounded-xl text-white text-xs font-mono focus:border-red-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-300 uppercase tracking-wider mb-2">
                            Nombre del Titular de la Cuenta *
                        </label>
                        <input type="text" name="bank_holder_name" value="{{ old('bank_holder_name', $user->bank_holder_name) }}" placeholder="Ej: Carlos Alberto Gómez"
                            class="w-full px-4 py-2.5 bg-[#0b0f19] border border-gray-800 rounded-xl text-white text-xs focus:border-red-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-300 uppercase tracking-wider mb-2">
                            CBU Bancario (22 dígitos)
                        </label>
                        <input type="text" name="bank_cbu" value="{{ old('bank_cbu', $user->bank_cbu) }}" placeholder="00000031..."
                            class="w-full px-4 py-2.5 bg-[#0b0f19] border border-gray-800 rounded-xl text-white text-xs font-mono focus:border-red-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-300 uppercase tracking-wider mb-2">
                            CVU Virtual (Billetera / Ualá / MP)
                        </label>
                        <input type="text" name="bank_cvu" value="{{ old('bank_cvu', $user->bank_cvu) }}" placeholder="00000079..."
                            class="w-full px-4 py-2.5 bg-[#0b0f19] border border-gray-800 rounded-xl text-white text-xs font-mono focus:border-red-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-300 uppercase tracking-wider mb-2">
                            Binance Pay ID / USDT TRC20 (Opcional)
                        </label>
                        <input type="text" name="binance_id" value="{{ old('binance_id', $user->binance_id) }}" placeholder="Ej: 192837465"
                            class="w-full px-4 py-2.5 bg-[#0b0f19] border border-gray-800 rounded-xl text-white text-xs font-mono focus:border-red-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-300 uppercase tracking-wider mb-2">
                            DNI o CUIT del Titular
                        </label>
                        <input type="text" name="bank_dni" value="{{ old('bank_dni', $user->bank_dni) }}" placeholder="30-12345678-9"
                            class="w-full px-4 py-2.5 bg-[#0b0f19] border border-gray-800 rounded-xl text-white text-xs focus:border-red-500">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-gray-300 uppercase tracking-wider mb-2">
                            Instrucciones Adicionales para el Cliente
                        </label>
                        <textarea name="bank_instructions" rows="3" placeholder="Ej: Por favor adjunta la captura o comprobante donde figure el número de operación..."
                            class="w-full px-4 py-2.5 bg-[#0b0f19] border border-gray-800 rounded-xl text-white text-xs focus:border-red-500">{{ old('bank_instructions', $user->bank_instructions) }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="pt-4 border-t border-gray-800 flex justify-end">
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold text-xs shadow-lg shadow-red-600/30 transition-all flex items-center gap-2">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    <span>Guardar Métodos de Cobro</span>
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
