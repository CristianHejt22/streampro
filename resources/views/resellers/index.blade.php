@extends('layouts.app')

@section('title', 'Super Panel - Control de Paneles y Revendedores')
@section('page_title', 'Super Panel de Revendedores & Paneles Clonados')
@section('page_subtitle', 'Control maestro de contraseñas, licencias en consignación 48hs y clonación de paneles ($15.000 / 30 días)')

@section('content')
<div class="space-y-6" x-data="{
    newResellerModal: false,
    rechargeModal: false,
    changePassModal: false,
    viewPassModal: false,
    clonePanelModal: false,
    commissionsModal: false,
    selectedReseller: null,
    revealedPassword: '',
    revealedAntiPhishing: '',
    revealedEmail: '',
    revealedName: '',
    loadingPass: false,
    copyText(text, label) {
        navigator.clipboard.writeText(text);
        alert(label + ' copiado al portapapeles: ' + text);
    },
    async revealPassword(res) {
        this.selectedReseller = res;
        this.loadingPass = true;
        this.viewPassModal = true;
        try {
            const resp = await fetch('/resellers/' + res.id + '/reveal-password');
            const data = await resp.json();
            if (data.success) {
                this.revealedPassword = data.password;
                this.revealedAntiPhishing = data.anti_phishing_code;
                this.revealedEmail = data.email;
                this.revealedName = data.name;
            } else {
                this.revealedPassword = 'Error al consultar clave';
            }
        } catch (e) {
            this.revealedPassword = 'Error de conexión';
        } finally {
            this.loadingPass = false;
        }
    }
}">

    <!-- Top Summary Banner -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-[#111827] p-5 rounded-2xl border border-gray-800 flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold text-gray-400 uppercase">Paneles Registrados</span>
                <h4 class="text-2xl font-black text-white mt-1">{{ $resellers->total() }}</h4>
            </div>
            <div class="w-12 h-12 rounded-xl bg-red-600/20 text-red-400 flex items-center justify-center text-xl">
                <i data-lucide="users" class="w-6 h-6"></i>
            </div>
        </div>

        <div class="bg-[#111827] p-5 rounded-2xl border border-gray-800 flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold text-gray-400 uppercase">Margen Revendedor</span>
                <h4 class="text-2xl font-black text-emerald-400 mt-1">40%</h4>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-600/20 text-emerald-400 flex items-center justify-center text-xl">
                <i data-lucide="percent" class="w-6 h-6"></i>
            </div>
        </div>

        <div class="bg-[#111827] p-5 rounded-2xl border border-gray-800 flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold text-gray-400 uppercase">Copia Panel (30 Días)</span>
                <h4 class="text-2xl font-black text-sky-400 mt-1">$15.000</h4>
            </div>
            <div class="w-12 h-12 rounded-xl bg-sky-600/20 text-sky-400 flex items-center justify-center text-xl">
                <i data-lucide="copy" class="w-6 h-6"></i>
            </div>
        </div>

        <div class="bg-[#111827] p-5 rounded-2xl border border-gray-800 flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold text-gray-400 uppercase">Comisión Patrocinador</span>
                <h4 class="text-2xl font-black text-purple-400 mt-1">25%</h4>
            </div>
            <div class="w-12 h-12 rounded-xl bg-purple-600/20 text-purple-400 flex items-center justify-center text-xl">
                <i data-lucide="trending-up" class="w-6 h-6"></i>
            </div>
        </div>
    </div>

    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h3 class="text-sm font-bold text-white uppercase tracking-wider">Control Maestro de Paneles Generados</h3>
            <p class="text-xs text-gray-400">Como Super Panel puedes ver y cambiar cualquier contraseña, activar copias clonadas y ajustar comisiones.</p>
        </div>

        <button @click="newResellerModal = true" class="px-4 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold text-xs flex items-center gap-2 transition-all shadow-lg shadow-red-600/30">
            <i data-lucide="user-plus" class="w-4 h-4"></i>
            <span>+ Generar Nuevo Panel</span>
        </button>
    </div>

    <!-- Resellers Table -->
    <div class="rounded-2xl bg-[#111827] border border-gray-800 shadow-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-gray-800 text-[11px] font-bold text-gray-400 uppercase tracking-wider bg-[#0d1322]">
                        <th class="py-3.5 px-4">Panel / Revendedor</th>
                        <th class="py-3.5 px-4">Tipo & Patrocinador</th>
                        <th class="py-3.5 px-4">Saldo Billetera</th>
                        <th class="py-3.5 px-4">Comisiones</th>
                        <th class="py-3.5 px-4">Suscripción Panel</th>
                        <th class="py-3.5 px-4">Anti-Phishing</th>
                        <th class="py-3.5 px-4 text-right">Control de Acceso</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800/60 text-xs text-gray-200">
                    @forelse($resellers as $res)
                    @php
                        $isCloned = $res->panel_type === 'cloned';
                        $daysLeft = $res->days_remaining_panel;
                        $isExpired = $res->isPanelExpired();
                    @endphp
                    <tr class="hover:bg-gray-800/30 transition-colors">
                        <td class="py-3 px-4">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-white text-sm block">{{ $res->name }}</span>
                                @if($res->role === 'super_reseller')
                                <span class="text-[9px] font-mono px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/30 font-bold">SUPER</span>
                                @endif
                            </div>
                            <span class="text-[11px] text-gray-400 block">{{ $res->email }}</span>
                            <span class="text-[10px] text-sky-400 font-mono">{{ $res->brand_name }} (Ref: {{ $res->referral_code }})</span>
                        </td>
                        <td class="py-3 px-4">
                            @if($isCloned)
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-500/20 text-purple-400 border border-purple-500/30 block w-max">
                                📋 Panel Clonado
                            </span>
                            @if($res->parent)
                            <span class="text-[10px] text-gray-400 block mt-1">Patrocinador: <strong class="text-white">{{ $res->parent->name }}</strong></span>
                            @endif
                            @else
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-cyan-500/20 text-cyan-400 border border-cyan-500/30 block w-max">
                                🛡️ Panel Master ({{ ucfirst($res->reseller_plan) }})
                            </span>
                            @endif
                        </td>
                        <td class="py-3 px-4">
                            <span class="text-sm font-black text-emerald-400 block">${{ number_format($res->balance, 2) }}</span>
                            <button @click="selectedReseller = {{ json_encode($res) }}; rechargeModal = true;" class="text-[10px] text-cyan-400 hover:underline font-semibold cursor-pointer">
                                + Recargar Saldo
                            </button>
                        </td>
                        <td class="py-3 px-4 text-xs font-mono">
                            <span class="text-emerald-400 font-bold">Revendedor: {{ $res->reseller_commission_pct ?? 40 }}%</span>
                            <span class="text-purple-400 block text-[11px]">Patrocinador: {{ $res->parent_commission_pct ?? 25 }}%</span>
                            <button @click="selectedReseller = {{ json_encode($res) }}; commissionsModal = true;" class="text-[10px] text-gray-400 hover:text-white underline block mt-0.5 cursor-pointer">
                                Ajustar %
                            </button>
                        </td>
                        <td class="py-3 px-4">
                            @if($isCloned)
                                @if($isExpired)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/20 text-rose-400 border border-rose-500/30 block w-max">
                                    🔴 Suspendido ($15.000 Vencido)
                                </span>
                                @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 block w-max">
                                    🟢 Activo ({{ $daysLeft ?? 30 }} días restantes)
                                </span>
                                @endif
                                <button @click="selectedReseller = {{ json_encode($res) }}; clonePanelModal = true;" class="text-[10px] text-sky-400 hover:underline block mt-1 cursor-pointer">
                                    Renovar 30d ($15.000)
                                </button>
                            @else
                            <button @click="selectedReseller = {{ json_encode($res) }}; clonePanelModal = true;" class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-sky-400 text-[10px] font-bold border border-slate-700 transition-colors cursor-pointer">
                                + Convertir a Clonado ($15k)
                            </button>
                            @endif
                        </td>
                        <td class="py-3 px-4">
                            <code class="px-2 py-0.5 rounded bg-black/60 border border-gray-800 text-amber-400 font-mono text-[10px] block w-max">
                                {{ $res->anti_phishing_code ?: 'SEC-' . $res->id }}
                            </code>
                        </td>
                        <td class="py-3 px-4 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                <!-- Botón VER CLAVE (Super Admin) -->
                                <button @click="revealPassword({{ json_encode($res) }})" title="Ver Contraseña Actual del Panel" class="px-2.5 py-1.5 rounded-xl bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 border border-amber-500/30 font-bold text-xs flex items-center gap-1 transition-all cursor-pointer">
                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                    <span>Ver Clave</span>
                                </button>

                                <!-- Botón CAMBIAR CLAVE (Super Admin) -->
                                <button @click="selectedReseller = {{ json_encode($res) }}; changePassModal = true;" title="Cambiar Contraseña del Panel" class="px-2.5 py-1.5 rounded-xl bg-sky-500/10 hover:bg-sky-500/20 text-sky-400 border border-sky-500/30 font-bold text-xs flex items-center gap-1 transition-all cursor-pointer">
                                    <i data-lucide="key" class="w-3.5 h-3.5"></i>
                                    <span>Cambiar</span>
                                </button>

                                <form method="POST" action="{{ route('resellers.toggle', $res) }}" class="inline">
                                    @csrf
                                    <button type="submit" title="{{ $res->status === 'active' ? 'Suspender panel' : 'Reactivar panel' }}" class="p-1.5 rounded-lg {{ $res->status === 'active' ? 'text-rose-400 hover:bg-rose-500/10' : 'text-emerald-400 hover:bg-emerald-500/10' }} cursor-pointer">
                                        <i data-lucide="power" class="w-3.5 h-3.5"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-12 text-center text-gray-400">
                            No hay paneles generados todavía.
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
    <!-- MODAL 1: VER CONTRASEÑA ACTUAL DEL PANEL -->
    <!-- ========================================== -->
    <div x-show="viewPassModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm" @click.self="viewPassModal = false">
        <div class="w-full max-w-md bg-[#111827] border border-gray-800 rounded-3xl shadow-2xl p-6 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-gray-800">
                <div class="flex items-center gap-2 text-amber-400">
                    <i data-lucide="shield-check" class="w-5 h-5"></i>
                    <h3 class="font-bold text-white text-base">Credenciales del Panel</h3>
                </div>
                <button @click="viewPassModal = false" class="text-gray-400 hover:text-white">✕</button>
            </div>

            <div x-show="loadingPass" class="text-center py-6 text-gray-400 text-xs">
                Cargando clave desencriptada desde el Super Panel...
            </div>

            <div x-show="!loadingPass" class="space-y-3 text-xs font-mono">
                <div class="p-3 rounded-2xl bg-[#0b0f19] border border-gray-800">
                    <span class="text-[10px] text-gray-400 uppercase font-sans block mb-1">Nombre del Panel</span>
                    <span class="text-white font-bold" x-text="revealedName"></span>
                </div>

                <div class="p-3 rounded-2xl bg-[#0b0f19] border border-gray-800">
                    <span class="text-[10px] text-gray-400 uppercase font-sans block mb-1">Correo Electrónico de Acceso</span>
                    <div class="flex items-center justify-between">
                        <span class="text-white font-bold" x-text="revealedEmail"></span>
                        <button @click="copyText(revealedEmail, 'Correo')" class="text-sky-400 hover:text-white text-xs font-sans">Copiar</button>
                    </div>
                </div>

                <div class="p-3 rounded-2xl bg-[#0b0f19] border border-amber-500/30 bg-amber-500/5">
                    <span class="text-[10px] text-amber-400 uppercase font-sans font-bold block mb-1">Contraseña Actual del Panel</span>
                    <div class="flex items-center justify-between">
                        <span class="text-amber-300 font-black text-sm" x-text="revealedPassword"></span>
                        <button @click="copyText(revealedPassword, 'Contraseña')" class="px-2.5 py-1 rounded-lg bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 text-xs font-sans font-bold">Copiar Clave</button>
                    </div>
                </div>

                <div class="p-3 rounded-2xl bg-[#0b0f19] border border-gray-800">
                    <span class="text-[10px] text-gray-400 uppercase font-sans block mb-1">Código Anti-Phishing</span>
                    <span class="text-emerald-400 font-bold" x-text="revealedAntiPhishing"></span>
                </div>
            </div>

            <div class="flex justify-end pt-2">
                <button @click="viewPassModal = false" class="px-4 py-2 rounded-xl bg-gray-800 text-gray-300 text-xs font-bold hover:bg-gray-700">Cerrar</button>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL 2: CAMBIAR CONTRASEÑA DIRECTA (SUPER ADMIN) -->
    <!-- ========================================== -->
    <div x-show="changePassModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm" @click.self="changePassModal = false">
        <div class="w-full max-w-md bg-[#111827] border border-gray-800 rounded-3xl shadow-2xl p-6 space-y-4">
            <h3 class="text-base font-bold text-white mb-1">Cambiar Contraseña del Panel</h3>
            <p class="text-xs text-gray-400">Modifica la clave de acceso para <strong class="text-white" x-text="selectedReseller?.name"></strong> directamente.</p>

            <form :action="'/resellers/' + selectedReseller?.id + '/update-password'" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Nueva Contraseña de Acceso *</label>
                    <input type="password" name="new_password" placeholder="Mínimo 6 caracteres" required class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white focus:border-red-500">
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="changePassModal = false" class="px-4 py-2 text-xs text-gray-400 hover:text-white">Cancelar</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold text-xs shadow-lg shadow-red-600/30">Guardar Nueva Contraseña</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL 3: CLONAR PANEL ($15.000 / 30 DÍAS) -->
    <!-- ========================================== -->
    <div x-show="clonePanelModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm" @click.self="clonePanelModal = false">
        <div class="w-full max-w-md bg-[#111827] border border-gray-800 rounded-3xl shadow-2xl p-6 space-y-4">
            <div class="flex items-center gap-2 text-sky-400">
                <i data-lucide="copy" class="w-5 h-5"></i>
                <h3 class="font-bold text-white text-base">Clonar / Renovar Panel ($15.000 / 30 Días)</h3>
            </div>
            <p class="text-xs text-gray-400">
                Configura este panel como copia de revendedor por <strong>$15.000 x 30 días</strong>. El revendedor obtiene <strong>40% de ganancia</strong> y el panel patrocinador obtiene <strong>25% de comisión</strong> por copia de servicios.
            </p>

            <form :action="'/resellers/' + selectedReseller?.id + '/clone-panel'" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Panel Patrocinador (Quien ofrece el servicio y gana el 25%)</label>
                    <select name="parent_id" class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white">
                        <option value="">-- Sin Patrocinador (Super Panel Directo) --</option>
                        @foreach($superResellersList as $sres)
                        <option value="{{ $sres->id }}">{{ $sres->name }} ({{ $sres->brand_name }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="p-3 rounded-2xl bg-sky-500/10 border border-sky-500/20 text-sky-300 text-xs">
                    ℹ️ Cuota mensual: <strong>$15.000</strong> por 30 días. Si no se abona a término, el panel se suspende automáticamente.
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="clonePanelModal = false" class="px-4 py-2 text-xs text-gray-400 hover:text-white">Cancelar</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs shadow-lg">Activar Panel Clonado</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL 4: AJUSTAR COMISIONES (40% / 25%) -->
    <!-- ========================================== -->
    <div x-show="commissionsModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm" @click.self="commissionsModal = false">
        <div class="w-full max-w-md bg-[#111827] border border-gray-800 rounded-3xl shadow-2xl p-6 space-y-4">
            <h3 class="text-base font-bold text-white mb-1">Ajustar Porcentajes de Ganancia</h3>
            <p class="text-xs text-gray-400">Configuración de márgenes para <strong class="text-white" x-text="selectedReseller?.name"></strong></p>

            <form :action="'/resellers/' + selectedReseller?.id + '/update-commissions'" method="POST" class="space-y-4">
                @csrf
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-emerald-400 mb-1">Ganancia Revendedor (%)</label>
                        <input type="number" step="0.5" name="reseller_commission_pct" :value="selectedReseller?.reseller_commission_pct || 40.00" class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white font-bold">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-purple-400 mb-1">Comisión Patrocinador (%)</label>
                        <input type="number" step="0.5" name="parent_commission_pct" :value="selectedReseller?.parent_commission_pct || 25.00" class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white font-bold">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="commissionsModal = false" class="px-4 py-2 text-xs text-gray-400 hover:text-white">Cancelar</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-lg">Guardar Comisiones</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL 5: RECARGA DE SALDO -->
    <!-- ========================================== -->
    <div x-show="rechargeModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm" @click.self="rechargeModal = false">
        <div class="w-full max-w-md bg-[#111827] border border-gray-800 rounded-3xl shadow-2xl p-6 space-y-4">
            <h3 class="text-base font-bold text-white mb-1">Recargar Saldo de Billetera</h3>
            <p class="text-xs text-gray-400">Acreditar saldo prepago a: <span class="text-white font-bold" x-text="selectedReseller?.name"></span></p>

            <form :action="'/resellers/' + selectedReseller?.id + '/recharge'" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-emerald-400 mb-1">Monto a Acreditar ($) *</label>
                    <input type="number" step="0.01" name="amount" placeholder="100.00" required class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-base text-white font-black text-emerald-400">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Motivo / Notas</label>
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
    <!-- MODAL 6: NUEVO REVENDEDOR / PANEL -->
    <!-- ========================================== -->
    <div x-show="newResellerModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm" @click.self="newResellerModal = false">
        <div class="w-full max-w-md bg-[#111827] border border-gray-800 rounded-3xl shadow-2xl p-6 space-y-4">
            <h3 class="text-base font-bold text-white mb-1">Generar Nuevo Panel de Revendedor</h3>
            <p class="text-xs text-gray-400">Crea el acceso al panel con clave guardada en el Super Panel</p>

            <form method="POST" action="{{ route('resellers.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Nombre Completo *</label>
                    <input type="text" name="name" placeholder="Ej: Nicolás Gómez" required class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white focus:border-red-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Email de Acceso *</label>
                        <input type="email" name="email" placeholder="panel@revendedor.com" required class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white focus:border-red-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Contraseña Inicial *</label>
                        <input type="password" name="password" placeholder="Mínimo 6 caracteres" required class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white focus:border-red-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Tipo de Panel</label>
                        <select name="panel_type" class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white">
                            <option value="master" selected>Panel Master</option>
                            <option value="cloned">Panel Clonado ($15k / 30d)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Rol</label>
                        <select name="role" class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white">
                            <option value="reseller" selected>Reseller Regular</option>
                            <option value="super_reseller">Super Reseller (Mayorista)</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Patrocinador Padre</label>
                        <select name="parent_id" class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white">
                            <option value="">-- Ninguno (Directo Super Panel) --</option>
                            @foreach($superResellersList as $sres)
                            <option value="{{ $sres->id }}">{{ $sres->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Saldo Inicial ($)</label>
                        <input type="number" step="0.01" name="initial_balance" value="0.00" class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white font-bold text-emerald-400">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Marca Blanca del Panel</label>
                    <input type="text" name="brand_name" placeholder="Ej: Nova Stream Digital" class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white focus:border-red-500">
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-800">
                    <button type="button" @click="newResellerModal = false" class="px-4 py-2 text-xs text-gray-400 hover:text-white">Cancelar</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold text-xs shadow-lg shadow-red-600/30">Crear Panel</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
