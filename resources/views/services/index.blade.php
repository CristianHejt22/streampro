@extends('layouts.app')

@section('title', 'Servicios & Planes de Streaming')
@section('page_title', 'Plataformas & Planes')
@section('page_subtitle', 'Configuración de catálogo, precios para clientes finales y mayoristas para revendedores')

@section('content')
<div class="space-y-6" x-data="{
    newServiceModal: false,
    newPlanModal: false,
    myPvpModal: false,
    selectedService: null
}">

    <!-- Tier & Capacity Banner -->
    @if(Auth::user()->isAdmin())
    <div class="rounded-2xl bg-gradient-to-r from-red-950/40 via-slate-900 to-slate-900 border border-red-500/30 p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-lg">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-red-600/20 text-red-400 border border-red-500/30 flex items-center justify-center font-bold">
                <i data-lucide="crown" class="w-5 h-5"></i>
            </div>
            <div>
                <span class="text-xs font-mono font-bold text-red-400 uppercase tracking-wider block">Super Panel Administrador</span>
                <span class="text-xs text-gray-300">Gestión global de plataformas maestras. Las plataformas globales se sincronizan con todos los revendedores.</span>
            </div>
        </div>
        <div class="px-3 py-1.5 rounded-xl bg-slate-950 border border-gray-800 text-xs font-mono text-gray-300">
            Total Plataformas: <strong class="text-white">{{ $activeServicesCount }}</strong>
        </div>
    </div>
    @elseif(Auth::user()->isPanelPro())
    <div class="rounded-2xl bg-gradient-to-r from-purple-950/40 via-slate-900 to-indigo-950/30 border border-purple-500/30 p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-lg">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-purple-600/20 text-purple-400 border border-purple-500/30 flex items-center justify-center font-bold">
                <i data-lucide="gem" class="w-5 h-5"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-mono font-bold text-purple-400 uppercase tracking-wider block">Panel PRO (Ilimitado)</span>
                    <span class="px-2 py-0.5 rounded-full text-[9px] font-black bg-purple-500 text-white">SIN LÍMITES</span>
                </div>
                <span class="text-xs text-gray-300">Puedes agregar todos los servicios y plataformas individuales que desees a tu catálogo.</span>
            </div>
        </div>
        <div class="px-3 py-1.5 rounded-xl bg-purple-500/10 border border-purple-500/30 text-xs font-mono text-purple-300">
            Plataformas en tu Panel: <strong class="text-white">{{ $activeServicesCount }}</strong> (Ilimitado)
        </div>
    </div>
    @else
    <div class="rounded-2xl bg-gradient-to-r from-amber-950/40 via-slate-900 to-slate-900 border border-amber-500/30 p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-lg">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-amber-600/20 text-amber-400 border border-amber-500/30 flex items-center justify-center font-bold">
                <i data-lucide="package" class="w-5 h-5"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-mono font-bold text-amber-400 uppercase tracking-wider block">Panel BÁSICO (Limitado a 10 Servicios)</span>
                    <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                        {{ $activeServicesCount }} / 10 Ocupados
                    </span>
                </div>
                <span class="text-xs text-gray-300">
                    Tu plan permite un máximo de 10 plataformas en catálogo. 
                    @if($activeServicesCount >= 10)
                    <strong class="text-rose-400">¡Límite alcanzado! Actualiza a Panel PRO para añadir más plataformas.</strong>
                    @else
                    Te quedan <strong>{{ max(0, 10 - $activeServicesCount) }}</strong> plataformas disponibles.
                    @endif
                </span>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <div class="w-32 bg-slate-950 rounded-full h-2.5 overflow-hidden border border-gray-800">
                <div class="h-2.5 rounded-full {{ $activeServicesCount >= 10 ? 'bg-rose-500' : 'bg-amber-500' }}" style="width: {{ min(100, ($activeServicesCount / 10) * 100) }}%"></div>
            </div>
            <span class="text-xs font-mono font-bold {{ $activeServicesCount >= 10 ? 'text-rose-400' : 'text-amber-400' }}">{{ $activeServicesCount }}/10</span>
        </div>
    </div>
    @endif

    <!-- ======================================================== -->
    <!-- SERVICIO DE REVENTA DE ACCESO A PANELES (SOLO REVENDEDORES) -->
    <!-- ======================================================== -->
    @if(Auth::user()->isReseller() && Auth::user()->isPanelBasic())
    <!-- Candado informativo para Panel BÁSICO -->
    <div class="rounded-3xl bg-gradient-to-r from-slate-900 via-amber-950/20 to-slate-900 border border-amber-500/30 p-6 shadow-xl space-y-3">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-2xl bg-amber-500/20 text-amber-400 border border-amber-500/40 flex items-center justify-center font-bold text-xl shrink-0">
                    <i data-lucide="lock" class="w-6 h-6"></i>
                </div>
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <h4 class="text-base font-black text-white">Reventa de Acceso a Funciones de Panel (Solo Revendedores)</h4>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-amber-500/20 text-amber-300 border border-amber-500/30 font-mono">
                            BLOQUEADO EN BÁSICO
                        </span>
                    </div>
                    <p class="text-xs text-slate-300 max-w-3xl leading-relaxed">
                        Tu panel actual es <strong>BÁSICO (SPRO PLAY)</strong>. <strong>El básico no revende ningún plan</strong> de panel, su alcance es exclusivamente la venta de cuentas y pantallas de streaming generales a clientes finales.
                    </p>
                    <span class="text-[11px] text-amber-300 block font-semibold">
                        💡 Para habilitar la posibilidad de revender funciones de panel a tus propios revendedores, actualiza tu panel a PRO.
                    </span>
                </div>
            </div>
            <a href="{{ route('dashboard') }}" class="px-5 py-3 rounded-2xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:opacity-90 text-white font-black text-xs uppercase tracking-wider flex items-center gap-2 shadow-lg shadow-purple-600/30 shrink-0">
                <i data-lucide="crown" class="w-4 h-4"></i>
                <span>Ascender a Panel PRO ($15k)</span>
            </a>
        </div>
    </div>
    @elseif(Auth::user()->canResellPanels())
    <!-- Panel HABILITADO para revender funciones de panel a revendedores (PRO, Proveedor o Admin) -->
    <div class="rounded-3xl bg-gradient-to-r from-[#141b2d] via-slate-900 to-[#141b2d] border-2 border-purple-500/40 p-6 sm:p-7 shadow-2xl space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-gray-800 pb-4">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-purple-600 to-indigo-600 text-white flex items-center justify-center font-black shadow-lg shadow-purple-600/30 shrink-0">
                    <i data-lucide="network" class="w-6 h-6"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-base sm:text-lg font-black text-white">Servicio de Reventa de Paneles a Revendedores</h3>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-gradient-to-r from-purple-500 to-indigo-500 text-white uppercase tracking-wider font-mono">
                            ✓ HABILITADO EN PRO
                        </span>
                    </div>
                    <p class="text-xs text-slate-300 mt-0.5">
                        Esto es para <strong>revender acceso a las funciones de panel</strong> a tus revendedores (no son cuentas generales). Las solicitudes se activan desde tu panel consumiendo 1 crédito según el plan adquirido.
                    </p>
                </div>
            </div>

            <!-- Botones de Acción / Compartir Enlace -->
            <div class="flex flex-wrap items-center gap-2 shrink-0">
                <button type="button" onclick="navigator.clipboard.writeText('{{ route('affiliate.register', ['code' => Auth::user()->referral_code ?: Auth::id()]) }}'); alert('¡Enlace de invitación de revendedores copiado al portapapeles!');" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-purple-300 border border-purple-500/30 font-bold text-xs flex items-center gap-1.5 cursor-pointer transition-all">
                    <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                    <span>Copiar Enlace Afiliación</span>
                </button>
                <a href="https://wa.me/?text={{ urlencode('¡Hola! Súmate como revendedor a mi red de streaming y adquiere tu panel propio aquí: ' . route('affiliate.register', ['code' => Auth::user()->referral_code ?: Auth::id()])) }}" target="_blank" class="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs flex items-center gap-1.5 shadow-md shadow-emerald-600/20">
                    <i data-lucide="message-circle" class="w-3.5 h-3.5"></i>
                    <span>WhatsApp</span>
                </a>
                <a href="{{ route('resellers.index') }}" class="px-3.5 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs flex items-center gap-1.5 shadow-md shadow-purple-600/30">
                    <i data-lucide="users" class="w-3.5 h-3.5"></i>
                    <span>Ver Solicitudes de Panel</span>
                </a>
            </div>
        </div>

        <!-- 2 Planes Disponibles para Ofrecer a los Revendedores -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <!-- Plan 1: Panel Básico SPRO PLAY -->
            <div class="p-5 rounded-2xl bg-[#0b0f19] border border-amber-500/30 flex flex-col justify-between space-y-4 hover:border-amber-500/60 transition-all shadow-lg">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-amber-500/20 text-amber-300 border border-amber-500/30 font-mono">
                            📦 PLAN BÁSICO (10 SERVICIOS)
                        </span>
                        <span class="text-[11px] font-mono text-slate-400">Marca: <strong>SPRO PLAY</strong></span>
                    </div>
                    <div>
                        <h4 class="text-base font-black text-white">Panel Revendedor Básico (SPRO PLAY)</h4>
                        <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                            Acceso para vender hasta 10 servicios con catálogo sincronizado. 
                            <strong class="text-amber-300 block mt-0.5">El básico no revende ningún plan</strong> (sólo comercializa cuentas generales a clientes finales).
                        </p>
                    </div>

                    <div class="p-3 rounded-xl bg-slate-900/90 border border-slate-800 text-xs space-y-1.5">
                        <div class="flex items-center justify-between text-slate-300">
                            <span>Costo de Activación (Tus Créditos):</span>
                            <span class="font-bold text-amber-400 font-mono">1 Crédito Básico ($2.500)</span>
                        </div>
                        <div class="flex items-center justify-between text-slate-400 text-[11px]">
                            <span>PVP Sugerido al Revendedor:</span>
                            <span class="text-white font-mono font-bold">$4.000 / mes</span>
                        </div>
                        <div class="flex items-center justify-between text-emerald-400 font-bold pt-1 border-t border-slate-800">
                            <span>Margen Neto Estimado:</span>
                            <span class="font-mono">+$1.500 por panel</span>
                        </div>
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-800 flex items-center justify-between text-xs">
                    <span class="text-slate-400 text-[11px]">Activación desde panel</span>
                    <a href="{{ route('resellers.index') }}" class="text-amber-400 hover:underline font-bold text-xs flex items-center gap-1">
                        Activar con 1 Crédito Básico <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                    </a>
                </div>
            </div>

            <!-- Plan 2: Panel PRO Ilimitado Marca Blanca -->
            <div class="p-5 rounded-2xl bg-[#0b0f19] border border-purple-500/40 flex flex-col justify-between space-y-4 hover:border-purple-500/70 transition-all shadow-lg">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-gradient-to-r from-purple-500 to-indigo-500 text-white font-mono shadow-sm">
                            💎 PLAN PRO (ILIMITADO)
                        </span>
                        <span class="text-[11px] font-mono text-purple-300">100% Marca Blanca</span>
                    </div>
                    <div>
                        <h4 class="text-base font-black text-white">Panel PRO Mayorista Ilimitado</h4>
                        <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                            Servicios ilimitados, personalización de marca completa y 
                            <strong class="text-purple-300 block mt-0.5">el PRO tiene acceso a la posibilidad de revender paneles a sus revendedores.</strong>
                        </p>
                    </div>

                    <div class="p-3 rounded-xl bg-slate-900/90 border border-slate-800 text-xs space-y-1.5">
                        <div class="flex items-center justify-between text-slate-300">
                            <span>Costo de Activación (Tus Créditos):</span>
                            <span class="font-bold text-purple-400 font-mono">1 Crédito PRO ($4.000)</span>
                        </div>
                        <div class="flex items-center justify-between text-slate-400 text-[11px]">
                            <span>PVP Sugerido al Revendedor:</span>
                            <span class="text-white font-mono font-bold">$6.000 / mes</span>
                        </div>
                        <div class="flex items-center justify-between text-emerald-400 font-bold pt-1 border-t border-slate-800">
                            <span>Margen Neto Estimado:</span>
                            <span class="font-mono">+$2.000 por panel</span>
                        </div>
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-800 flex items-center justify-between text-xs">
                    <span class="text-slate-400 text-[11px]">Activación desde panel</span>
                    <a href="{{ route('resellers.index') }}" class="text-purple-400 hover:underline font-bold text-xs flex items-center gap-1">
                        Activar con 1 Crédito PRO <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endif
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h3 class="text-sm font-bold text-white uppercase tracking-wider">Catálogo de Plataformas</h3>
            <p class="text-xs text-gray-400">Servicios disponibles para venta, reglas de entrega y planes</p>
        </div>

        <div class="flex items-center gap-2">
            <button @click="myPvpModal = true" class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs flex items-center gap-2 transition-all shadow-lg shadow-emerald-600/30 cursor-pointer">
                <i data-lucide="tag" class="w-4 h-4"></i>
                <span>Ajustar Mis Precios PVP</span>
            </button>
            <button @click="newServiceModal = true" class="px-4 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold text-xs flex items-center gap-2 transition-all shadow-lg shadow-red-600/30 cursor-pointer">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                <span>Nueva Plataforma</span>
            </button>
        </div>
    </div>

    <!-- Services Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($services as $serv)
        <div class="rounded-2xl bg-[#111827] border border-gray-800 shadow-xl overflow-hidden flex flex-col justify-between hover:border-gray-700 transition-all">
            <!-- Header de Plataforma -->
            <div class="p-5 border-b border-gray-800/80 bg-[#0d1322]">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-white shadow-md text-sm" style="background-color: {{ $serv->color_hex }}">
                            {{ substr($serv->name, 0, 2) }}
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-white">{{ $serv->name }}</h4>
                            <div class="flex flex-wrap items-center gap-1.5 mt-0.5">
                                <span class="text-[10px] px-2 py-0.5 rounded-full bg-gray-800 text-gray-300 font-medium">
                                    {{ strtoupper($serv->category) }} · {{ $serv->max_profiles_default }} Perfiles Max
                                </span>
                                @if(!$serv->user_id)
                                <span class="text-[9px] px-2 py-0.5 rounded-full bg-amber-500/10 text-amber-300 border border-amber-500/30 font-bold">
                                    👑 Oficial Global
                                </span>
                                @elseif($serv->user_id === Auth::id())
                                <span class="text-[9px] px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-300 border border-emerald-500/30 font-bold">
                                    ✨ Tu Servicio Propio
                                </span>
                                @else
                                <span class="text-[9px] px-2 py-0.5 rounded-full bg-purple-500/10 text-purple-300 border border-purple-500/30 font-bold">
                                    🏢 Catálogo de Proveedor o Revendedor
                                </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    @if(Auth::user()->isAdmin() || $serv->user_id === Auth::id())
                    <form method="POST" action="{{ route('services.destroy', $serv) }}" onsubmit="return confirm('¿Seguro que deseas eliminar esta plataforma y sus planes?');" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-gray-400 hover:text-rose-400 p-1" title="Eliminar Plataforma">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </form>
                    @endif
                </div>
            </div>

            <!-- Planes Asociados -->
            <div class="p-5 flex-1 space-y-3">
                <div class="flex items-center justify-between text-xs text-gray-400 font-bold uppercase tracking-wider">
                    <span>Planes Ofrecidos ({{ $serv->plans->count() }})</span>
                    <button @click="selectedService = {{ json_encode($serv) }}; newPlanModal = true;" class="text-red-400 hover:text-red-300 text-[11px] font-semibold flex items-center gap-1">
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i> Añadir Plan
                    </button>
                </div>

                <div class="space-y-2">
                    @forelse($serv->plans as $plan)
                    @php
                        $pricing = Auth::user()->getEffectivePlanPricing($plan);
                        $margin = max(0, $pricing['reseller_pvp'] - $pricing['provider_cost']);
                    @endphp
                    <div class="p-3 rounded-xl bg-[#0b0f19] border border-gray-800 flex items-center justify-between text-xs hover:border-gray-700 transition-colors">
                        <div>
                            <div class="font-bold text-white">{{ $plan->name }}</div>
                            <div class="text-[10px] text-gray-400 mt-0.5">
                                {{ $plan->duration_days }} días · {{ $plan->modality === 'profile' ? 'Pantalla con PIN' : 'Cuenta Completa' }}
                            </div>
                            <div class="text-[10px] text-emerald-400 font-mono mt-0.5">
                                Ganancia neta: <strong>+${{ number_format($margin, 2) }}</strong>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="font-black text-white text-sm">${{ number_format($pricing['reseller_pvp'], 2) }} <span class="text-[9px] font-normal text-gray-400">PVP</span></div>
                            <div class="text-[10px] text-cyan-400 font-mono font-semibold">Costo: ${{ number_format($pricing['provider_cost'], 2) }}</div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-4 text-gray-400 text-xs">
                        Sin planes configurados. Haz clic en "Añadir Plan".
                    </div>
                    @endforelse
                </div>

                @if($serv->default_rules)
                <div class="mt-3 p-3 rounded-xl bg-gray-900 border border-gray-800/80 text-[10px] text-gray-400">
                    <span class="font-bold text-gray-300 block mb-0.5">Reglas de Uso Predeterminadas:</span>
                    <pre class="font-sans whitespace-pre-line truncate">{{ $serv->default_rules }}</pre>
                </div>
                @endif
            </div>

            <div class="p-4 border-t border-gray-800/80 bg-[#0e1424] flex items-center justify-between text-xs">
                <span class="text-gray-400">Disponibilidad en Stock:</span>
                <span class="text-emerald-400 font-bold">{{ $serv->available_profiles_count }} pantallas libres</span>
            </div>
        </div>
        @endforeach
    </div>

    <!-- ========================================== -->
    <!-- MODAL: NUEVA PLATAFORMA STREAMING -->
    <!-- ========================================== -->
    <div x-show="newServiceModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
        <div @click.away="newServiceModal = false" class="w-full max-w-lg bg-[#111827] border border-gray-800 rounded-3xl shadow-2xl p-6">
            <h3 class="text-base font-bold text-white mb-1">Nueva Plataforma de Streaming</h3>
            <p class="text-xs text-gray-400 mb-4">Registra una nueva aplicación (Netflix, Disney+, IPTV, etc.)</p>

            <form method="POST" action="{{ route('services.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Nombre de la Plataforma *</label>
                    <input type="text" name="name" placeholder="Ej: Paramount+ UHD" required class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white focus:border-red-500">
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Categoría</label>
                        <select name="category" class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white">
                            <option value="video">Películas & Series</option>
                            <option value="music">Música</option>
                            <option value="iptv">IPTV / TV</option>
                            <option value="anime">Anime</option>
                            <option value="other">Otro</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Color Distintivo</label>
                        <input type="color" name="color_hex" value="#E50914" class="w-full h-10 rounded-xl bg-[#0b0f19] border border-gray-800 p-1 cursor-pointer">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Perfiles Máx</label>
                        <input type="number" name="max_profiles_default" value="5" min="1" max="10" class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Reglas de Uso Obligatorias (Se enviarán al cliente)</label>
                    <textarea name="default_rules" rows="3" placeholder="- Solo ingresar al perfil asignado&#10;- Prohibido cambiar correo o contraseña" class="w-full px-3.5 py-2 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white focus:border-red-500"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-800">
                    <button type="button" @click="newServiceModal = false" class="px-4 py-2 text-xs text-gray-400 hover:text-white">Cancelar</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold text-xs shadow-lg shadow-red-600/30">Guardar Plataforma</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL: AÑADIR PLAN A SERVICIO -->
    <!-- ========================================== -->
    <div x-show="newPlanModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
        <div @click.away="newPlanModal = false" class="w-full max-w-lg bg-[#111827] border border-gray-800 rounded-3xl shadow-2xl p-6">
            <h3 class="text-base font-bold text-white mb-1">Añadir Plan a <span class="text-red-400" x-text="selectedService?.name"></span></h3>
            <p class="text-xs text-gray-400 mb-4">Define modalidades (pantalla con PIN o cuenta completa) y precios</p>

            <form :action="'/services/' + selectedService?.id + '/plans'" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Nombre del Plan *</label>
                    <input type="text" name="name" placeholder="Ej: 1 Pantalla con PIN (30 días)" required class="w-full px-3.5 py-2 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white focus:border-red-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Modalidad</label>
                        <select name="modality" class="w-full px-3.5 py-2 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white">
                            <option value="profile">1 Pantalla / Perfil Individual</option>
                            <option value="full_account">Cuenta Completa (Todos los perfiles)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Duración (Días)</label>
                        <input type="number" name="duration_days" value="30" min="1" max="365" class="w-full px-3.5 py-2 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white">
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-emerald-400 mb-1">Precio Público ($) *</label>
                        <input type="number" step="0.01" name="price" placeholder="4500.00" required class="w-full px-3.5 py-2 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white font-bold">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-cyan-400 mb-1">Mayorista ($) *</label>
                        <input type="number" step="0.01" name="reseller_price" placeholder="3200.00" required class="w-full px-3.5 py-2 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white font-bold">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Costo Prov. ($)</label>
                        <input type="number" step="0.01" name="cost_estimate" placeholder="2000.00" class="w-full px-3.5 py-2 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white">
                    </div>
                </div>

                <input type="hidden" name="screens_count" value="1">

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-800">
                    <button type="button" @click="newPlanModal = false" class="px-4 py-2 text-xs text-gray-400 hover:text-white">Cancelar</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold text-xs shadow-lg shadow-red-600/30">Crear Plan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL: AJUSTAR MIS PRECIOS DE VENTA (PVP) -->
    <!-- ========================================== -->
    <div x-show="myPvpModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-md" @click.self="myPvpModal = false">
        <div class="w-full max-w-2xl bg-[#111827] border border-gray-800 rounded-3xl shadow-2xl p-6 space-y-4 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between pb-3 border-b border-gray-800">
                <div class="flex items-center gap-2.5 text-emerald-400">
                    <i data-lucide="badge-dollar-sign" class="w-6 h-6"></i>
                    <div>
                        <h3 class="font-black text-white text-base">Definir Mis Precios al Público (PVP)</h3>
                        <span class="text-xs text-gray-400">Fija el precio de venta en tu tienda y visualiza tu ganancia neta en tiempo real</span>
                    </div>
                </div>
                <button @click="myPvpModal = false" class="text-gray-400 hover:text-white text-lg font-bold cursor-pointer">✕</button>
            </div>

            <div class="p-3.5 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-300 text-xs leading-relaxed">
                💵 <strong>Tú decides tu margen:</strong> El costo mayorista es lo que se descontará de tu billetera al entregar cada cuenta. Lo que cobres por encima en tu tienda es tu <strong>Ganancia Neta directa</strong>.
            </div>

            <form action="{{ route('services.plans.update_my_pvp') }}" method="POST" class="space-y-4">
                @csrf

                <div class="rounded-2xl border border-gray-800 overflow-hidden">
                    <table class="w-full text-left text-xs text-gray-200">
                        <thead>
                            <tr class="bg-gray-900 border-b border-gray-800 text-[10px] uppercase font-bold text-gray-400">
                                <th class="py-2.5 px-3">Plataforma / Plan</th>
                                <th class="py-2.5 px-3">Tu Costo Mayorista</th>
                                <th class="py-2.5 px-3">PVP Sugerido Base</th>
                                <th class="py-2.5 px-3">Tu Precio al Público (PVP)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-800/60 font-mono">
                            @foreach($services as $s)
                            @foreach($s->plans as $p)
                            @php
                                $pricing = Auth::user()->getEffectivePlanPricing($p);
                            @endphp
                            <tr class="hover:bg-gray-800/30">
                                <td class="py-2.5 px-3 font-sans">
                                    <span class="font-bold text-white block">{{ $s->name }}</span>
                                    <span class="text-[11px] text-gray-400">{{ $p->name }}</span>
                                </td>
                                <td class="py-2.5 px-3 text-cyan-400 font-bold">
                                    ${{ number_format($pricing['provider_cost'], 2) }}
                                </td>
                                <td class="py-2.5 px-3 text-gray-400">
                                    ${{ number_format($p->price, 2) }}
                                </td>
                                <td class="py-2.5 px-3">
                                    <input type="number" step="0.01" min="{{ $pricing['provider_cost'] }}" 
                                           name="reseller_pvps[{{ $p->id }}]" 
                                           value="{{ $pricing['reseller_pvp'] }}" 
                                           class="w-32 px-3 py-1.5 rounded-xl bg-gray-950 border border-gray-700 text-xs font-mono text-emerald-400 font-bold focus:border-emerald-500 outline-none">
                                </td>
                            </tr>
                            @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="myPvpModal = false" class="px-4 py-2 text-xs text-gray-400 hover:text-white cursor-pointer">Cancelar</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-lg shadow-emerald-600/30 cursor-pointer">
                        Guardar Mis Precios PVP
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
