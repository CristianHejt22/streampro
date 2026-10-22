@extends('layouts.app')

@section('title', 'Servicios Disponibles & Stock en Tiempo Real')
@section('page_title', 'Servicios Disponibles')
@section('page_subtitle', 'Catálogo oficial de servicios de streaming, configuración de planes y gestión de inventario')

@section('content')
<div class="space-y-6" x-data="{
    newServiceModal: false,
    editServiceModal: false,
    editingService: { id: null, name: '', category: 'video', color_hex: '#E50914', max_profiles_default: 5, default_rules: '', status: 'active' },
    openEdit(serv) {
        this.editingService = {
            id: serv.id,
            name: serv.name,
            category: serv.category || 'video',
            color_hex: serv.color_hex || '#E50914',
            max_profiles_default: serv.max_profiles_default || 5,
            default_rules: serv.default_rules || '',
            status: serv.status || 'active'
        };
        this.editServiceModal = true;
    }
}">

    <!-- ======================================================== -->
    <!-- TIER & CAPACITY BANNER -->
    <!-- ======================================================== -->
    @if(Auth::user()->isAdmin())
    <div class="rounded-2xl bg-gradient-to-r from-red-950/40 via-slate-900 to-slate-900 border border-red-500/30 p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-lg">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-red-600/20 text-red-400 border border-red-500/30 flex items-center justify-center font-bold">
                <i data-lucide="crown" class="w-5 h-5"></i>
            </div>
            <div>
                <span class="text-xs font-mono font-bold text-red-400 uppercase tracking-wider block">Super Panel Administrador</span>
                <span class="text-xs text-gray-300">Gestión global de plataformas maestras e inventario integral del sistema.</span>
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
                <span class="text-xs text-gray-300">Puedes agregar todos los servicios disponibles que desees y clonar el catálogo de tu proveedor.</span>
            </div>
        </div>
        <div class="px-3 py-1.5 rounded-xl bg-purple-500/10 border border-purple-500/30 text-xs font-mono text-purple-300">
            Servicios en tu Catálogo: <strong class="text-white">{{ $activeServicesCount }}</strong> (Ilimitado)
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
                    <span class="text-xs font-mono font-bold text-amber-400 uppercase tracking-wider block">Panel BÁSICO SPRO PLAY (Hasta 10 Servicios)</span>
                    <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                        {{ $activeServicesCount }} / 10 Ocupados
                    </span>
                </div>
                <span class="text-xs text-gray-300">
                    Tu plan permite un máximo de 10 plataformas disponibles.
                    @if($activeServicesCount >= 10)
                    <strong class="text-rose-400">¡Límite alcanzado! Actualiza a Panel PRO para añadir servicios ilimitados.</strong>
                    @else
                    Te quedan <strong>{{ max(0, 10 - $activeServicesCount) }}</strong> servicios disponibles para agregar.
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
                <span>Ascender a Panel PRO</span>
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
                        Esto es para <strong>revender acceso a las funciones de panel</strong> a tus revendedores. Las solicitudes se activan desde tu panel consumiendo 1 crédito según el plan adquirido.
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

        <!-- 2 Opciones Disponibles para Ofrecer a los Revendedores -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <!-- Plan 1: Panel Básico SPRO PLAY -->
            <div class="p-5 rounded-2xl bg-[#0b0f19] border border-amber-500/30 flex flex-col justify-between space-y-4 hover:border-amber-500/60 transition-all shadow-lg">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-amber-500/20 text-amber-300 border border-amber-500/30 font-mono">
                            📦 PANEL BÁSICO (10 SERVICIOS)
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
                    <span class="text-slate-400 text-[11px]">Activación desde solicitudes</span>
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
                            💎 PANEL PRO (ILIMITADO)
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
                    <span class="text-slate-400 text-[11px]">Activación desde solicitudes</span>
                    <a href="{{ route('resellers.index') }}" class="text-purple-400 hover:underline font-bold text-xs flex items-center gap-1">
                        Activar con 1 Crédito PRO <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- ======================================================== -->
    <!-- BARRA SUPERIOR: CATÁLOGO DE SERVICIOS & ACCIONES -->
    <!-- ======================================================== -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-[#0f172a] p-5 rounded-2xl border border-slate-800">
        <div>
            <h3 class="text-base font-black text-white flex items-center gap-2">
                <span>📺</span>
                <span>Disponibilidad de Stock por Plataforma (Tiempo Real)</span>
            </h3>
            <p class="text-xs text-slate-400">Capacidad total y perfiles libres listos para entrega inmediata a clientes y revendedores.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            @if(Auth::user()->canResellPanels())
            <!-- Botón Clonar Catálogo para PRO / Proveedor / Admin -->
            <form method="POST" action="{{ route('services.clone_catalog') }}" onsubmit="return confirm('¿Deseas clonar y sincronizar las plataformas maestras disponibles a tu catálogo propio?');" class="inline">
                @csrf
                <button type="submit" class="px-4 py-2.5 rounded-xl bg-purple-600/20 hover:bg-purple-600 text-purple-300 hover:text-white font-bold text-xs flex items-center gap-2 transition-all border border-purple-500/30 shadow-md cursor-pointer">
                    <i data-lucide="copy" class="w-4 h-4"></i>
                    <span>🔄 Clonar Servicios</span>
                </button>
            </form>
            @endif

            @if(Auth::user()->isPanelBasic() && $activeServicesCount >= 10)
            <button disabled class="px-4 py-2.5 rounded-xl bg-gray-800 text-gray-500 font-bold text-xs flex items-center gap-2 cursor-not-allowed">
                <i data-lucide="lock" class="w-4 h-4"></i>
                <span>Límite 10/10 (Actualiza a PRO)</span>
            </button>
            @else
            <button @click="newServiceModal = true" class="px-4 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold text-xs flex items-center gap-2 transition-all shadow-lg shadow-red-600/30 cursor-pointer">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                <span>+ Agregar Servicio Disponible</span>
            </button>
            @endif
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- GRILLA DE SERVICIOS DISPONIBLES CON STOCK EN TIEMPO REAL -->
    <!-- ======================================================== -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($services as $serv)
        @php
            $free = $serv->scoped_free_profiles ?? 0;
            $total = $serv->scoped_total_profiles ?? 0;
            $occupied = $serv->scoped_occupied_profiles ?? 0;
            $badge = $serv->scoped_status_badge ?? 'out_of_stock';
            $accountsCount = $serv->scoped_accounts_count ?? 0;
            $pctFree = $total > 0 ? round(($free / $total) * 100) : 0;
        @endphp
        <div class="rounded-2xl bg-[#111827] border border-gray-800 shadow-xl overflow-hidden flex flex-col justify-between hover:border-gray-700 transition-all">
            <!-- Header de Plataforma -->
            <div class="p-5 border-b border-gray-800/80 bg-[#0d1322]">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 rounded-xl flex items-center justify-center font-black text-white shadow-md text-sm shrink-0" style="background-color: {{ $serv->color_hex ?? '#E50914' }}">
                            {{ substr($serv->name, 0, 2) }}
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-white">{{ $serv->name }}</h4>
                            <div class="flex flex-wrap items-center gap-1.5 mt-0.5">
                                <span class="text-[10px] px-2 py-0.5 rounded-full bg-gray-800 text-gray-300 font-medium font-mono">
                                    {{ strtoupper($serv->category) }} · {{ $serv->max_profiles_default }} Perfiles Máx
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
                                    🏢 Catálogo Sincronizado
                                </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Botones de Edición / Eliminación (si es dueño o admin) -->
                    @if(Auth::user()->isAdmin() || $serv->user_id === Auth::id())
                    <div class="flex items-center gap-1">
                        <button type="button" @click="openEdit({{ json_encode($serv) }})" class="text-gray-400 hover:text-sky-400 p-1 cursor-pointer" title="Editar Servicio">
                            <i data-lucide="edit-3" class="w-4 h-4"></i>
                        </button>
                        <form method="POST" action="{{ route('services.destroy', $serv) }}" onsubmit="return confirm('¿Seguro que deseas eliminar este servicio?');" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-gray-400 hover:text-rose-400 p-1 cursor-pointer" title="Eliminar Plataforma">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </form>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Cuerpo: Disponibilidad de Stock en Tiempo Real -->
            <div class="p-5 flex-1 space-y-4">
                <!-- Estado y Capacidad -->
                <div class="flex items-center justify-between">
                    <span class="text-xs font-mono text-slate-400 font-semibold">{{ $accountsCount }} cuenta(s) en tu inventario</span>
                    <span class="text-[10px] font-bold px-2.5 py-0.5 rounded-full border font-mono {{ $badge === 'in_stock' ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30' : ($badge === 'low_stock' ? 'bg-amber-500/20 text-amber-300 border-amber-500/30' : 'bg-rose-500/20 text-rose-400 border-rose-500/30') }}">
                        {{ $badge === 'in_stock' ? '🟢 Listo para Entrega' : ($badge === 'low_stock' ? '🟡 Stock Bajo' : '🔴 Agotado') }}
                    </span>
                </div>

                <!-- Métricas Visuales de Capacidad y Perfiles Libres (Desplegable) -->
                <div class="p-4 rounded-2xl bg-[#0b0f19] border border-gray-800 space-y-3" x-data="{ openStock: false }">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-[10px] text-gray-400 uppercase tracking-wider block font-bold">Capacidad del Servicio</span>
                            <span class="text-xs text-gray-500 font-mono">{{ $total }} perfiles totales</span>
                        </div>
                        <button type="button" @click="openStock = !openStock" class="py-1 px-2.5 rounded-lg bg-gray-900 hover:bg-gray-800 text-[11px] text-sky-400 hover:text-sky-300 font-bold border border-gray-800 flex items-center gap-1.5 transition cursor-pointer">
                            <span x-text="openStock ? 'Cerrar Sección' : 'Abrir Sección & Ver Libres'"></span>
                            <i data-lucide="chevron-down" class="w-3.5 h-3.5 transition-transform duration-200" :class="openStock ? 'rotate-180 text-sky-400' : 'text-slate-400'"></i>
                        </button>
                    </div>

                    <div x-show="openStock" x-cloak x-collapse class="space-y-3 pt-3 border-t border-gray-800">
                        <div class="flex items-end justify-between">
                            <div>
                                <span class="text-[10px] text-gray-400 uppercase tracking-wider block font-bold">Perfiles Libres en Stock</span>
                                <div class="text-3xl font-black {{ $free > 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                                    {{ $free }}
                                    <span class="text-xs text-gray-500 font-normal">/ {{ $total }} capacidad total</span>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-xs font-mono font-bold {{ $free > 0 ? 'text-emerald-400' : 'text-gray-500' }}">{{ $pctFree }}% libre</span>
                            </div>
                        </div>

                        <!-- Barra de Progreso de Disponibilidad -->
                        <div class="w-full bg-slate-900 rounded-full h-2 overflow-hidden border border-gray-800">
                            <div class="h-2 rounded-full {{ $free > 2 ? 'bg-emerald-500' : ($free > 0 ? 'bg-amber-500' : 'bg-rose-500') }}" style="width: {{ $pctFree }}%"></div>
                        </div>

                        <!-- Desglose de perfiles -->
                        <div class="grid grid-cols-3 gap-2 pt-2 border-t border-gray-800/80 text-center">
                            <div class="p-1.5 rounded-lg bg-gray-900/60">
                                <span class="text-[9px] text-gray-400 block font-semibold uppercase">Libres</span>
                                <span class="text-xs font-mono font-bold text-emerald-400">{{ $free }}</span>
                            </div>
                            <div class="p-1.5 rounded-lg bg-gray-900/60">
                                <span class="text-[9px] text-gray-400 block font-semibold uppercase">Ocupados</span>
                                <span class="text-xs font-mono font-bold text-amber-400">{{ $occupied }}</span>
                            </div>
                            <div class="p-1.5 rounded-lg bg-gray-900/60">
                                <span class="text-[9px] text-gray-400 block font-semibold uppercase">Total Cupo</span>
                                <span class="text-xs font-mono font-bold text-white">{{ $total }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                @if($serv->default_rules)
                <div class="p-3 rounded-xl bg-gray-900/80 border border-gray-800/80 text-[10px] text-gray-400">
                    <span class="font-bold text-gray-300 block mb-0.5">Reglas de Uso para el Cliente:</span>
                    <p class="font-sans line-clamp-2 text-slate-300">{{ $serv->default_rules }}</p>
                </div>
                @endif
            </div>

            <!-- Footer: Acciones Rápidas -->
            <div class="p-4 border-t border-gray-800/80 bg-[#0e1424] flex items-center justify-between gap-2">
                <a href="{{ route('accounts.index', ['service_id' => $serv->id]) }}" class="px-3 py-2 rounded-xl bg-sky-500/10 hover:bg-sky-500/20 text-sky-400 hover:text-white border border-sky-500/20 font-bold text-xs flex items-center gap-1.5 transition-all">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    <span>+ Cargar Cuentas</span>
                </a>

                <a href="{{ route('sales.index') }}" class="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs flex items-center gap-1.5 shadow-md shadow-emerald-600/20 transition-all">
                    <i data-lucide="send" class="w-3.5 h-3.5"></i>
                    <span>Emitir a Cliente</span>
                </a>
            </div>
        </div>
        @empty
        <div class="col-span-full py-12 text-center bg-[#111827] rounded-3xl border border-gray-800 p-8 space-y-3">
            <div class="w-16 h-16 rounded-2xl bg-gray-800 text-gray-500 mx-auto flex items-center justify-center text-2xl">
                <i data-lucide="tv" class="w-8 h-8"></i>
            </div>
            <h4 class="text-base font-bold text-white">No hay servicios disponibles registrados</h4>
            <p class="text-xs text-gray-400 max-w-md mx-auto">
                Agrega nuevas plataformas de streaming a tu panel o sincroniza las plataformas oficiales de tu proveedor.
            </p>
            <div class="pt-2">
                <button @click="newServiceModal = true" class="px-5 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold text-xs shadow-lg shadow-red-600/30">
                    + Agregar Primer Servicio
                </button>
            </div>
        </div>
        @endforelse
    </div>

    <!-- ======================================================== -->
    <!-- MODAL: AGREGAR SERVICIO DISPONIBLE -->
    <!-- ======================================================== -->
    <div x-show="newServiceModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
        <div @click.away="newServiceModal = false" class="w-full max-w-lg bg-[#111827] border border-gray-800 rounded-3xl shadow-2xl p-6">
            <h3 class="text-base font-bold text-white mb-1">Agregar Servicio Disponible</h3>
            <p class="text-xs text-gray-400 mb-4">Registra una nueva plataforma de streaming en tu catálogo</p>

            <form method="POST" action="{{ route('services.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Nombre de la Plataforma *</label>
                    <input type="text" name="name" placeholder="Ej: Paramount+ UHD, HBO Max, Crunchyroll" required class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white focus:border-red-500">
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Categoría</label>
                        <select name="category" class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white">
                            <option value="video">Películas & Series</option>
                            <option value="music">Música</option>
                            <option value="iptv">IPTV / TV</option>
                            <option value="anime">Anime</option>
                            <option value="combo">Combo</option>
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
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Reglas de Uso para el Cliente (Opcional)</label>
                    <textarea name="default_rules" rows="3" placeholder="- Solo ingresar al perfil asignado con su PIN&#10;- Prohibido cambiar correo o contraseña&#10;- Garantía válida por 30 días" class="w-full px-3.5 py-2 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white focus:border-red-500"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-800">
                    <button type="button" @click="newServiceModal = false" class="px-4 py-2 text-xs text-gray-400 hover:text-white cursor-pointer">Cancelar</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold text-xs shadow-lg shadow-red-600/30 cursor-pointer">Guardar Servicio</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL: EDITAR SERVICIO DISPONIBLE -->
    <!-- ======================================================== -->
    <div x-show="editServiceModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
        <div @click.away="editServiceModal = false" class="w-full max-w-lg bg-[#111827] border border-gray-800 rounded-3xl shadow-2xl p-6">
            <h3 class="text-base font-bold text-white mb-1">Editar Servicio Disponible</h3>
            <p class="text-xs text-gray-400 mb-4">Modifica los detalles y reglas de la plataforma</p>

            <form :action="'/services/' + editingService.id" method="POST" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Nombre de la Plataforma *</label>
                    <input type="text" name="name" x-model="editingService.name" required class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white focus:border-sky-500">
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Categoría</label>
                        <select name="category" x-model="editingService.category" class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white">
                            <option value="video">Películas & Series</option>
                            <option value="music">Música</option>
                            <option value="iptv">IPTV / TV</option>
                            <option value="anime">Anime</option>
                            <option value="combo">Combo</option>
                            <option value="other">Otro</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Color Distintivo</label>
                        <input type="color" name="color_hex" x-model="editingService.color_hex" class="w-full h-10 rounded-xl bg-[#0b0f19] border border-gray-800 p-1 cursor-pointer">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Perfiles Máx</label>
                        <input type="number" name="max_profiles_default" x-model="editingService.max_profiles_default" min="1" max="10" class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Estado</label>
                    <select name="status" x-model="editingService.status" class="w-full px-3.5 py-2 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white">
                        <option value="active">Activo</option>
                        <option value="inactive">Inactivo</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Reglas de Uso para el Cliente</label>
                    <textarea name="default_rules" x-model="editingService.default_rules" rows="3" class="w-full px-3.5 py-2 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white focus:border-sky-500"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-800">
                    <button type="button" @click="editServiceModal = false" class="px-4 py-2 text-xs text-gray-400 hover:text-white cursor-pointer">Cancelar</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs shadow-lg shadow-sky-600/30 cursor-pointer">Actualizar Servicio</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
