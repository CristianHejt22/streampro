@extends('layouts.app')

@section('title', 'Servicios & Planes de Streaming')
@section('page_title', 'Plataformas & Planes')
@section('page_subtitle', 'Configuración de catálogo, precios para clientes finales y mayoristas para revendedores')

@section('content')
<div class="space-y-6" x-data="{
    newServiceModal: false,
    newPlanModal: false,
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

    <!-- Top Action Bar -->
    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-sm font-bold text-white uppercase tracking-wider">Catálogo de Plataformas</h3>
            <p class="text-xs text-gray-400">Servicios disponibles para venta, reglas de entrega y planes</p>
        </div>

        <button @click="newServiceModal = true" class="px-4 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold text-xs flex items-center gap-2 transition-all shadow-lg shadow-red-600/30">
            <i data-lucide="plus-circle" class="w-4 h-4"></i>
            <span>Nueva Plataforma</span>
        </button>
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
                    <div class="p-3 rounded-xl bg-[#0b0f19] border border-gray-800 flex items-center justify-between text-xs">
                        <div>
                            <div class="font-bold text-white">{{ $plan->name }}</div>
                            <div class="text-[10px] text-gray-400 mt-0.5">
                                {{ $plan->duration_days }} días · {{ $plan->modality === 'profile' ? 'Pantalla con PIN' : 'Cuenta Completa' }}
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="font-black text-emerald-400">${{ number_format($plan->price, 2) }}</div>
                            <div class="text-[10px] text-cyan-400 font-semibold">Mayorista: ${{ number_format($plan->reseller_price, 2) }}</div>
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

</div>
@endsection
