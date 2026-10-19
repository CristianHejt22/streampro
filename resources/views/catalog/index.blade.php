@extends('layouts.public')

@section('title', 'Catálogo de Cuentas Streaming')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-10">

    <!-- Reseller Referral Notice -->
    @if($seller && $seller->isReseller())
    <div class="p-4 rounded-2xl bg-gradient-to-r from-red-950/40 via-purple-950/30 to-gray-900 border border-red-500/40 flex items-center justify-between shadow-xl">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-red-500/20 text-red-400 flex items-center justify-center font-bold text-lg">
                🍿
            </div>
            <div>
                <span class="text-xs text-red-300 font-semibold uppercase tracking-wider block">Tienda Oficial Asociada</span>
                <h4 class="font-bold text-sm text-white">Atendido directamente por: <strong class="text-red-300">{{ $seller->brand_name ?: $seller->name }}</strong></h4>
            </div>
        </div>
        <span class="px-3 py-1 rounded-full text-xs font-mono bg-red-500/20 text-red-300 border border-red-500/30 font-bold">
            Ref: {{ $seller->referral_code }}
        </span>
    </div>
    @endif

    <!-- Hero Section -->
    <div class="text-center max-w-3xl mx-auto space-y-4">
        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-red-500/10 border border-red-500/20 text-red-400 text-xs font-semibold">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
            <span>Entrega Inmediata de Credenciales • Garantía Total 30 Días</span>
        </div>
        <h1 class="text-4xl sm:text-5xl font-black tracking-tight text-white leading-tight">
            Tus Cuentas de <span class="bg-gradient-to-r from-red-500 via-rose-400 to-amber-400 bg-clip-text text-transparent">Streaming Favoritas</span> al Mejor Precio
        </h1>
        <p class="text-sm sm:text-base text-gray-400 leading-relaxed">
            Netflix 4K, Disney+ Premium, Max, Prime Video, Spotify y MagisTV. Perfiles individuales privados con PIN o cuentas completas. Activación rápida y soporte personalizado por WhatsApp.
        </p>
    </div>

    <!-- Filters & Categories Bar -->
    <div class="bg-[#111827] border border-gray-800 rounded-3xl p-4 sm:p-5 flex flex-col md:flex-row items-center justify-between gap-4 backdrop-blur-md">
        <!-- Categories Pills -->
        <div class="flex items-center gap-2 overflow-x-auto w-full md:w-auto pb-2 md:pb-0 scrollbar-none">
            <a href="{{ route('catalog.index', array_merge(request()->except('category'), ['ref' => request('ref')])) }}" class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all shrink-0 {{ !request('category') ? 'bg-red-600 text-white shadow-md shadow-red-600/30' : 'bg-gray-800/80 text-gray-400 hover:text-white hover:bg-gray-700' }}">
                Todos los Servicios
            </a>
            @foreach($categories as $cat)
            <a href="{{ route('catalog.index', array_merge(request()->except('category'), ['category' => $cat, 'ref' => request('ref')])) }}" class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all shrink-0 {{ request('category') === $cat ? 'bg-red-600 text-white shadow-md shadow-red-600/30' : 'bg-gray-800/80 text-gray-400 hover:text-white hover:bg-gray-700' }}">
                {{ ucfirst($cat) }}
            </a>
            @endforeach
        </div>

        <!-- Search Input -->
        <form method="GET" action="{{ route('catalog.index') }}" class="w-full md:w-72 relative">
            @if(request('ref'))
                <input type="hidden" name="ref" value="{{ request('ref') }}">
            @endif
            @if(request('category'))
                <input type="hidden" name="category" value="{{ request('category') }}">
            @endif
            <i data-lucide="search" class="w-4 h-4 text-gray-500 absolute left-3.5 top-3"></i>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar plataforma..."
                class="w-full pl-10 pr-4 py-2 bg-[#0b0f19] border border-gray-800 rounded-xl text-xs text-white placeholder-gray-500 focus:outline-none focus:border-red-500">
        </form>
    </div>

    <!-- Streaming Services Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($services as $serv)
        <div class="bg-[#111827] border border-gray-800 rounded-3xl p-6 flex flex-col justify-between hover:border-gray-700 transition-all hover:scale-[1.01] shadow-xl group relative overflow-hidden">
            <!-- Glow Accent -->
            <div class="absolute -top-12 -right-12 w-28 h-28 rounded-full blur-2xl pointer-events-none opacity-20" style="background-color: {{ $serv->color_hex }}"></div>

            <div>
                <!-- Top Brand Header -->
                <div class="flex items-center justify-between gap-3 mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl flex items-center justify-center font-black text-white shadow-lg text-lg shrink-0" style="background-color: {{ $serv->color_hex }}">
                            {{ substr($serv->name, 0, 2) }}
                        </div>
                        <div>
                            <h3 class="text-lg font-black text-white group-hover:text-red-400 transition-colors">
                                {{ $serv->name }}
                            </h3>
                            <span class="text-[11px] text-gray-400 capitalize">{{ $serv->category }} · Ultra HD</span>
                        </div>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                        ONLINE
                    </span>
                </div>

                <p class="text-xs text-gray-400 mt-2 line-clamp-2">
                    {{ $serv->description ?: 'Disfruta del catálogo completo con perfiles privados y sin interrupciones.' }}
                </p>

                <!-- Planes de la Plataforma -->
                <div class="mt-4 pt-3 border-t border-gray-800 space-y-2.5">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 block">Planes Disponibles:</span>
                    @foreach($serv->plans as $plan)
                    <div class="p-3 rounded-2xl bg-[#0b0f19] border border-gray-800 flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold text-white block">{{ $plan->name }}</span>
                            <span class="text-[10px] text-gray-400">{{ $plan->duration_days }} días · {{ $plan->modality === 'profile' ? 'Pantalla con PIN' : 'Cuenta Completa' }}</span>
                        </div>
                        <div class="text-right">
                            <span class="text-base font-black text-emerald-400">${{ number_format($plan->price, 2) }}</span>
                            <a href="{{ route('catalog.checkout', ['plan' => $plan->id, 'seller_id' => $seller->id]) }}" class="block px-3 py-1 rounded-lg bg-red-600 hover:bg-red-500 text-white font-bold text-[11px] mt-1 shadow-md shadow-red-600/20 transition-all text-center">
                                Comprar
                            </a>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Ventajas -->
            <div class="mt-4 pt-3 border-t border-gray-800 text-[11px] text-gray-400 flex items-center justify-between">
                <span class="flex items-center gap-1 text-emerald-400">
                    <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                    Garantía 30 días
                </span>
                <span class="flex items-center gap-1 text-cyan-400">
                    <i data-lucide="zap" class="w-3.5 h-3.5"></i>
                    Activación Rápida
                </span>
            </div>
        </div>
        @empty
        <div class="col-span-3 py-16 text-center text-gray-400">
            No se encontraron plataformas disponibles en este momento.
        </div>
        @endforelse
    </div>

</div>
@endsection
