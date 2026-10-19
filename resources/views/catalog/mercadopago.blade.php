@extends('layouts.public')

@section('title', 'Pasarela Mercado Pago')

@section('content')
<div class="max-w-md mx-auto px-4 py-16 text-center">
    <div class="bg-[#111827] border border-gray-800 rounded-3xl p-8 shadow-2xl space-y-6">
        <!-- MP Brand Icon -->
        <div class="w-16 h-16 rounded-2xl bg-cyan-500/10 border border-cyan-500/30 text-cyan-400 mx-auto flex items-center justify-center text-3xl">
            💳
        </div>

        <div>
            <h2 class="text-2xl font-black text-white">Pasarela Mercado Pago</h2>
            <p class="text-xs text-gray-400 mt-1">Conectando con la cuenta del vendedor: <strong>{{ $order->seller ? ($order->seller->brand_name ?: $order->seller->name) : 'Admin' }}</strong></p>
        </div>

        <div class="p-4 rounded-2xl bg-[#0b0f19] border border-gray-800 text-left space-y-2 text-xs">
            <div class="flex justify-between text-gray-400">
                <span>Orden:</span>
                <span class="font-mono text-cyan-400 font-bold">{{ $order->order_code }}</span>
            </div>
            <div class="flex justify-between text-gray-400">
                <span>Servicio / Plan:</span>
                <span class="text-white font-semibold">{{ $order->servicePlan ? $order->servicePlan->name : 'N/A' }}</span>
            </div>
            <div class="flex justify-between text-gray-400">
                <span>Cliente:</span>
                <span class="text-white">{{ $order->customer_name }}</span>
            </div>
            <div class="pt-2 border-t border-gray-800 flex justify-between font-bold text-sm">
                <span class="text-white">Monto:</span>
                <span class="text-emerald-400">${{ number_format($order->amount, 2) }}</span>
            </div>
        </div>

        <div class="p-3 rounded-xl bg-cyan-500/10 border border-cyan-500/20 text-[11px] text-cyan-300">
            ⚡ En entorno productivo, redirige a la pasarela oficial de Mercado Pago. En este entorno puedes probar la acreditación inmediata:
        </div>

        <form method="POST" action="{{ route('catalog.mp_simulate', $order->order_code) }}">
            @csrf
            <button type="submit" class="w-full py-3.5 px-4 rounded-2xl bg-gradient-to-r from-cyan-500 to-blue-600 hover:opacity-95 text-white font-bold text-sm shadow-xl shadow-cyan-500/25 flex items-center justify-center gap-2 transition-all">
                <i data-lucide="check-circle" class="w-4 h-4"></i>
                <span>Aprobar Pago Automáticamente</span>
            </button>
        </form>

        <a href="{{ route('catalog.index') }}" class="text-xs text-gray-500 hover:text-gray-400 block">
            Cancelar y volver al catálogo
        </a>
    </div>
</div>
@endsection
