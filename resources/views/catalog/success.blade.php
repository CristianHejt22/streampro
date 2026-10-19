@extends('layouts.public')

@section('title', 'Orden ' . $order->order_code)

@section('content')
<div class="max-w-xl mx-auto px-4 sm:px-6 py-12" x-data="{
    copied: false,
    copy(text) {
        navigator.clipboard.writeText(text);
        this.copied = true;
        setTimeout(() => this.copied = false, 2000);
    }
}">

    <div class="bg-[#111827] border border-gray-800 rounded-3xl p-6 sm:p-8 shadow-2xl text-center space-y-6">

        @if($order->payment_status === 'approved' && $order->sale)
        <!-- ========================================== -->
        <!-- PAGO APROBADO: MOSTRAR CREDENCIALES -->
        <!-- ========================================== -->
        <div class="w-16 h-16 rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center mx-auto text-2xl font-black">
            <i data-lucide="check" class="w-8 h-8"></i>
        </div>

        <div>
            <span class="text-xs font-bold text-emerald-400 uppercase tracking-widest block mb-1">¡Pago Aprobado con Éxito!</span>
            <h2 class="text-2xl font-black text-white">Tu Cuenta de Streaming Está Lista</h2>
            <p class="text-xs text-gray-400 mt-1">Orden: <strong class="font-mono text-cyan-400">{{ $order->order_code }}</strong></p>
        </div>

        <!-- Ficha de Credenciales -->
        <div class="p-5 rounded-2xl bg-[#0b0f19] border border-gray-800 text-left space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-gray-800">
                <span class="text-xs font-bold text-white">{{ $order->sale->service->name }}</span>
                <span class="text-[10px] px-2 py-0.5 rounded bg-red-600/20 text-red-400 font-bold">
                    {{ $order->sale->profile_name_snapshot ?: 'Cuenta Completa' }}
                </span>
            </div>

            <!-- Usuario -->
            <div>
                <span class="text-[10px] text-gray-400 uppercase font-semibold block">Usuario / Correo:</span>
                <div class="flex items-center justify-between gap-2 mt-0.5">
                    <code class="font-mono text-xs text-white font-bold select-all">{{ $order->sale->account_email_snapshot }}</code>
                    <button @click="copy('{{ $order->sale->account_email_snapshot }}')" class="p-1 text-gray-400 hover:text-white">
                        <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                    </button>
                </div>
            </div>

            <!-- Contraseña -->
            <div>
                <span class="text-[10px] text-gray-400 uppercase font-semibold block">Contraseña:</span>
                <div class="flex items-center justify-between gap-2 mt-0.5">
                    <code class="font-mono text-xs text-amber-400 font-bold select-all">{{ $order->sale->account_password_snapshot }}</code>
                    <button @click="copy('{{ $order->sale->account_password_snapshot }}')" class="p-1 text-gray-400 hover:text-white">
                        <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                    </button>
                </div>
            </div>

            <!-- Perfil & PIN -->
            @if($order->sale->profile_pin_snapshot)
            <div class="grid grid-cols-2 gap-2 pt-2 border-t border-gray-800/80">
                <div>
                    <span class="text-[10px] text-gray-400 uppercase font-semibold block">Perfil:</span>
                    <span class="text-xs font-bold text-white">{{ $order->sale->profile_name_snapshot }}</span>
                </div>
                <div>
                    <span class="text-[10px] text-gray-400 uppercase font-semibold block">PIN del Perfil:</span>
                    <code class="text-xs font-mono font-bold text-emerald-400">{{ $order->sale->profile_pin_snapshot }}</code>
                </div>
            </div>
            @endif

            <!-- Vencimiento -->
            <div class="pt-2 border-t border-gray-800/80 text-[11px] text-gray-400 flex items-center justify-between">
                <span>Vence el:</span>
                <strong class="text-white">{{ $order->sale->end_date->format('d/m/Y') }}</strong>
            </div>

            <!-- Reglas -->
            @if($order->sale->usage_rules_snapshot)
            <div class="pt-2 border-t border-gray-800/80 text-[10px] text-amber-200/90">
                <span class="font-bold block mb-0.5">⚠️ Reglas de uso:</span>
                <pre class="font-sans whitespace-pre-line text-[10px] text-gray-300">{{ $order->sale->usage_rules_snapshot }}</pre>
            </div>
            @endif
        </div>

        <div x-show="copied" class="p-2 rounded-xl bg-emerald-500/20 text-emerald-400 text-xs font-bold text-center">
            ¡Copiado al portapapeles!
        </div>

        <button @click="copy('{{ addslashes($order->sale->whats_app_delivery_message) }}')" class="w-full py-3 rounded-2xl bg-gray-800 hover:bg-gray-700 text-white font-bold text-xs flex items-center justify-center gap-2 transition-all">
            <i data-lucide="copy-check" class="w-4 h-4 text-emerald-400"></i>
            <span>Copiar Toda la Ficha de Acceso</span>
        </button>

        @else
        <!-- ========================================== -->
        <!-- PAGO PENDIENTE: EN AUDITORÍA MINI NUBE -->
        <!-- ========================================== -->
        <div class="w-16 h-16 rounded-full bg-amber-500/20 text-amber-400 border border-amber-500/30 flex items-center justify-center mx-auto text-2xl font-black animate-pulse">
            <i data-lucide="clock" class="w-8 h-8"></i>
        </div>

        <div>
            <span class="text-xs font-bold text-amber-400 uppercase tracking-widest block mb-1">Orden Recibida</span>
            <h2 class="text-2xl font-black text-white">Comprobante en Auditoría</h2>
            <p class="text-xs text-gray-400 mt-2">
                Código de Orden: <strong class="font-mono text-cyan-400">{{ $order->order_code }}</strong>
            </p>
        </div>

        <div class="p-5 rounded-2xl bg-[#0b0f19] border border-gray-800 text-xs text-gray-300 text-left space-y-2">
            <p>Tu comprobante fue cargado correctamente a la <strong>Mini Nube de verificación</strong>.</p>
            <p>Nuestro equipo auditará la transferencia bancaria en unos minutos y activará tu cuenta de streaming de inmediato.</p>
        </div>

        <!-- Botón Contactar por WhatsApp -->
        @php
            $waNumber = $order->seller ? preg_replace('/[^0-9]/', '', $order->seller->support_whatsapp ?: $order->seller->phone) : null;
        @endphp
        @if($waNumber)
        <div>
            <a href="https://wa.me/{{ $waNumber }}?text={{ urlencode('¡Hola! Acabo de enviar el comprobante de mi orden ' . $order->order_code . ' para la cuenta de ' . ($order->servicePlan ? $order->servicePlan->name : 'Streaming')) }}" target="_blank" class="w-full py-3 rounded-2xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs flex items-center justify-center gap-2 shadow-lg shadow-emerald-600/30 transition-all">
                <i data-lucide="message-circle" class="w-4 h-4"></i>
                <span>Avisar al Vendedor por WhatsApp</span>
            </a>
        </div>
        @endif

        @endif

        <div class="pt-4 border-t border-gray-800 text-center">
            <a href="{{ route('catalog.index', ['ref' => $order->seller ? $order->seller->referral_code : '']) }}" class="text-xs text-gray-400 hover:text-white">
                Volver al Catálogo
            </a>
        </div>
    </div>

</div>
@endsection
