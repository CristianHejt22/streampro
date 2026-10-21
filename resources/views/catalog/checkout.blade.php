@extends('layouts.public')

@section('title', 'Finalizar Compra - ' . $plan->name)

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 py-10" x-data="{
    paymentMethod: 'bank_transfer',
    receiptFileName: ''
}">

    <div class="mb-6">
        <a href="{{ route('catalog.index', ['ref' => $seller->referral_code]) }}" class="inline-flex items-center gap-1.5 text-xs text-gray-400 hover:text-white transition-colors">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            <span>Volver al Catálogo</span>
        </a>
    </div>

    <div class="bg-[#111827] border border-gray-800 rounded-3xl shadow-2xl overflow-hidden">
        <!-- Order Header Summary -->
        <div class="p-6 border-b border-gray-800 bg-[#0d1322] flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center font-black text-white text-lg shadow-lg" style="background-color: {{ $plan->service->color_hex }}">
                    {{ substr($plan->service->name, 0, 2) }}
                </div>
                <div>
                    <h2 class="text-base font-bold text-white">{{ $plan->service->name }}</h2>
                    <p class="text-xs text-gray-400">{{ $plan->name }} · {{ $plan->duration_days }} días</p>
                </div>
            </div>
            <div class="text-right">
                <span class="text-xs text-gray-400 block">Total a Pagar</span>
                <span class="text-2xl font-black text-emerald-400">${{ number_format($plan->price, 2) }}</span>
            </div>
        </div>

        <form method="POST" action="{{ route('catalog.checkout.process', $plan) }}" enctype="multipart/form-data" class="p-6 md:p-8 space-y-6">
            @csrf
            <input type="hidden" name="seller_id" value="{{ $seller->id }}">

            <!-- Datos del Cliente -->
            <div class="space-y-4">
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider flex items-center gap-2">
                    <i data-lucide="user" class="w-4 h-4 text-red-500"></i>
                    <span>Tus Datos de Contacto (Para Envío de Credenciales)</span>
                </h3>

                <div>
                    <label class="block text-xs text-gray-400 mb-1">Nombre y Apellido *</label>
                    <input type="text" name="customer_name" placeholder="Ej: Juan Manuel Pérez" required class="w-full px-4 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white focus:outline-none focus:border-red-500">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Número de WhatsApp * (Con código de país)</label>
                        <input type="text" name="customer_phone" placeholder="Ej: +5491144556677" required class="w-full px-4 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white focus:outline-none focus:border-red-500 font-mono">
                        <span class="text-[10px] text-gray-400 mt-1 block">Aquí recibirás el usuario, contraseña y PIN de tu perfil.</span>
                    </div>

                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Correo Electrónico (Opcional)</label>
                        <input type="email" name="customer_email" placeholder="tu.correo@gmail.com" class="w-full px-4 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white focus:outline-none focus:border-red-500">
                    </div>
                </div>

                <!-- PIN de Seguridad de la Bóveda (Ajuste en la Primera Compra) -->
                <div class="p-4 rounded-2xl bg-[#0b0f19] border border-amber-500/30 space-y-2">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-bold text-amber-400 uppercase tracking-wider flex items-center gap-1.5">
                            <i data-lucide="key" class="w-4 h-4"></i>
                            <span>PIN Personal de Bóveda (4 Dígitos) *</span>
                        </label>
                        <span class="text-[10px] font-mono text-amber-300 font-bold px-2 py-0.5 rounded-full bg-amber-500/10 border border-amber-500/20">
                            Llave Privada
                        </span>
                    </div>

                    <input type="password" maxlength="4" pattern="[0-9]{4}" inputmode="numeric" name="boveda_pin" placeholder="Ej: 1234" required class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-sm font-mono text-center tracking-[0.5em] text-white focus:outline-none focus:border-amber-400 font-black">

                    <p class="text-[11px] text-slate-300 leading-relaxed">
                        🔒 <strong>¿Para qué sirve este PIN que debes recordar?</strong>
                        Este PIN de 4 dígitos es tu <strong>clave privada de desbloqueo para la Bóveda Cifrada</strong>. Te servirá para consultar y revelar tus contraseñas, usuario y perfiles asignados en cualquier celular o PC, o cuando tu dispositivo no use biometría. <strong>Guárdalo y recuérdalo siempre</strong> para tus futuras consultas y renovaciones.
                    </p>
                </div>
            </div>

            <!-- Selección de Método de Pago -->
            <div class="space-y-4 pt-4 border-t border-gray-800">
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider flex items-center gap-2">
                    <i data-lucide="credit-card" class="w-4 h-4 text-red-500"></i>
                    <span>Selecciona tu Método de Pago</span>
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label class="p-4 rounded-2xl border cursor-pointer transition-all flex items-center gap-3" :class="paymentMethod === 'bank_transfer' ? 'bg-red-950/20 border-red-500 text-white' : 'bg-[#0b0f19] border-gray-800 text-gray-400 hover:border-gray-700'">
                        <input type="radio" name="payment_method" value="bank_transfer" x-model="paymentMethod" class="text-red-600 focus:ring-red-500">
                        <div>
                            <span class="text-xs font-bold block text-white">Transferencia / Billetera</span>
                            <span class="text-[11px] text-gray-400">Alias, CBU, CVU o Binance</span>
                        </div>
                    </label>

                    <label class="p-4 rounded-2xl border cursor-pointer transition-all flex items-center gap-3" :class="paymentMethod === 'mercadopago' ? 'bg-cyan-950/20 border-cyan-500 text-white' : 'bg-[#0b0f19] border-gray-800 text-gray-400 hover:border-gray-700'">
                        <input type="radio" name="payment_method" value="mercadopago" x-model="paymentMethod" class="text-cyan-600 focus:ring-cyan-500">
                        <div>
                            <span class="text-xs font-bold block text-white">Mercado Pago</span>
                            <span class="text-[11px] text-gray-400">Tarjetas y acreditación inmediata</span>
                        </div>
                    </label>
                </div>

                <!-- Datos Bancarios & Subida de Comprobante -->
                <div x-show="paymentMethod === 'bank_transfer'" class="p-5 rounded-2xl bg-[#0b0f19] border border-gray-800 space-y-4">
                    <div class="text-xs font-bold text-white mb-2 flex items-center gap-2">
                        <i data-lucide="info" class="w-4 h-4 text-amber-400"></i>
                        <span>Datos para realizar tu transferencia:</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs bg-black/40 p-4 rounded-xl border border-gray-800">
                        @if($seller->bank_alias)
                        <div>
                            <span class="text-[10px] text-gray-400 block uppercase">Alias CBU / CVU:</span>
                            <span class="font-mono font-bold text-emerald-400 text-sm select-all">{{ $seller->bank_alias }}</span>
                        </div>
                        @endif

                        @if($seller->bank_holder_name)
                        <div>
                            <span class="text-[10px] text-gray-400 block uppercase">Titular:</span>
                            <span class="font-bold text-white">{{ $seller->bank_holder_name }}</span>
                        </div>
                        @endif

                        @if($seller->bank_cvu)
                        <div>
                            <span class="text-[10px] text-gray-400 block uppercase">CVU:</span>
                            <span class="font-mono text-gray-300 text-xs select-all">{{ $seller->bank_cvu }}</span>
                        </div>
                        @endif

                        @if($seller->binance_id)
                        <div>
                            <span class="text-[10px] text-gray-400 block uppercase">Binance Pay ID:</span>
                            <span class="font-mono text-amber-400 font-bold select-all">{{ $seller->binance_id }}</span>
                        </div>
                        @endif
                    </div>

                    @if($seller->bank_instructions)
                    <p class="text-[11px] text-gray-400 italic">{{ $seller->bank_instructions }}</p>
                    @endif

                    <!-- Input Subir Comprobante (Mini Nube) -->
                    <div>
                        <label class="block text-xs font-bold text-white mb-1">Adjuntar Comprobante de Transferencia *</label>
                        <div class="relative border-2 border-dashed border-gray-800 hover:border-red-500/50 rounded-2xl p-4 text-center cursor-pointer transition-all bg-black/20">
                            <input type="file" name="receipt" accept="image/*,.pdf" @change="receiptFileName = $event.target.files[0]?.name" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                            <i data-lucide="upload-cloud" class="w-8 h-8 text-gray-400 mx-auto mb-2"></i>
                            <span class="text-xs text-gray-300 block font-medium" x-text="receiptFileName || 'Toca aquí para seleccionar captura o PDF'"></span>
                            <span class="text-[10px] text-gray-400">JPG, PNG o PDF (Máximo 10MB)</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="pt-4 border-t border-gray-800">
                <button type="submit" class="w-full py-3.5 rounded-2xl bg-red-600 hover:bg-red-500 text-white font-black text-sm shadow-xl shadow-red-600/30 transition-all flex items-center justify-center gap-2">
                    <span x-text="paymentMethod === 'bank_transfer' ? 'Enviar Comprobante y Solicitar Activación' : 'Continuar a Mercado Pago'"></span>
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
