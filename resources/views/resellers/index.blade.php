@extends('layouts.app')

@section('title', Auth::user()->isAdmin() ? 'Super Panel - Control Maestro' : 'Panel de Proveedor - Mis Revendedores')
@section('page_title', Auth::user()->isAdmin() ? 'Super Panel de Revendedores & Paneles Clonados' : 'Gestión de Mis Revendedores & Tiendas Clonadas')
@section('page_subtitle', Auth::user()->isAdmin() ? 'Control maestro de licencias, activación de paneles y tiendas clonadas ($15.000 / mes: $8.000 Admin | $7.000 Proveedor)' : 'Administración de revendedores afiliados, activación con créditos y precios mayoristas')

@section('content')
<div class="space-y-6" x-data="{
    newResellerModal: false,
    rechargeModal: false,
    changePassModal: false,
    viewPassModal: false,
    clonePanelModal: false,
    commissionsModal: false,
    serviceCommissionsModal: false,
    calculatorModal: false,
    sellCreditsModal: false,
    masterActivationModal: false,
    creditActivationModal: false,
    transferAuditModal: false,
    activeTransfer: null,
    transferReceiptUrl: '',
    transferResellerName: '',
    transferAmount: '',
    transferMethod: '',
    transferRef: '',
    transferNotes: '',
    getResellerFromScript(id) {
        const el = document.getElementById('reseller-data-' + id);
        return el ? JSON.parse(el.textContent) : null;
    },
    getTransferFromScript(id) {
        const el = document.getElementById('transfer-data-' + id);
        return el ? JSON.parse(el.textContent) : null;
    },
    openTransferAudit(tr) {
        if (!tr) return;
        this.activeTransfer = tr;
        this.transferReceiptUrl = tr.receipt_path ? ('/resellers/balance-transfers/' + tr.id + '/receipt') : '';
        this.transferResellerName = tr.reseller ? tr.reseller.name : 'Revendedor';
        this.transferAmount = parseFloat(tr.amount).toLocaleString('es-AR', {minimumFractionDigits: 2});
        this.transferMethod = tr.payment_method || 'transfer';
        this.transferRef = tr.reference_code || '';
        this.transferNotes = tr.reseller_notes || '';
        this.transferAuditModal = true;
    },
    openTransferAuditFromScript(id) {
        const tr = this.getTransferFromScript(id);
        if (tr) this.openTransferAudit(tr);
    },
    selectedCreditPack: 'basic_10',
    selectedTier: 'basic',
    selectedDays: 30,
    calcPrice: 4500,
    calcMonths: 1,
    calcMargin: 40,
    calcParentPct: 25,
    serviceReseller: null,
    selectedReseller: null,
    openServiceCommissions(res) {
        if (!res) return;
        this.serviceReseller = res;
        this.serviceCommissionsModal = true;
    },
    openServiceCommissionsFromScript(id) {
        const res = this.getResellerFromScript(id);
        if (res) this.openServiceCommissions(res);
    },
    openPlanPricesFromScript(id) {
        const res = this.getResellerFromScript(id);
        if (res) this.openPlanPrices(res);
    },
    openWhatsAppPrefFromScript(id) {
        const res = this.getResellerFromScript(id);
        if (res) this.openWhatsAppPref(res);
    },
    openRechargeFromScript(id) {
        this.selectedReseller = this.getResellerFromScript(id);
        this.rechargeModal = true;
    },
    openMasterActivationFromScript(id) {
        this.selectedReseller = this.getResellerFromScript(id);
        this.masterActivationModal = true;
    },
    openSellCreditsFromScript(id) {
        this.selectedReseller = this.getResellerFromScript(id);
        this.sellCreditsModal = true;
    },
    openCreditActivationFromScript(id) {
        this.selectedReseller = this.getResellerFromScript(id);
        this.creditActivationModal = true;
    },
    openClonePanelFromScript(id) {
        this.selectedReseller = this.getResellerFromScript(id);
        this.clonePanelModal = true;
    },
    revealPasswordFromScript(id) {
        const res = this.getResellerFromScript(id);
        if (res) this.revealPassword(res);
    },
    openChangePassFromScript(id) {
        this.selectedReseller = this.getResellerFromScript(id);
        this.changePassModal = true;
    },
    planPricesModal: false,
    selectedPlanPricesReseller: null,
    currentPlanCosts: {},
    openPlanPrices(res) {
        this.selectedPlanPricesReseller = res;
        this.currentPlanCosts = {};
        if (res.custom_plan_prices && Array.isArray(res.custom_plan_prices)) {
            res.custom_plan_prices.forEach(p => {
                this.currentPlanCosts[p.service_plan_id] = p.provider_cost;
            });
        }
        this.planPricesModal = true;
    },
    whatsappPrefModal: false,
    selectedPrefReseller: null,
    currentWhatsAppPref: 'auto',
    openWhatsAppPref(res) {
        this.selectedPrefReseller = res;
        this.currentWhatsAppPref = res.whatsapp_support_preference || 'auto';
        this.whatsappPrefModal = true;
    },
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
                <h4 class="text-2xl font-black text-white mt-1">{{ $stats['total_resellers'] ?? (method_exists($resellers, 'total') ? $resellers->total() : $resellers->count()) }}</h4>
                <span class="text-[10px] text-amber-400 font-mono">{{ $stats['basic_panels'] ?? 0 }} Básicos · {{ $stats['pro_panels'] ?? 0 }} PRO</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-red-600/20 text-red-400 flex items-center justify-center text-xl">
                <i data-lucide="users" class="w-6 h-6"></i>
            </div>
        </div>

        <div class="bg-[#111827] p-5 rounded-2xl border border-gray-800 flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold text-gray-400 uppercase">Estado de Paneles</span>
                <h4 class="text-2xl font-black text-emerald-400 mt-1">{{ $stats['active_panels'] ?? 0 }} Activos</h4>
                <span class="text-[10px] text-rose-400 font-mono">{{ $stats['expired_panels'] ?? 0 }} Vencidos / Suspendidos</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-600/20 text-emerald-400 flex items-center justify-center text-xl">
                <i data-lucide="check-circle" class="w-6 h-6"></i>
            </div>
        </div>

        <div class="bg-[#111827] p-5 rounded-2xl border border-gray-800 flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold text-gray-400 uppercase">Saldo en Billeteras</span>
                <h4 class="text-2xl font-black text-sky-400 mt-1">${{ number_format($stats['total_wallets_balance'] ?? 0, 2) }}</h4>
                <span class="text-[10px] text-gray-400">Fondos activos para compras</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-sky-600/20 text-sky-400 flex items-center justify-center text-xl">
                <i data-lucide="wallet" class="w-6 h-6"></i>
            </div>
        </div>

        <div class="bg-[#111827] p-5 rounded-2xl border border-gray-800 flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold text-gray-400 uppercase">Créditos de Activación</span>
                <h4 class="text-2xl font-black text-purple-400 mt-1">
                    @if(Auth::user()->isAdmin())
                        <span class="text-emerald-400">∞</span> <span class="text-xs text-emerald-400 font-sans uppercase font-bold">Ilimitados</span>
                    @else
                        {{ $stats['total_credits_basic'] ?? 0 }} B / {{ $stats['total_credits_pro'] ?? 0 }} P
                    @endif
                </h4>
                <span class="text-[10px] text-purple-300 font-mono">Básicos ($2.5k) · PRO ($4k)</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-purple-600/20 text-purple-400 flex items-center justify-center text-xl">
                <i data-lucide="gem" class="w-6 h-6"></i>
            </div>
        </div>
    </div>

    @if(Auth::user()->isAdmin())
    <!-- CLARIFICACIÓN DEL MODELO DE NEGOCIO: ROL SUPER ADMIN CENTRAL -->
    <div class="p-4 rounded-3xl bg-gradient-to-r from-blue-950/40 via-slate-900 to-indigo-950/30 border border-blue-500/30 shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-2xl bg-blue-500/20 text-blue-400 border border-blue-500/40 flex items-center justify-center shrink-0 mt-0.5">
                <i data-lucide="shield-check" class="w-5 h-5"></i>
            </div>
            <div class="text-xs space-y-1">
                <h4 class="font-black text-white text-sm flex items-center gap-2">
                    <span>Super Admin Central: Solo Paneles & Clonaciones Mensuales</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 font-mono font-bold">0% Comisión en Servicios</span>
                </h4>
                <p class="text-slate-300 leading-relaxed">
                    El Super Admin Central monetiza exclusivamente mediante la provisión de <strong>paneles (créditos)</strong> y el cobro de <strong>tiendas clonadas ($8.000/mes por tienda)</strong>. 
                    <strong class="text-white">NO cobra ninguna comisión ni ganancia sobre las cuentas o servicios ofrecidos por cada proveedor.</strong>
                </p>
                <p class="text-slate-400 text-[11px]">
                    El Super Admin es totalmente independiente para elegir libremente los proveedores y servicios que considere convenientes ofrecer en su panel de venta propio.
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <span class="px-3 py-1.5 rounded-xl bg-blue-500/10 border border-blue-500/30 text-blue-300 font-mono text-xs font-bold">
                $8.000 / Tienda Clonada
            </span>
        </div>
    </div>

    <!-- PREFERENCIAS DEL SUPER PANEL: ARQUITECTURA DE CANALES DE WHATSAPP -->
    <div class="p-5 rounded-3xl bg-gradient-to-r from-gray-900 via-[#10182b] to-gray-900 border border-emerald-500/30 shadow-2xl space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-gray-800">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-emerald-500/20 text-emerald-400 border border-emerald-500/40 flex items-center justify-center font-bold">
                    <i data-lucide="message-circle" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-sm font-black text-white uppercase tracking-wider flex items-center gap-2">
                        <span>Configuración de WhatsApp & Canales de Soporte</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 font-mono">Regla 3 Niveles</span>
                    </h3>
                    <p class="text-xs text-gray-400">Admin solo soporte de panel propio · Proveedor solo para revendedor · Revendedor para cliente final</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="px-3 py-1 rounded-xl bg-gray-950 border border-gray-800 text-[11px] text-gray-300 font-mono">
                    {{ $stats['resellers_with_whatsapp'] ?? 0 }} / {{ $stats['total_resellers'] ?? 0 }} Revendedores con WhatsApp Activo
                </span>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.settings.whatsapp_support') }}" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Canal 1: Admin Central (Panel Principal Propio) -->
                <div class="p-4 rounded-2xl bg-[#0b0f19] border border-gray-800 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-emerald-400 uppercase tracking-wider flex items-center gap-1.5">
                            <i data-lucide="shield" class="w-3.5 h-3.5"></i>
                            <span>1. Admin Central (Panel Propio)</span>
                        </span>
                        <span class="px-2 py-0.5 rounded text-[9px] font-black bg-emerald-500/20 text-emerald-300">EXCLUSIVO</span>
                    </div>
                    <p class="text-[11px] text-gray-400 leading-relaxed">
                        WhatsApp oficial para soporte técnico del sistema, asistencia directa al panel principal propio y convenios administrativos con proveedores.
                    </p>
                    <div class="pt-1 flex items-center gap-2">
                        <input type="text" name="whatsapp_support_admin_phone" value="{{ $whatsappAdminPhone ?? '' }}" placeholder="5491199887766" class="w-full p-2.5 rounded-xl bg-gray-900 border border-gray-700 text-xs text-white outline-none focus:border-emerald-500 font-mono">
                        <button type="submit" class="px-3.5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-md transition-all cursor-pointer shrink-0">
                            Guardar
                        </button>
                    </div>
                </div>

                <!-- Canal 2: Proveedor -> Revendedor -->
                <div class="p-4 rounded-2xl bg-[#0b0f19] border border-gray-800 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-teal-400 uppercase tracking-wider flex items-center gap-1.5">
                            <i data-lucide="network" class="w-3.5 h-3.5"></i>
                            <span>2. Proveedor ➔ Revendedor</span>
                        </span>
                        <span class="px-2 py-0.5 rounded text-[9px] font-black bg-teal-500/20 text-teal-300">MAYORISTA</span>
                    </div>
                    <p class="text-[11px] text-gray-400 leading-relaxed">
                        El WhatsApp de cada proveedor se muestra <strong>únicamente a sus revendedores afiliados</strong> para recargas de saldo, compras de paneles y consultas técnicas.
                    </p>
                    <div class="pt-2 text-[11px] text-teal-300 font-mono flex items-center gap-2">
                        <i data-lucide="check" class="w-3.5 h-3.5 text-teal-400"></i>
                        <span>Los clientes finales nunca ven este número</span>
                    </div>
                </div>

                <!-- Canal 3: Revendedor -> Cliente Final -->
                <div class="p-4 rounded-2xl bg-[#0b0f19] border border-gray-800 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-sky-400 uppercase tracking-wider flex items-center gap-1.5">
                            <i data-lucide="users" class="w-3.5 h-3.5"></i>
                            <span>3. Revendedor ➔ Cliente Final</span>
                        </span>
                        <span class="px-2 py-0.5 rounded text-[9px] font-black bg-sky-500/20 text-sky-300">COMERCIAL</span>
                    </div>
                    <p class="text-[11px] text-gray-400 leading-relaxed">
                        Cada revendedor atiende directamente a sus propios clientes en la Bóveda y Catálogo con su número configurado en Marca Blanca.
                    </p>
                    <div class="pt-2 flex items-center justify-between text-[11px]">
                        <span class="text-gray-400">Sin WhatsApp asignado:</span>
                        <span class="font-bold text-amber-400 font-mono">{{ $stats['resellers_without_whatsapp'] ?? 0 }} revendedores</span>
                    </div>
                </div>
            </div>
        </form>
    </div>
    @endif

    @if(Auth::user()->isSuperReseller() || Auth::user()->isAdmin())
    <!-- ======================================================== -->
    <!-- VISUALIZADOR DE CRÉDITOS PRO Y BÁSICOS EN PROVEEDORES / ADMIN -->
    <!-- ======================================================== -->
    <div class="rounded-3xl bg-gradient-to-r from-[#141b2d] via-slate-900 to-[#141b2d] border-2 border-purple-500/40 p-6 shadow-2xl space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-800 pb-3">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-purple-600 to-indigo-600 text-white flex items-center justify-center font-black shadow-lg shadow-purple-600/30">
                    <i data-lucide="gem" class="w-6 h-6"></i>
                </div>
                <div>
                    <h3 class="text-base font-black text-white flex items-center gap-2">
                        <span>Billetera de Créditos de Activación de Paneles</span>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-purple-500/20 text-purple-300 border border-purple-500/30 uppercase tracking-wider font-mono">
                            {{ Auth::user()->isAdmin() ? 'Super Panel Central' : 'Proveedor Mayorista' }}
                        </span>
                    </h3>
                    <p class="text-xs text-slate-400">
                        Los paneles solicitados se activan exclusivamente consumiendo 1 crédito según el plan adquirido (Básico o PRO).
                    </p>
                </div>
            </div>

            @if(Auth::user()->isSuperReseller())
            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $whatsappAdminPhone) }}?text=Hola,%20quisiera%20comprar%20un%20pack%20de%20cr%C3%A9ditos%20para%20activaci%C3%B3n%20de%20paneles." target="_blank" class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs flex items-center gap-1.5 shadow-lg shadow-purple-600/30 w-max cursor-pointer">
                <i data-lucide="coins" class="w-3.5 h-3.5"></i>
                <span>Comprar Créditos al Admin</span>
            </a>
            @endif
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <!-- Créditos Básicos -->
            <div class="p-4 rounded-2xl bg-[#0b0f19] border border-amber-500/30 flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-amber-400 block font-mono">📦 Créditos BÁSICO (10 Serv)</span>
                    <div class="flex items-baseline gap-2 mt-1">
                        <span class="text-3xl font-black text-white font-mono">
                            @if(Auth::user()->isAdmin())
                                <span class="text-amber-400">∞</span>
                            @else
                                {{ Auth::user()->credits_basic ?? 0 }}
                            @endif
                        </span>
                        <span class="text-xs text-amber-300 font-mono font-bold">{{ Auth::user()->isAdmin() ? 'Ilimitados' : 'disponibles' }}</span>
                    </div>
                    <span class="text-[11px] text-slate-400 block mt-1">Tarifa: $2.500 c/u · Marca SPRO PLAY</span>
                </div>
                <div class="w-12 h-12 rounded-xl bg-amber-500/10 text-amber-400 border border-amber-500/30 flex items-center justify-center font-bold">
                    <i data-lucide="package" class="w-6 h-6"></i>
                </div>
            </div>

            <!-- Créditos PRO -->
            <div class="p-4 rounded-2xl bg-[#0b0f19] border border-purple-500/30 flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-purple-400 block font-mono">💎 Créditos PRO (Ilimitado)</span>
                    <div class="flex items-baseline gap-2 mt-1">
                        <span class="text-3xl font-black text-white font-mono">
                            @if(Auth::user()->isAdmin())
                                <span class="text-purple-400">∞</span>
                            @else
                                {{ Auth::user()->credits_pro ?? 0 }}
                            @endif
                        </span>
                        <span class="text-xs text-purple-300 font-mono font-bold">{{ Auth::user()->isAdmin() ? 'Ilimitados' : 'disponibles' }}</span>
                    </div>
                    <span class="text-[11px] text-slate-400 block mt-1">Tarifa: $4.000 c/u · Marca Blanca Total</span>
                </div>
                <div class="w-12 h-12 rounded-xl bg-purple-500/10 text-purple-400 border border-purple-500/30 flex items-center justify-center font-bold">
                    <i data-lucide="crown" class="w-6 h-6"></i>
                </div>
            </div>

            <!-- Total Créditos -->
            <div class="p-4 rounded-2xl bg-[#0b0f19] border border-emerald-500/30 flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-400 block font-mono">🌟 Total Créditos Activación</span>
                    <div class="flex items-baseline gap-2 mt-1">
                        <span class="text-3xl font-black text-emerald-400 font-mono">
                            @if(Auth::user()->isAdmin())
                                <span>∞</span>
                            @else
                                {{ (Auth::user()->credits_basic ?? 0) + (Auth::user()->credits_pro ?? 0) }}
                            @endif
                        </span>
                        <span class="text-xs text-slate-400 font-mono">{{ Auth::user()->isAdmin() ? 'Créditos Infinitos' : 'créditos totales' }}</span>
                    </div>
                    <span class="text-[11px] text-slate-400 block mt-1">Combo Dúo: 5 Básicos + 5 PRO por $40.000</span>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 flex items-center justify-center font-bold">
                    <i data-lucide="zap" class="w-6 h-6"></i>
                </div>
            </div>
        </div>

        @if(Auth::user()->isSuperReseller())
        <!-- Canal Mayorista: WhatsApp de Proveedor exclusivo para Revendedores -->
        <div class="p-3.5 rounded-2xl bg-slate-950/80 border border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl bg-teal-500/10 border border-teal-500/30 text-teal-400 flex items-center justify-center shrink-0">
                    <i data-lucide="shield-check" class="w-4 h-4"></i>
                </div>
                <div>
                    <span class="text-xs font-bold text-slate-200 block">Canal Mayorista: Proveedor ➔ Revendedor</span>
                    <span class="text-[11px] text-slate-400">
                        Tu WhatsApp <strong class="text-teal-300 font-mono">{{ Auth::user()->support_whatsapp ?: '(Sin número cargado)' }}</strong> está asignado para la atención de tus revendedores afiliados (recargas y paneles). Tus revendedores atienden a sus propios clientes.
                    </span>
                </div>
            </div>
            <a href="{{ route('whitelabel.index') }}" class="px-3 py-1.5 rounded-xl bg-teal-500/10 hover:bg-teal-500/20 text-teal-300 border border-teal-500/30 text-xs font-bold cursor-pointer transition-all shrink-0">
                Ajustar Mi WhatsApp
            </a>
        </div>
        @endif
    </div>
    @endif

    <!-- ======================================================== -->
    <!-- SECCIÓN: SOLICITUDES DE PANEL PENDIENTES DE ACTIVACIÓN -->
    <!-- ======================================================== -->
    @if(isset($pendingRequests) && $pendingRequests->isNotEmpty())
    <div class="p-6 rounded-3xl bg-gradient-to-r from-amber-950/40 via-slate-900 to-[#10182b] border-2 border-amber-500/50 shadow-2xl space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-amber-500/20 pb-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-500/20 text-amber-400 border border-amber-500/40 flex items-center justify-center font-bold">
                    <i data-lucide="user-plus" class="w-5 h-5 animate-bounce"></i>
                </div>
                <div>
                    <h3 class="text-base font-black text-white flex items-center gap-2">
                        <span>Solicitudes de Panel Pendientes de Activación con Crédito</span>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-mono font-black bg-amber-500 text-slate-950 shadow">
                            {{ $pendingRequests->count() }} PENDIENTE(S)
                        </span>
                    </h3>
                    <p class="text-xs text-slate-300">
                        Los revendedores registrados sólo se activan con créditos de plataforma según el plan solicitado.
                    </p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($pendingRequests as $req)
            <div class="p-4 rounded-2xl bg-[#0b0f19] border border-amber-500/30 flex flex-col justify-between space-y-3 shadow-lg">
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        @if($req->isPanelPro())
                        <span class="px-2.5 py-0.5 rounded-full text-[9px] font-black bg-gradient-to-r from-purple-500 to-indigo-500 text-white shadow-sm">
                            💎 PLAN PRO (ILIMITADO)
                        </span>
                        @else
                        <span class="px-2.5 py-0.5 rounded-full text-[9px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                            📦 PLAN BÁSICO (10 SERV)
                        </span>
                        @endif
                        <span class="text-[10px] text-slate-400 font-mono">{{ $req->created_at ? $req->created_at->diffForHumans() : 'Reciente' }}</span>
                    </div>

                    <div>
                        <strong class="text-white text-sm block">{{ $req->name }}</strong>
                        <span class="text-xs text-slate-400 block font-mono">{{ $req->email }}</span>
                        @if($req->support_whatsapp || $req->phone)
                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $req->support_whatsapp ?: $req->phone) }}" target="_blank" class="text-[11px] text-emerald-400 hover:underline flex items-center gap-1 mt-1 font-mono">
                            <i data-lucide="message-circle" class="w-3.5 h-3.5"></i>
                            {{ $req->support_whatsapp ?: $req->phone }}
                        </a>
                        @endif
                        @if($req->brand_name && $req->isPanelPro())
                        <span class="text-[10px] text-purple-300 block mt-0.5 font-mono">Marca: {{ $req->brand_name }}</span>
                        @endif
                        @if($req->parent && Auth::user()->isAdmin())
                        <span class="text-[10px] text-slate-400 block mt-0.5">Patrocinador: <strong class="text-white">{{ $req->parent->name }}</strong></span>
                        @endif
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-800 flex items-center justify-between gap-2">
                    <!-- Formulario de Activación con 1 Crédito -->
                    <form method="POST" action="{{ route('resellers.activate_credit', $req) }}" class="flex-1">
                        @csrf
                        <input type="hidden" name="panel_tier" value="{{ $req->panel_tier ?? 'basic' }}">
                        <button type="submit" onclick="return confirm('¿Confirmas activar el panel {{ strtoupper($req->panel_tier ?? 'basic') }} de {{ $req->name }} consumiendo 1 crédito?')" class="w-full py-2 px-3 rounded-xl {{ $req->isPanelPro() ? 'bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white' : 'bg-amber-500 hover:bg-amber-400 text-slate-950 font-black' }} font-bold text-xs flex items-center justify-center gap-1.5 shadow-md cursor-pointer transition-all">
                            <i data-lucide="zap" class="w-3.5 h-3.5"></i>
                            <span>Activar con 1 Crédito {{ strtoupper($req->panel_tier ?? 'basic') }}</span>
                        </button>
                    </form>

                    <!-- Descartar solicitud -->
                    <form method="POST" action="{{ route('resellers.reject_request', $req) }}">
                        @csrf
                        <button type="submit" onclick="return confirm('¿Descartar esta solicitud de panel?')" class="p-2 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/30 text-xs cursor-pointer" title="Descartar solicitud">
                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                        </button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    @if(isset($pendingTransfers) && $pendingTransfers->isNotEmpty())
    <!-- Solicitudes de Recarga / Transferencias Pendientes de Aprobación -->
    <div class="p-5 rounded-2xl bg-amber-500/10 border border-amber-500/30 shadow-xl space-y-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2.5 text-amber-400">
                <i data-lucide="bell-ring" class="w-5 h-5 animate-pulse"></i>
                <h3 class="text-sm font-black text-white uppercase tracking-wider">
                    🔔 Solicitudes de Saldo / Transferencias Pendientes de Aprobación ({{ $pendingTransfers->count() }})
                </h3>
            </div>
            <span class="text-xs text-amber-300 font-mono">Revisión para acreditación inmediata de saldo</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-gray-200">
                <thead>
                    <tr class="border-b border-amber-500/20 text-[10px] uppercase font-bold text-amber-400/80 bg-black/30">
                        <th class="py-2.5 px-3">Revendedor</th>
                        <th class="py-2.5 px-3">Monto Notificado</th>
                        <th class="py-2.5 px-3">Método / Referencia</th>
                        <th class="py-2.5 px-3">Comprobante</th>
                        <th class="py-2.5 px-3">Notas</th>
                        <th class="py-2.5 px-3 text-right">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-amber-500/10 font-mono">
                    @foreach($pendingTransfers as $tr)
                    <script id="transfer-data-{{ $tr->id }}" type="application/json">@json($tr)</script>
                    <tr class="hover:bg-amber-500/5">
                        <td class="py-2.5 px-3">
                            <span class="font-bold text-white block">{{ $tr->reseller ? $tr->reseller->name : 'Revendedor' }}</span>
                            <span class="text-[10px] text-gray-400 font-sans">{{ $tr->reseller ? $tr->reseller->email : '' }}</span>
                        </td>
                        <td class="py-2.5 px-3">
                            <span class="text-sm font-black text-emerald-400">${{ number_format($tr->amount, 2) }}</span>
                        </td>
                        <td class="py-2.5 px-3 text-xs">
                            <span class="px-2 py-0.5 rounded bg-slate-800 text-sky-300 border border-slate-700 font-sans font-bold uppercase">{{ $tr->payment_method }}</span>
                            @if($tr->reference_code)
                            <span class="block text-[10px] text-gray-400 mt-0.5">Ref: {{ $tr->reference_code }}</span>
                            @endif
                        </td>
                        <td class="py-2.5 px-3">
                            @if($tr->receipt_path)
                            <button type="button" @click="openTransferAuditFromScript({{ $tr->id }})" class="px-2.5 py-1 rounded-xl bg-cyan-500/10 hover:bg-cyan-500/20 border border-cyan-500/30 text-cyan-400 text-xs font-bold flex items-center gap-1.5 transition-all cursor-pointer font-sans">
                                <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                                <span>Ver Comprobante (Mini Nube)</span>
                            </button>
                            @else
                            <span class="text-gray-500 text-[10px] font-sans italic">Sin archivo adjunto</span>
                            @endif
                        </td>
                        <td class="py-2.5 px-3 text-[11px] font-sans text-gray-300">
                            {{ $tr->reseller_notes ?: '—' }}
                        </td>
                        <td class="py-2.5 px-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button type="button" @click="openTransferAuditFromScript({{ $tr->id }})" class="px-3 py-1.5 rounded-xl bg-cyan-600/20 hover:bg-cyan-600/40 text-cyan-300 border border-cyan-500/40 font-bold text-xs flex items-center gap-1 cursor-pointer font-sans transition-all">
                                    <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                                    Auditar
                                </button>
                                <form method="POST" action="{{ route('resellers.balance_transfers.approve', $tr) }}" class="inline">
                                    @csrf
                                    <button type="submit" onclick="return confirm('¿Aprobar y acreditar ${{ number_format($tr->amount, 2) }} a la billetera de {{ $tr->reseller ? $tr->reseller->name : 'este revendedor' }}? El comprobante se purgará de la mini nube de forma permanente.')" class="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs flex items-center gap-1 shadow-md cursor-pointer font-sans">
                                        <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                        Aprobar
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('resellers.balance_transfers.reject', $tr) }}" class="inline">
                                    @csrf
                                    <button type="submit" onclick="return confirm('¿Rechazar esta solicitud de saldo? El comprobante se purgará de la mini nube.')" class="px-2.5 py-1.5 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/30 font-bold text-xs cursor-pointer font-sans">
                                        ✕
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Filtros de Búsqueda y Gestión de Paneles -->
    <div class="p-4 rounded-2xl bg-[#111827] border border-gray-800">
        <form method="GET" action="{{ route('resellers.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3 items-end">
            <div>
                <label class="text-[10px] font-bold uppercase text-gray-400 block mb-1">Buscar Panel</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Nombre, email, marca..." class="w-full px-3 py-2 rounded-xl bg-gray-900 border border-gray-700 text-xs text-white placeholder-gray-500 outline-none focus:border-sky-500">
            </div>

            <div>
                <label class="text-[10px] font-bold uppercase text-gray-400 block mb-1">Nivel / Tier</label>
                <select name="tier" class="w-full px-3 py-2 rounded-xl bg-gray-900 border border-gray-700 text-xs text-white outline-none focus:border-sky-500">
                    <option value="">Todos los Niveles</option>
                    <option value="basic" {{ request('tier') === 'basic' ? 'selected' : '' }}>📦 BÁSICO (10 Serv)</option>
                    <option value="pro" {{ request('tier') === 'pro' ? 'selected' : '' }}>💎 PRO (Ilimitado)</option>
                </select>
            </div>

            <div>
                <label class="text-[10px] font-bold uppercase text-gray-400 block mb-1">Estado</label>
                <select name="status" class="w-full px-3 py-2 rounded-xl bg-gray-900 border border-gray-700 text-xs text-white outline-none focus:border-sky-500">
                    <option value="">Todos los Estados</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>🟢 Activos</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>🟡 Pendientes de Activación ({{ $stats['pending_panels'] ?? 0 }})</option>
                    <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>🔴 Suspendidos</option>
                    <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>⏳ Vencidos</option>
                </select>
            </div>

            @if(Auth::user()->isAdmin() && isset($superResellersList))
            <div>
                <label class="text-[10px] font-bold uppercase text-gray-400 block mb-1">Patrocinador / Proveedor</label>
                <select name="parent_id" class="w-full px-3 py-2 rounded-xl bg-gray-900 border border-gray-700 text-xs text-white outline-none focus:border-sky-500">
                    <option value="">Todos los Proveedores</option>
                    @foreach($superResellersList as $sp)
                    <option value="{{ $sp->id }}" {{ request('parent_id') == $sp->id ? 'selected' : '' }}>{{ $sp->name }} ({{ $sp->brand_name }})</option>
                    @endforeach
                </select>
            </div>
            @else
            <div></div>
            @endif

            <div class="flex items-center gap-2">
                <button type="submit" class="flex-1 py-2 rounded-xl bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs flex items-center justify-center gap-1.5 shadow-md cursor-pointer">
                    <i data-lucide="search" class="w-3.5 h-3.5"></i>
                    Filtrar
                </button>
                @if(request()->hasAny(['search', 'tier', 'status', 'parent_id']))
                <a href="{{ route('resellers.index') }}" class="px-3 py-2 rounded-xl bg-gray-800 hover:bg-gray-700 text-gray-300 font-bold text-xs cursor-pointer">
                    Limpiar
                </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h3 class="text-sm font-bold text-white uppercase tracking-wider">
                {{ Auth::user()->isAdmin() ? 'Control Maestro de Paneles Generados' : 'Gestión de Mis Revendedores Afiliados' }}
            </h3>
            <p class="text-xs text-gray-400">
                {{ Auth::user()->isAdmin() ? 'Como Super Panel puedes gestionar accesos, activar copias clonadas y ajustar comisiones.' : 'Administra tus revendedores, ajusta precios de costo de proveedor y activa sus paneles.' }}
            </p>
        </div>

        <div class="flex items-center gap-2">
            <button @click="calculatorModal = true" class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-sky-400 font-bold text-xs flex items-center gap-2 transition-all border border-slate-700 cursor-pointer">
                <i data-lucide="calculator" class="w-4 h-4"></i>
                <span>Cotizador de Ganancias</span>
            </button>

            @if(Auth::user()->isAdmin())
            <button @click="newResellerModal = true" class="px-4 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold text-xs flex items-center gap-2 transition-all shadow-lg shadow-red-600/30 cursor-pointer">
                <i data-lucide="user-plus" class="w-4 h-4"></i>
                <span>+ Generar Nuevo Panel</span>
            </button>
            @endif
        </div>
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
                    <script id="reseller-data-{{ $res->id }}" type="application/json">@json($res)</script>
                    @php
                        $isCloned = $res->panel_type === 'cloned';
                        $daysLeft = $res->days_remaining_panel;
                        $isExpired = $res->isPanelExpired();
                        $resPwaShortUrl = url('/app/' . $res->referral_code);
                        $resPwaBrand = $res->isPanelBasic() ? 'SPRO PLAY' : ($res->brand_name ?: $res->name);
                        $resPwaText = "📲 ¡Instala la App oficial de *{$resPwaBrand}*!\n👉 " . $resPwaShortUrl;
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
                            <div class="flex items-center gap-2 mt-0.5">
                                <span class="text-[10px] text-sky-400 font-mono">{{ $res->brand_name }} (Ref: {{ $res->referral_code }})</span>
                                <a href="{{ $resPwaShortUrl }}" target="_blank" class="text-[9px] px-1.5 py-0.2 rounded bg-cyan-500/10 hover:bg-cyan-500/20 text-cyan-300 border border-cyan-500/30 font-mono flex items-center gap-0.5" title="Abrir instalador PWA">
                                    <i data-lucide="smartphone" class="w-2.5 h-2.5"></i>
                                    <span>PWA</span>
                                </a>
                                <a href="https://wa.me/?text={{ urlencode($resPwaText) }}" target="_blank" class="text-[9px] text-emerald-400 hover:text-emerald-300 font-mono flex items-center gap-0.5" title="Compartir App por WhatsApp">
                                    <i data-lucide="share-2" class="w-2.5 h-2.5"></i>
                                </a>
                            </div>
                            @if($res->support_whatsapp || $res->phone)
                            <div class="mt-1 flex items-center gap-1.5">
                                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $res->support_whatsapp ?: $res->phone) }}" target="_blank" class="text-[11px] text-emerald-400 hover:underline flex items-center gap-1 font-mono">
                                    <i data-lucide="message-circle" class="w-3 h-3"></i>
                                    {{ $res->support_whatsapp ?: $res->phone }}
                                </a>
                                @php
                                    $resAgreement = "📋 *CONVENIO ADMINISTRATIVO - CSTREAME*\n\n"
                                        . "Hola *" . $res->name . "* (" . ($res->brand_name ?: 'Panel') . "):\n"
                                        . "Puntos puntuales del acuerdo administrativo vigente:\n\n"
                                        . "1️⃣ *PANELES & TIENDAS:*\n"
                                        . "• Activación y gestión de paneles y tiendas mediante créditos.\n\n"
                                        . "2️⃣ *CERO COMISIÓN EN SERVICIOS:*\n"
                                        . "• La administración NO cobra comisión ni porcentaje sobre tus cuentas o servicios (0%).\n\n"
                                        . "3️⃣ *ENTREGAS & SALDO:*\n"
                                        . "• Emisión contra saldo disponible o transferencia acreditada (sin cuentas fiadas).\n\n"
                                        . "4️⃣ *INDEPENDENCIA COMERCIAL:*\n"
                                        . "• Cada parte opera con total libertad en sus precios, servicios y catálogo.\n\n"
                                        . "Confirmar recepción para mantener la operatividad activa.\n\n"
                                        . "— Administración Central CStreame";
                                @endphp
                                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $res->support_whatsapp ?: $res->phone) }}?text={{ urlencode($resAgreement) }}" target="_blank" class="px-1.5 py-0.5 rounded bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 text-[9px] font-bold flex items-center gap-1" title="Enviar Convenio Administrativo por WhatsApp">
                                    <i data-lucide="file-check" class="w-3 h-3"></i>
                                    <span>Convenio</span>
                                </a>
                            </div>
                            @endif
                        </td>
                        <td class="py-3 px-4">
                            <div class="space-y-1">
                                <div>
                                    @if($res->isPanelPro())
                                    <span class="px-2 py-0.5 rounded-full text-[9px] font-black bg-gradient-to-r from-purple-500 to-indigo-500 text-white shadow-sm inline-flex items-center gap-1">
                                        💎 PRO (ILIMITADO)
                                    </span>
                                    @else
                                    <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-amber-500/10 text-amber-300 border border-amber-500/30 inline-flex items-center gap-1">
                                        📦 BÁSICO (10 SERV)
                                    </span>
                                    @endif
                                </div>

                                @if($res->panel_type === 'cloned')
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-500/20 text-purple-400 border border-purple-500/30 block w-max">
                                    📋 Tienda Clonada
                                </span>
                                @if($res->parent)
                                <span class="text-[10px] text-gray-400 block mt-0.5">Patrocinador: <strong class="text-white">{{ $res->parent->name }}</strong></span>
                                @endif
                                @elseif($res->panel_type === 'individual')
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 block w-max">
                                    👤 Panel Individual (Propio)
                                </span>
                                @if($res->parent)
                                <span class="text-[10px] text-gray-400 block mt-0.5">Afiliado a: <strong class="text-white">{{ $res->parent->name }}</strong></span>
                                @endif
                                @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-cyan-500/20 text-cyan-400 border border-cyan-500/30 block w-max">
                                    🛡️ Panel Master ({{ ucfirst($res->reseller_plan) }})
                                </span>
                                @endif

                                @if($res->role === 'super_reseller')
                                <div class="text-[10px] text-gray-400 font-mono pt-1">
                                    Créditos: <span class="text-amber-400 font-bold">{{ $res->credits_basic ?? 0 }} B</span> · <span class="text-purple-400 font-bold">{{ $res->credits_pro ?? 0 }} PRO</span>
                                </div>
                                @endif

                                <div class="pt-1.5 border-t border-gray-800/80">
                                    @if($res->canResellPanels())
                                        <div class="flex items-center gap-1.5">
                                             <span class="text-[10px] text-gray-400 font-mono">Reventa:</span>
                                            <form method="POST" action="{{ route('resellers.toggle_panel_offer', $res) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="px-2 py-0.5 rounded-full text-[9px] font-bold border transition-all cursor-pointer {{ $res->isPanelSalesOffered() ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30 hover:bg-emerald-500/30' : 'bg-gray-800 text-gray-400 border-gray-700 hover:bg-gray-700' }}" title="Habilitar o pausar que este revendedor ofrezca paneles en su catálogo">
                                                    {{ $res->isPanelSalesOffered() ? '✓ Paneles Habilitados' : '✕ Paneles Pausados' }}
                                                </button>
                                            </form>
                                        </div>
                                        <span class="text-[9px] text-slate-400 font-mono block mt-0.5">PVP Sugerido: ${{ number_format($res->basic_panel_price, 0, ',', '.') }} / ${{ number_format($res->pro_panel_price, 0, ',', '.') }}</span>
                                    @else
                                        <span class="text-[9px] text-gray-500 font-mono block">🔒 Básico (Venta directa cuentas)</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="py-3 px-4">
                            <span class="text-sm font-black text-emerald-400 block">${{ number_format($res->balance, 2) }}</span>
                            <button type="button" @click="openRechargeFromScript({{ $res->id }})" class="text-[10px] text-cyan-400 hover:underline font-semibold cursor-pointer">
                                + Recargar Saldo
                            </button>
                        </td>
                        <td class="py-3 px-4 text-xs font-mono">
                            <span class="text-emerald-400 font-bold block">General: {{ $res->reseller_commission_pct ?? 40 }}%</span>
                            @if($res->parent && $res->parent->isSuperReseller())
                            <span class="text-purple-400 block text-[11px]">Proveedor: {{ $res->parent_commission_pct ?? 25 }}%</span>
                            @else
                            <span class="text-slate-400 block text-[11px]">Comisión Admin en Servicios: <strong class="text-emerald-400 font-mono">0%</strong></span>
                            @endif
                            @if($res->customServiceCommissions && $res->customServiceCommissions->isNotEmpty())
                            <div class="mt-1 flex flex-wrap gap-1">
                                @foreach($res->customServiceCommissions as $csc)
                                <span class="text-[9px] px-1.5 py-0.5 rounded bg-slate-800 text-sky-300 border border-slate-700 font-sans" title="{{ $csc->service ? $csc->service->name : '' }}: {{ $csc->commission_pct }}%">
                                    {{ $csc->service ? substr($csc->service->name, 0, 7) : 'Srv' }}: {{ (int)$csc->commission_pct }}%
                                </span>
                                @endforeach
                            </div>
                            @endif
                            <button type="button" @click="openServiceCommissionsFromScript({{ $res->id }})" class="mt-1.5 px-2 py-1 rounded-lg bg-sky-500/10 hover:bg-sky-500/20 text-sky-400 border border-sky-500/30 text-[10px] font-sans font-bold flex items-center gap-1 cursor-pointer">
                                <i data-lucide="sliders" class="w-3 h-3"></i>
                                <span>Comisiones por Servicio</span>
                            </button>

                            <div class="mt-1 flex flex-col gap-1">
                                <button type="button" @click="openPlanPricesFromScript({{ $res->id }})" class="px-2 py-1 rounded-lg bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 text-[10px] font-sans font-bold flex items-center gap-1 cursor-pointer" title="Ajustar Costos Mayoristas de Proveedor">
                                    <i data-lucide="tag" class="w-3 h-3"></i>
                                    <span>⚙️ Precios Proveedor</span>
                                </button>
                                <button type="button" @click="openWhatsAppPrefFromScript({{ $res->id }})" class="px-2 py-1 rounded-lg bg-teal-500/10 hover:bg-teal-500/20 text-teal-300 border border-teal-500/30 text-[10px] font-sans font-bold flex items-center gap-1 cursor-pointer" title="Configurar WhatsApp en Bóveda Clientes">
                                    <i data-lucide="message-circle" class="w-3 h-3"></i>
                                    <span>📱 WhatsApp ({{ $res->whatsapp_support_preference ?? 'auto' }})</span>
                                </button>
                            </div>
                        </td>
                        <td class="py-3 px-4 text-xs">
                            @if($res->status === 'pending')
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-amber-500 text-slate-950 block w-max shadow-sm animate-pulse">
                                    🟡 SOLICITUD PENDIENTE
                                </span>
                                <span class="text-[10px] text-amber-400 font-mono block mt-1">Plan: {{ strtoupper($res->panel_tier ?? 'basic') }}</span>
                                <form method="POST" action="{{ route('resellers.activate_credit', $res) }}" class="mt-2">
                                    @csrf
                                    <input type="hidden" name="panel_tier" value="{{ $res->panel_tier ?? 'basic' }}">
                                    <button type="submit" onclick="return confirm('¿Activar panel {{ strtoupper($res->panel_tier ?? 'basic') }} de {{ $res->name }} consumiendo 1 crédito?')" class="px-2.5 py-1 rounded-lg {{ $res->isPanelPro() ? 'bg-gradient-to-r from-purple-600 to-indigo-600 text-white' : 'bg-amber-500 text-slate-950' }} font-bold text-[10px] flex items-center gap-1 shadow cursor-pointer">
                                        <i data-lucide="zap" class="w-3 h-3"></i>
                                        <span>⚡ Activar con 1 Crédito {{ strtoupper($res->panel_tier ?? 'basic') }}</span>
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('resellers.reject_request', $res) }}" class="mt-1">
                                    @csrf
                                    <button type="submit" onclick="return confirm('¿Descartar la solicitud de panel de {{ $res->name }}?')" class="text-[9px] text-rose-400 hover:text-rose-300 underline cursor-pointer">
                                        Descartar solicitud
                                    </button>
                                </form>
                            @elseif($isCloned)
                                @if($isExpired)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/20 text-rose-400 border border-rose-500/30 block w-max">
                                    🔴 Suspendido (Tienda $15k Vencida)
                                </span>
                                @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 block w-max">
                                    🟢 Activo ({{ $daysLeft ?? 30 }} días restantes)
                                </span>
                                @endif

                                <div class="mt-1.5 text-[10px] font-mono space-y-0.5">
                                    <span class="text-emerald-400 block font-bold">✓ Tienda $15k ($8k Admin | $7k Prov)</span>
                                    <div class="flex items-center gap-1">
                                         @if($res->cloned_store_paid_by_reseller)
                                        <span class="text-emerald-400 font-bold">✓ Revendedor: Cobrado</span>
                                        @else
                                        <span class="text-amber-400 font-bold">⏳ Revendedor: Pendiente</span>
                                        @endif
                                        <form method="POST" action="{{ route('resellers.toggle_store_paid', $res) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="text-[9px] text-sky-400 hover:underline cursor-pointer">
                                                [{{ $res->cloned_store_paid_by_reseller ? 'Marcar Pendiente' : 'Marcar Cobrado' }}]
                                            </button>
                                        </form>
                                    </div>
                                    @if(!$res->cloned_store_paid_by_reseller)
                                    <span class="text-[9px] px-1.5 py-0.5 rounded bg-rose-500/20 text-rose-300 border border-rose-500/30 font-bold block mt-1 w-max">
                                        ⛔ Entrega de cuentas BLOQUEADA
                                    </span>
                                    @else
                                    <span class="text-[9px] px-1.5 py-0.5 rounded bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 font-bold block mt-1 w-max">
                                        ✓ Entrega de cuentas HABILITADA
                                    </span>
                                    @endif
                                </div>
                            @else
                                <div class="text-[11px] text-cyan-400 font-mono font-bold block mb-1">
                                    Vigencia: {{ $res->panel_expires_at ? \Carbon\Carbon::parse($res->panel_expires_at)->format('d/m/Y') : 'Permanente' }}
                                </div>
                            @endif

                            <!-- Botones de Activación Maestra & Créditos -->
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                @if(Auth::user()->isAdmin())
                                <button type="button" @click="openMasterActivationFromScript({{ $res->id }})" class="px-2 py-1 rounded-lg bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 border border-amber-500/30 text-[10px] font-bold flex items-center gap-1 cursor-pointer" title="Activación Maestra Super Admin">
                                    <i data-lucide="zap" class="w-3 h-3"></i>
                                    <span>⚡ Activar Maestro</span>
                                </button>
                                <button type="button" @click="openSellCreditsFromScript({{ $res->id }})" class="px-2 py-1 rounded-lg bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 text-[10px] font-bold flex items-center gap-1 cursor-pointer" title="Vender Créditos de Paneles">
                                    <i data-lucide="coins" class="w-3 h-3"></i>
                                    <span>💰 Vender Créditos</span>
                                </button>
                                @endif

                                <button type="button" @click="openCreditActivationFromScript({{ $res->id }})" class="px-2 py-1 rounded-lg bg-purple-500/10 hover:bg-purple-500/20 text-purple-300 border border-purple-500/30 text-[10px] font-bold flex items-center gap-1 cursor-pointer" title="Activar con 1 crédito">
                                    <i data-lucide="gem" class="w-3 h-3"></i>
                                    <span>💎 1 Crédito</span>
                                </button>

                                <button type="button" @click="openClonePanelFromScript({{ $res->id }})" class="px-2 py-1 rounded-lg bg-sky-500/10 hover:bg-sky-500/20 text-sky-300 border border-sky-500/30 text-[10px] font-bold flex items-center gap-1 cursor-pointer" title="Clonar Catálogo $15.000">
                                    <i data-lucide="copy" class="w-3 h-3"></i>
                                    <span>$15k Clon</span>
                                </button>
                            </div>
                        </td>
                        <td class="py-3 px-4">
                            <code class="px-2 py-0.5 rounded bg-black/60 border border-gray-800 text-amber-400 font-mono text-[10px] block w-max">
                                {{ $res->anti_phishing_code ?: 'SEC-' . $res->id }}
                            </code>
                        </td>
                        <td class="py-3 px-4 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                @if(Auth::user()->isAdmin())
                                <!-- Botón VER CLAVE (Exclusivo Super Admin) -->
                                <button type="button" @click="revealPasswordFromScript({{ $res->id }})" title="Ver Contraseña Actual del Panel" class="px-2.5 py-1.5 rounded-xl bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 border border-amber-500/30 font-bold text-xs flex items-center gap-1 transition-all cursor-pointer">
                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                    <span>Ver Clave</span>
                                </button>

                                <!-- Botón CAMBIAR CLAVE (Exclusivo Super Admin) -->
                                <button type="button" @click="openChangePassFromScript({{ $res->id }})" title="Cambiar Contraseña del Panel" class="px-2.5 py-1.5 rounded-xl bg-sky-500/10 hover:bg-sky-500/20 text-sky-400 border border-sky-500/30 font-bold text-xs flex items-center gap-1 transition-all cursor-pointer">
                                    <i data-lucide="key" class="w-3.5 h-3.5"></i>
                                    <span>Cambiar</span>
                                </button>

                                <!-- Botón TOGGLE ROL (Revendedor <-> Super Reseller) -->
                                <form method="POST" action="{{ route('resellers.toggle_role', $res) }}" class="inline">
                                    @csrf
                                    <button type="submit" onclick="return confirm('¿Cambiar el rol de este panel entre Revendedor y Proveedor / Super Reseller?')" class="px-2 py-1.5 rounded-xl bg-purple-500/10 hover:bg-purple-500/20 text-purple-300 border border-purple-500/30 font-bold text-xs flex items-center gap-1 cursor-pointer" title="Ascender a Proveedor o Cambiar Rol">
                                        <i data-lucide="shield" class="w-3.5 h-3.5"></i>
                                        <span>Rol</span>
                                    </button>
                                </form>
                                @endif

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

        @if(method_exists($resellers, 'hasPages') && $resellers->hasPages())
        <div class="p-4 border-t border-gray-800 bg-[#0d1322]">
            {{ $resellers->links() }}
        </div>
        @endif
    </div>

    @if(Auth::user()->isAdmin())
    <!-- ========================================== -->
    <!-- MODAL 1: VER CONTRASEÑA ACTUAL DEL PANEL (EXCLUSIVO SUPER ADMIN) -->
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
    @endif

    <!-- ========================================== -->
    <!-- MODAL 3: CLONAR PANEL / TIENDA ($15.000 / 30 DÍAS) -->
    <!-- ========================================== -->
    <div x-show="clonePanelModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm" @click.self="clonePanelModal = false">
        <div class="w-full max-w-md bg-[#111827] border border-gray-800 rounded-3xl shadow-2xl p-6 space-y-4">
            <div class="flex items-center gap-2 text-sky-400">
                <i data-lucide="copy" class="w-5 h-5"></i>
                <h3 class="font-bold text-white text-base">Clonar / Renovar Tienda de Revendedor</h3>
            </div>
            <p class="text-xs text-gray-400 leading-relaxed">
                Tiendas Clonadas: <strong>$15.000 / mes</strong> (30 días).<br>
                Distribución oficial: <span class="text-purple-300 font-bold">$8.000 para el Administrador</span> y <span class="text-emerald-400 font-bold">$7.000 para el Proveedor</span>.
            </p>

            <form :action="'/resellers/' + selectedReseller?.id + '/clone-panel'" method="POST" class="space-y-4">
                @csrf

                @if(Auth::user()->isAdmin())
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Proveedor o Revendedor Patrocinador de Cuentas</label>
                        <select name="parent_id" class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white">
                            <option value="">-- Sin Patrocinador (Super Panel Directo) --</option>
                            @foreach($superResellersList ?? [] as $sres)
                            <option value="{{ $sres->id }}">{{ $sres->name }} ({{ $sres->brand_name }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="p-3 rounded-2xl bg-purple-500/10 border border-purple-500/20 text-purple-300 text-xs space-y-1">
                        <span class="font-bold block">Distribución del abono mensual ($15.000):</span>
                        <p class="text-[11px] text-purple-200">
                            • <strong>$8.000</strong> plataforma para el Administrador General.<br>
                            • <strong>$7.000</strong> asignados al Proveedor patrocinador.
                        </p>
                    </div>

                    <label class="flex items-center gap-2.5 cursor-pointer p-2.5 rounded-xl bg-[#0b0f19] border border-gray-800">
                        <input type="checkbox" name="credit_provider_share" value="1" checked class="rounded bg-gray-900 border-gray-700 text-emerald-600 focus:ring-0 w-4 h-4">
                        <div class="text-xs">
                            <span class="font-bold text-white block">Acreditar $7.000 en billetera del proveedor patrocinador</span>
                            <span class="text-[11px] text-gray-400">Si se recibió el abono de $15.000, le acredita su ganancia correspondiente.</span>
                        </div>
                    </label>
                </div>
                @endif

                @if(Auth::user()->isSuperReseller())
                <div class="p-3.5 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-300 text-xs space-y-2">
                    <div class="font-bold flex items-center justify-between">
                        <div class="flex items-center gap-1.5">
                            <i data-lucide="wallet" class="w-4 h-4 text-amber-400"></i>
                            <span>Cuota de Plataforma al Admin: $8.000</span>
                        </div>
                        <span class="px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 font-bold text-[10px] border border-emerald-500/30">
                            +$7.000 ganancia Proveedor
                        </span>
                    </div>
                    <p class="text-[11px] text-amber-200/90 leading-relaxed">
                        Costo total de la Tienda Clonada: <strong>$15.000 / mes</strong>.<br>
                        De tu billetera se debitarán únicamente <strong>$8.000</strong> para el Administrador General. Tu ganancia neta de <strong>$7.000</strong> queda en tu poder.
                    </p>
                    <div class="text-[11px] text-gray-400 pt-1.5 border-t border-amber-500/20">
                        Tu saldo actual en billetera: <strong>${{ number_format(Auth::user()->balance, 2) }}</strong>
                    </div>
                </div>

                <!-- Cobro del Revendedor al Proveedor o Revendedor Patrocinador -->
                <div class="p-3.5 rounded-2xl bg-[#0b0f19] border border-gray-800 space-y-2.5">
                    <label class="flex items-center gap-2.5 cursor-pointer">
                        <input type="checkbox" name="cloned_store_paid_by_reseller" value="1" checked class="rounded bg-gray-900 border-gray-700 text-emerald-600 focus:ring-0 w-4 h-4">
                        <div class="text-xs">
                            <span class="font-bold text-white block">El revendedor ya me abonó la tienda ($15.000)</span>
                            <span class="text-[11px] text-gray-400">Registrar como cobrado al revendedor ($7.000 ganancia para ti, $8.000 debitados para admin)</span>
                        </div>
                    </label>

                    <label class="flex items-center gap-2.5 cursor-pointer pt-2 border-t border-gray-800">
                        <input type="checkbox" name="debit_from_reseller_balance" value="1" class="rounded bg-gray-900 border-gray-700 text-sky-600 focus:ring-0 w-4 h-4">
                        <div class="text-xs">
                            <span class="font-medium text-slate-300 block">Cobrar $15.000 del saldo de billetera del revendedor</span>
                            <span class="text-[10px] text-gray-400">Si el revendedor tiene saldo, se le descuentan $15.000 (te acredita $7.000 a ti y $8.000 al admin).</span>
                        </div>
                    </label>
                </div>
                @endif

                <div class="p-3 rounded-2xl bg-sky-500/10 border border-sky-500/20 text-sky-300 text-xs">
                    ℹ️ Vigencia: <strong>+30 días</strong> desde la activación o desde el vencimiento actual.
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="clonePanelModal = false" class="px-4 py-2 text-xs text-gray-400 hover:text-white cursor-pointer">Cancelar</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs shadow-lg cursor-pointer">
                        Confirmar & Activar Tienda ($15.000)
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL 7: COMISIONES ESPECÍFICAS POR CUENTA / SERVICIO -->
    <!-- ========================================== -->
    <div x-show="serviceCommissionsModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-md" @click.self="serviceCommissionsModal = false">
        <div class="w-full max-w-lg bg-[#111827] border border-gray-800 rounded-3xl shadow-2xl p-6 sm:p-7 space-y-5 relative overflow-hidden">
            <!-- Glow background -->
            <div class="absolute -top-16 -right-16 w-36 h-36 bg-sky-500/20 rounded-full blur-2xl pointer-events-none"></div>

            <div class="flex items-center justify-between pb-3 border-b border-gray-800">
                <div class="flex items-center gap-2.5 text-sky-400">
                    <i data-lucide="sliders" class="w-6 h-6"></i>
                    <div>
                        <h3 class="font-black text-white text-base">Comisiones por Servicio para Revendedor</h3>
                        <span class="text-xs text-gray-400" x-text="'Configuración para: ' + (serviceReseller?.name || 'Revendedor')"></span>
                    </div>
                </div>
                <button @click="serviceCommissionsModal = false" class="text-gray-400 hover:text-white text-lg font-bold cursor-pointer">✕</button>
            </div>

            <p class="text-xs text-gray-400 leading-relaxed">
                Ajusta aquí la comisión porcentual del proveedor o revendedor por cada plataforma de streaming en particular, en lugar de utilizar una comisión general única.
            </p>

            <form :action="'/resellers/' + serviceReseller?.id + '/update-service-commissions'" method="POST" class="space-y-4">
                @csrf

                <div class="space-y-3 max-h-72 overflow-y-auto pr-1">
                    @foreach($servicesList ?? [] as $srv)
                    <div class="p-3.5 rounded-2xl bg-[#0b0f19] border border-gray-800/80 flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <span class="w-3.5 h-3.5 rounded-full shrink-0 shadow" style="background-color: {{ $srv->color_hex ?? '#E50914' }}"></span>
                            <div>
                                <h4 class="font-bold text-white text-xs">{{ $srv->name }}</h4>
                                <span class="text-[10px] text-gray-400 font-mono">Margen particular por cuenta</span>
                            </div>
                        </div>

                        <div class="w-36 flex items-center gap-1.5">
                            <input type="number" step="0.5" min="0" max="100" name="commissions[{{ $srv->id }}]" placeholder="40.0" class="w-full px-3 py-1.5 rounded-xl bg-gray-900 border border-gray-700 text-xs text-emerald-400 font-black text-right outline-none focus:border-sky-500 font-mono">
                            <span class="text-xs text-gray-400 font-bold">%</span>
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="p-3 rounded-2xl bg-slate-900 border border-slate-800 text-[11px] text-slate-400">
                    💡 Si dejas un servicio vacío, el sistema aplicará automáticamente la comisión general del revendedor (por defecto 40%).
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-800">
                    <button type="button" @click="serviceCommissionsModal = false" class="px-4 py-2 text-xs text-gray-400 hover:text-white cursor-pointer">Cancelar</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-sky-600 to-blue-600 hover:opacity-90 text-white font-bold text-xs shadow-lg shadow-sky-600/30 flex items-center gap-1.5 cursor-pointer">
                        <i data-lucide="check" class="w-4 h-4"></i>
                        <span>Guardar Comisiones Específicas</span>
                    </button>
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

    @if(Auth::user()->isAdmin())
    <!-- ========================================== -->
    <!-- MODAL 6: NUEVO REVENDEDOR / PANEL (SUPER ADMIN) -->
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

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Tipo de Panel</label>
                        <select name="panel_type" class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white">
                            <option value="cloned" selected>👥 Tienda Clonada (Comparte inventario del proveedor)</option>
                            <option value="individual">👤 Panel Individual (Inventario propio independiente)</option>
                            <option value="master">👑 Panel Master</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Capacidad / Tier</label>
                        <select name="panel_tier" class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white">
                            <option value="basic" selected>📦 Básico (10 Serv)</option>
                            <option value="pro">💎 PRO (Ilimitado)</option>
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
                            @foreach($superResellersList ?? [] as $sres)
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
    @endif

    <!-- ========================================== -->
    <!-- MODAL 9: COTIZADOR & CALCULADORA DE GANANCIAS -->
    <!-- ========================================== -->
    <div x-show="calculatorModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-md" @click.self="calculatorModal = false">
        <div class="w-full max-w-lg bg-[#111827] border border-gray-800 rounded-3xl shadow-2xl p-6 sm:p-7 space-y-5 relative overflow-hidden">
            <!-- Glow background -->
            <div class="absolute -top-16 -right-16 w-36 h-36 bg-emerald-500/20 rounded-full blur-2xl pointer-events-none"></div>

            <div class="flex items-center justify-between pb-3 border-b border-gray-800">
                <div class="flex items-center gap-2.5 text-emerald-400">
                    <i data-lucide="calculator" class="w-6 h-6"></i>
                    <div>
                        <h3 class="font-black text-white text-base">Cotizador & Calculadora de Ganancias</h3>
                        <span class="text-xs text-gray-400">Simulador de precios, costos mayoristas y comisiones</span>
                    </div>
                </div>
                <button @click="calculatorModal = false" class="text-gray-400 hover:text-white text-lg font-bold cursor-pointer">✕</button>
            </div>

            <!-- Selector de Servicio -->
            <div class="space-y-3">
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-400">1. Selecciona la Plataforma</label>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <button type="button" @click="calcPrice = 4500" :class="calcPrice === 4500 ? 'bg-red-600 text-white border-red-500 ring-2 ring-red-500/30 font-bold' : 'bg-slate-900 text-slate-300 border-gray-800'" class="p-2.5 rounded-xl border text-left flex items-center justify-between cursor-pointer">
                        <span>NETFLIX 4K</span>
                        <span class="font-mono text-[11px] opacity-80">$4.500</span>
                    </button>
                    <button type="button" @click="calcPrice = 3500" :class="calcPrice === 3500 ? 'bg-blue-600 text-white border-blue-500 ring-2 ring-blue-500/30 font-bold' : 'bg-slate-900 text-slate-300 border-gray-800'" class="p-2.5 rounded-xl border text-left flex items-center justify-between cursor-pointer">
                        <span>HBO MAX</span>
                        <span class="font-mono text-[11px] opacity-80">$3.500</span>
                    </button>
                    <button type="button" @click="calcPrice = 3200" :class="calcPrice === 3200 ? 'bg-indigo-600 text-white border-indigo-500 ring-2 ring-indigo-500/30 font-bold' : 'bg-slate-900 text-slate-300 border-gray-800'" class="p-2.5 rounded-xl border text-left flex items-center justify-between cursor-pointer">
                        <span>DISNEY+ ESTÁNDAR</span>
                        <span class="font-mono text-[11px] opacity-80">$3.200</span>
                    </button>
                    <button type="button" @click="calcPrice = 4200" :class="calcPrice === 4200 ? 'bg-purple-600 text-white border-purple-500 ring-2 ring-purple-500/30 font-bold' : 'bg-slate-900 text-slate-300 border-gray-800'" class="p-2.5 rounded-xl border text-left flex items-center justify-between cursor-pointer">
                        <span>DISNEY+ PREMIUM</span>
                        <span class="font-mono text-[11px] opacity-80">$4.200</span>
                    </button>
                </div>
            </div>

            <!-- Selector de Meses -->
            <div class="space-y-2">
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-400">2. Duración de la Licencia</label>
                <div class="grid grid-cols-5 gap-2">
                    @foreach([1, 2, 3, 6, 12] as $m)
                    <button type="button" @click="calcMonths = {{ $m }}" :class="calcMonths === {{ $m }} ? 'bg-emerald-600 text-white border-emerald-500 font-black ring-2 ring-emerald-500/30 shadow' : 'bg-slate-900 text-slate-300 border-gray-800'" class="p-2 rounded-xl border text-xs text-center cursor-pointer">
                        {{ $m }} {{ $m == 1 ? 'Mes' : 'Meses' }}
                    </button>
                    @endforeach
                </div>
            </div>

            <!-- Porcentajes de Margen -->
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-emerald-400 mb-1">Margen Revendedor (%)</label>
                    <input type="number" step="1" min="10" max="90" x-model.number="calcMargin" class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-gray-700 text-xs text-white font-black font-mono">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-purple-400 mb-1">Comisión Patrocinador (%)</label>
                    <input type="number" step="1" min="0" max="50" x-model.number="calcParentPct" class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-gray-700 text-xs text-white font-black font-mono">
                </div>
            </div>

            <!-- Resultados en Vivo -->
            <div class="p-4 rounded-2xl bg-gradient-to-r from-emerald-950/40 via-slate-900 to-purple-950/40 border border-emerald-500/30 space-y-2.5">
                <div class="flex items-center justify-between text-xs">
                    <span class="text-slate-400">Precio Final al Cliente (PVP):</span>
                    <strong class="text-white font-mono text-sm" x-text="'$' + (calcMonths * calcPrice).toLocaleString('es-AR', {minimumFractionDigits: 2})"></strong>
                </div>

                <div class="flex items-center justify-between text-xs">
                    <span class="text-slate-400">Costo Descontado de Saldo:</span>
                    <strong class="text-sky-300 font-mono text-sm" x-text="'$' + ((calcMonths * calcPrice) * (1 - calcMargin/100)).toLocaleString('es-AR', {minimumFractionDigits: 2})"></strong>
                </div>

                <div class="pt-2 border-t border-gray-800 flex items-center justify-between">
                    <div>
                        <span class="text-xs uppercase font-bold text-emerald-400 block">Tu Ganancia Neta:</span>
                        <span class="text-[11px] text-gray-400" x-text="calcMargin + '% del valor total'"></span>
                    </div>
                    <span class="text-xl font-black text-emerald-400 font-mono" x-text="'$' + ((calcMonths * calcPrice) * (calcMargin/100)).toLocaleString('es-AR', {minimumFractionDigits: 2})"></span>
                </div>

                <div class="pt-2 border-t border-gray-800/80 flex items-center justify-between text-xs">
                    <span class="text-purple-400">Comisión Patrocinador (<span x-text="calcParentPct"></span>%):</span>
                    <strong class="text-purple-300 font-mono" x-text="'$' + (((calcMonths * calcPrice) * (calcMargin/100)) * (calcParentPct/100)).toLocaleString('es-AR', {minimumFractionDigits: 2})"></strong>
                </div>
            </div>

            <div class="flex justify-end pt-1">
                <button type="button" @click="calculatorModal = false" class="px-5 py-2.5 rounded-xl bg-slate-800 text-slate-300 text-xs font-bold hover:bg-slate-700 cursor-pointer">
                    Cerrar Cotizador
                </button>
            </div>
        </div>
    </div>

    @if(Auth::user()->isAdmin())
    <!-- ========================================== -->
    <!-- MODAL 10: VENTA DE CRÉDITOS (SUPER ADMIN) -->
    <!-- ========================================== -->
    <div x-show="sellCreditsModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-md" @click.self="sellCreditsModal = false">
        <div class="w-full max-w-lg bg-[#111827] border border-gray-800 rounded-3xl shadow-2xl p-6 sm:p-7 space-y-5 relative">
            <div class="flex items-center justify-between pb-3 border-b border-gray-800">
                <div class="flex items-center gap-2.5 text-emerald-400">
                    <i data-lucide="coins" class="w-6 h-6"></i>
                    <div>
                        <h3 class="font-black text-white text-base">Venta de Créditos de Activación</h3>
                        <span class="text-xs text-gray-400">Asigna paquetes de créditos para proveedor o revendedor</span>
                    </div>
                </div>
                <button @click="sellCreditsModal = false" class="text-gray-400 hover:text-white text-lg font-bold cursor-pointer">✕</button>
            </div>

            <div class="p-3.5 rounded-2xl bg-[#0b0f19] border border-gray-800 flex items-center justify-between">
                <div>
                    <span class="text-[10px] uppercase font-bold text-gray-400 block">Comprador (Proveedor o Revendedor):</span>
                    <strong class="text-white text-sm" x-text="selectedReseller?.name"></strong>
                    <span class="text-xs text-sky-400 block" x-text="selectedReseller?.email"></span>
                </div>
                <div class="text-right font-mono text-[11px] space-y-0.5">
                    <span class="text-gray-400 block">Créditos actuales:</span>
                    <span class="text-amber-400 font-bold block" x-text="'📦 Básicos: ' + (selectedReseller?.credits_basic || 0)"></span>
                    <span class="text-purple-400 font-bold block" x-text="'💎 PRO: ' + (selectedReseller?.credits_pro || 0)"></span>
                </div>
            </div>

            <form :action="'/resellers/' + selectedReseller?.id + '/sell-credits'" method="POST" class="space-y-4">
                @csrf

                <div class="space-y-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-400">Selecciona el Paquete de Créditos *</label>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 text-xs">
                        <!-- Pack 10 Básicos -->
                        <label class="p-3 rounded-2xl border cursor-pointer transition-all flex flex-col justify-between"
                               :class="selectedCreditPack === 'basic_10' ? 'bg-amber-500/10 border-amber-500 text-white shadow-lg ring-1 ring-amber-500/50' : 'bg-[#0b0f19] border-gray-800 text-gray-300 hover:border-gray-700'">
                            <div class="flex items-center justify-between">
                                <input type="radio" name="pack_type" value="basic_10" x-model="selectedCreditPack" class="text-amber-500 focus:ring-0">
                                <span class="px-2 py-0.5 rounded-full text-[9px] font-black bg-amber-500/20 text-amber-300">10 BÁSICOS</span>
                            </div>
                            <div class="mt-2">
                                <div class="font-bold text-white text-sm">📦 10 Básicos</div>
                                <div class="text-[11px] text-amber-400 font-mono mt-0.5">$2.500 c/u</div>
                                <div class="text-[10px] text-gray-400 mt-0.5">Mínimo 10 créditos</div>
                            </div>
                            <div class="mt-2 pt-2 border-t border-gray-800/80 font-black text-white font-mono text-right">
                                Total: $25.000
                            </div>
                        </label>

                        <!-- Pack 10 PRO -->
                        <label class="p-3 rounded-2xl border cursor-pointer transition-all flex flex-col justify-between"
                               :class="selectedCreditPack === 'pro_10' ? 'bg-purple-500/10 border-purple-500 text-white shadow-lg ring-1 ring-purple-500/50' : 'bg-[#0b0f19] border-gray-800 text-gray-300 hover:border-gray-700'">
                            <div class="flex items-center justify-between">
                                <input type="radio" name="pack_type" value="pro_10" x-model="selectedCreditPack" class="text-purple-500 focus:ring-0">
                                <span class="px-2 py-0.5 rounded-full text-[9px] font-black bg-purple-500/20 text-purple-300">10 PRO</span>
                            </div>
                            <div class="mt-2">
                                <div class="font-bold text-white text-sm">💎 10 PRO</div>
                                <div class="text-[11px] text-purple-400 font-mono mt-0.5">$4.000 c/u</div>
                                <div class="text-[10px] text-gray-400 mt-0.5">Ilimitado · Mínimo 10</div>
                            </div>
                            <div class="mt-2 pt-2 border-t border-gray-800/80 font-black text-white font-mono text-right">
                                Total: $40.000
                            </div>
                        </label>

                        <!-- Combo Dúo (5 de cada uno) -->
                        <label class="p-3 rounded-2xl border cursor-pointer transition-all flex flex-col justify-between"
                               :class="selectedCreditPack === 'combo_5_5' ? 'bg-gradient-to-br from-amber-500/15 via-slate-900 to-purple-500/15 border-emerald-500 text-white shadow-lg ring-1 ring-emerald-500/50' : 'bg-[#0b0f19] border-gray-800 text-gray-300 hover:border-gray-700'">
                            <div class="flex items-center justify-between">
                                <input type="radio" name="pack_type" value="combo_5_5" x-model="selectedCreditPack" class="text-emerald-500 focus:ring-0">
                                <span class="px-2 py-0.5 rounded-full text-[9px] font-black bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">COMBO DÚO</span>
                            </div>
                            <div class="mt-2">
                                <div class="font-bold text-white text-sm">🌟 5 de Cada Uno</div>
                                <div class="text-[10px] text-slate-300 font-mono mt-0.5 space-y-0.5">
                                    <div class="text-amber-400">5 Básicos ($3.000 c/u) = $15k</div>
                                    <div class="text-purple-400">5 PRO ($5.000 c/u) = $25k</div>
                                </div>
                                <div class="text-[10px] text-gray-400 mt-0.5">10 Créditos Mixtos</div>
                            </div>
                            <div class="mt-2 pt-2 border-t border-gray-800/80 font-black text-emerald-400 font-mono text-right">
                                Total: $40.000
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Custom pack option -->
                <div class="p-3 rounded-2xl bg-[#0b0f19] border border-gray-800 space-y-2">
                    <label class="flex items-center gap-2 cursor-pointer text-xs font-bold text-gray-300">
                        <input type="radio" name="pack_type" value="custom" x-model="selectedCreditPack" class="text-sky-500 focus:ring-0">
                        <span>Pack Personalizado / Cantidad a Medida</span>
                    </label>

                    <div x-show="selectedCreditPack === 'custom'" class="grid grid-cols-3 gap-2 pt-2">
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-gray-400 mb-0.5">Tipo</label>
                            <select name="custom_tier" class="w-full px-2.5 py-1.5 rounded-xl bg-slate-900 border border-gray-700 text-xs text-white">
                                <option value="basic">Básico</option>
                                <option value="pro">PRO</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-gray-400 mb-0.5">Cantidad</label>
                            <input type="number" name="custom_qty" min="1" value="10" class="w-full px-2.5 py-1.5 rounded-xl bg-slate-900 border border-gray-700 text-xs text-white font-bold">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-gray-400 mb-0.5">Precio Unit ($)</label>
                            <input type="number" step="0.01" name="custom_price" value="2500" class="w-full px-2.5 py-1.5 rounded-xl bg-slate-900 border border-gray-700 text-xs text-white font-bold">
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Notas / Referencia de Pago</label>
                    <input type="text" name="notes" placeholder="Ej: Pago recibido por transferencia banco / comprobante #8822" class="w-full px-3.5 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white">
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="sellCreditsModal = false" class="px-4 py-2 text-xs text-gray-400 hover:text-white cursor-pointer">Cancelar</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-lg shadow-emerald-600/30 cursor-pointer">
                        Confirmar Venta & Acreditar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL 11: ACTIVACIÓN MAESTRA (SUPER ADMIN) -->
    <!-- ========================================== -->
    <div x-show="masterActivationModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-md" @click.self="masterActivationModal = false">
        <div class="w-full max-w-md bg-[#111827] border border-gray-800 rounded-3xl shadow-2xl p-6 space-y-4 relative">
            <div class="flex items-center justify-between pb-3 border-b border-gray-800">
                <div class="flex items-center gap-2.5 text-amber-400">
                    <i data-lucide="zap" class="w-6 h-6"></i>
                    <div>
                        <h3 class="font-black text-white text-base">Activación Maestra Super Admin</h3>
                        <span class="text-xs text-gray-400">Control total directo sobre cualquier panel</span>
                    </div>
                </div>
                <button @click="masterActivationModal = false" class="text-gray-400 hover:text-white text-lg font-bold cursor-pointer">✕</button>
            </div>

            <div class="p-3 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-300 text-xs space-y-1">
                <span class="font-bold block">⚡ Privilegio de Super Administrador</span>
                <p class="text-[11px] text-amber-200/80">Esta acción activa o extiende el panel inmediatamente sin descontar créditos de ninguna billetera y habilita el catálogo de streaming al 100%.</p>
            </div>

            <form :action="'/resellers/' + selectedReseller?.id + '/activate-master'" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Panel a Activar</label>
                    <div class="p-3 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs font-bold text-white" x-text="selectedReseller?.name + ' (' + (selectedReseller?.email || '') + ')'"></div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Nivel / Tier</label>
                        <select name="panel_tier" x-model="selectedTier" class="w-full px-3 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white">
                            <option value="basic">📦 BÁSICO (10 Serv)</option>
                            <option value="pro">💎 PRO (Ilimitado)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Duración</label>
                        <select name="duration_days" x-model="selectedDays" class="w-full px-3 py-2.5 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs text-white">
                            <option value="30">30 días (1 mes)</option>
                            <option value="60">60 días (2 meses)</option>
                            <option value="90">90 días (3 meses)</option>
                            <option value="180">180 días (6 meses)</option>
                            <option value="360">360 días (1 año)</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="masterActivationModal = false" class="px-4 py-2 text-xs text-gray-400 hover:text-white cursor-pointer">Cancelar</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-500 text-white font-bold text-xs shadow-lg shadow-amber-600/30 cursor-pointer">
                        Ejecutar Activación Maestra
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- ========================================== -->
    <!-- MODAL 12: ACTIVAR CON 1 CRÉDITO -->
    <!-- ========================================== -->
    <div x-show="creditActivationModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-md" @click.self="creditActivationModal = false">
        <div class="w-full max-w-md bg-[#111827] border border-gray-800 rounded-3xl shadow-2xl p-6 space-y-4 relative">
            <div class="flex items-center justify-between pb-3 border-b border-gray-800">
                <div class="flex items-center gap-2.5 text-purple-400">
                    <i data-lucide="gem" class="w-6 h-6"></i>
                    <div>
                        <h3 class="font-black text-white text-base">Activar Panel con 1 Crédito</h3>
                        <span class="text-xs text-gray-400">Canje de 1 crédito para proveedor o revendedor por 30 días de suscripción</span>
                    </div>
                </div>
                <button @click="creditActivationModal = false" class="text-gray-400 hover:text-white text-lg font-bold cursor-pointer">✕</button>
            </div>

            <!-- Saldo actual del usuario conectado -->
            <div class="p-3.5 rounded-2xl bg-[#0b0f19] border border-purple-500/30 space-y-1">
                <span class="text-[10px] uppercase font-bold text-gray-400 block">Tus Créditos Disponibles:</span>
                <div class="flex items-center gap-3">
                    <span class="px-2.5 py-1 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-300 font-mono text-xs font-bold">
                        📦 Básicos: {{ Auth::user()->isAdmin() ? '∞ Ilimitado' : (Auth::user()->credits_basic ?? 0) }}
                    </span>
                    <span class="px-2.5 py-1 rounded-xl bg-purple-500/10 border border-purple-500/30 text-purple-300 font-mono text-xs font-bold">
                        💎 PRO: {{ Auth::user()->isAdmin() ? '∞ Ilimitado' : (Auth::user()->credits_pro ?? 0) }}
                    </span>
                </div>
            </div>

            <form :action="'/resellers/' + selectedReseller?.id + '/activate-credit'" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Proveedor o Revendedor a Activar</label>
                    <div class="p-3 rounded-xl bg-[#0b0f19] border border-gray-800 text-xs font-bold text-white" x-text="selectedReseller?.name + ' (' + (selectedReseller?.brand_name || '') + ')'"></div>
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-400">Selecciona el Tipo de Panel *</label>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <label class="p-3 rounded-xl border border-gray-800 bg-[#0b0f19] cursor-pointer hover:border-amber-500/50 flex flex-col justify-between">
                            <div class="flex items-center justify-between">
                                <input type="radio" name="panel_tier" value="basic" checked class="text-amber-500 focus:ring-0">
                                <span class="text-[10px] font-mono text-amber-400 font-bold">-1 CR</span>
                            </div>
                            <div class="mt-2 font-bold text-white">📦 BÁSICO</div>
                            <div class="text-[10px] text-gray-400 mt-0.5">Hasta 10 servicios</div>
                        </label>

                        <label class="p-3 rounded-xl border border-gray-800 bg-[#0b0f19] cursor-pointer hover:border-purple-500/50 flex flex-col justify-between">
                            <div class="flex items-center justify-between">
                                <input type="radio" name="panel_tier" value="pro" class="text-purple-500 focus:ring-0">
                                <span class="text-[10px] font-mono text-purple-400 font-bold">-1 CR</span>
                            </div>
                            <div class="mt-2 font-bold text-white">💎 PRO ILIMITADO</div>
                            <div class="text-[10px] text-gray-400 mt-0.5">Servicios ilimitados</div>
                        </label>
                    </div>
                </div>

                <div class="p-3 rounded-2xl bg-sky-500/10 border border-sky-500/20 text-sky-300 text-xs">
                    ℹ️ Se sumarán <strong>+30 días</strong> de vigencia activa y se habilitará la entrega y venta de cuentas.
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="creditActivationModal = false" class="px-4 py-2 text-xs text-gray-400 hover:text-white cursor-pointer">Cancelar</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-lg shadow-purple-600/30 cursor-pointer">
                        Descontar 1 Crédito & Activar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL 13: AJUSTAR PRECIOS DE PROVEEDOR (COSTO MAYORISTA) -->
    <!-- ========================================== -->
    <div x-show="planPricesModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-md" @click.self="planPricesModal = false">
        <div class="w-full max-w-2xl bg-[#111827] border border-gray-800 rounded-3xl shadow-2xl p-6 space-y-4 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between pb-3 border-b border-gray-800">
                <div class="flex items-center gap-2.5 text-emerald-400">
                    <i data-lucide="tag" class="w-6 h-6"></i>
                    <div>
                        <h3 class="font-black text-white text-base">Precios de Costo Mayorista para Revendedor</h3>
                        <span class="text-xs text-gray-400" x-text="'Configuración individual para ' + (selectedPlanPricesReseller?.name || 'Revendedor')"></span>
                    </div>
                </div>
                <button @click="planPricesModal = false" class="text-gray-400 hover:text-white text-lg font-bold cursor-pointer">✕</button>
            </div>

            <div class="p-3.5 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-300 text-xs leading-relaxed">
                💡 <strong>¿Cómo funciona?</strong> Al clonar tu catálogo, puedes ajustar el <em>Costo Mayorista de Proveedor</em> de cada plan. Cuando el revendedor realice una entrega a su cliente, este costo se descontará de su saldo de billetera, generándote a ti la ganancia de proveedor.
            </div>

            <form :action="'/resellers/' + selectedPlanPricesReseller?.id + '/update-plan-prices'" method="POST" class="space-y-4">
                @csrf

                <div class="rounded-2xl border border-gray-800 overflow-hidden">
                    <table class="w-full text-left text-xs text-gray-200">
                        <thead>
                            <tr class="bg-gray-900 border-b border-gray-800 text-[10px] uppercase font-bold text-gray-400">
                                <th class="py-2.5 px-3">Servicio / Plan</th>
                                <th class="py-2.5 px-3">PVP Sugerido</th>
                                <th class="py-2.5 px-3">Costo Base</th>
                                <th class="py-2.5 px-3">Tu Costo al Revendedor ($)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-800/60 font-mono">
                            @foreach($servicePlansList ?? [] as $sp)
                            <tr class="hover:bg-gray-800/30">
                                <td class="py-2.5 px-3 font-sans">
                                    <span class="font-bold text-white block">{{ $sp->service ? $sp->service->name : 'Streaming' }}</span>
                                    <span class="text-[11px] text-gray-400">{{ $sp->name }}</span>
                                </td>
                                <td class="py-2.5 px-3 text-sky-400">
                                    ${{ number_format($sp->price, 2) }}
                                </td>
                                <td class="py-2.5 px-3 text-gray-400">
                                    ${{ number_format($sp->reseller_price, 2) }}
                                </td>
                                <td class="py-2.5 px-3">
                                    <input type="number" step="0.01" min="0" 
                                           :name="'provider_costs[' + {{ $sp->id }} + ']'" 
                                           :value="currentPlanCosts[{{ $sp->id }}] || '{{ $sp->reseller_price }}'" 
                                           class="w-32 px-3 py-1.5 rounded-xl bg-gray-950 border border-gray-700 text-xs font-mono text-emerald-400 font-bold focus:border-emerald-500 outline-none">
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="planPricesModal = false" class="px-4 py-2 text-xs text-gray-400 hover:text-white cursor-pointer">Cancelar</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-lg shadow-emerald-600/30 cursor-pointer">
                        Guardar Precios de Proveedor
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL 14: PREFERENCIA DE WHATSAPP EN BÓVEDA -->
    <!-- ========================================== -->
    <div x-show="whatsappPrefModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-md" @click.self="whatsappPrefModal = false">
        <div class="w-full max-w-md bg-[#111827] border border-gray-800 rounded-3xl shadow-2xl p-6 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-gray-800">
                <div class="flex items-center gap-2.5 text-teal-400">
                    <i data-lucide="message-circle" class="w-6 h-6"></i>
                    <div>
                        <h3 class="font-black text-white text-base">Soporte WhatsApp en Bóveda</h3>
                        <span class="text-xs text-gray-400" x-text="'Destino de soporte para ' + (selectedPrefReseller?.name || 'este panel')"></span>
                    </div>
                </div>
                <button @click="whatsappPrefModal = false" class="text-gray-400 hover:text-white text-lg font-bold cursor-pointer">✕</button>
            </div>

            <form :action="'/resellers/' + selectedPrefReseller?.id + '/update-whatsapp-preference'" method="POST" class="space-y-4">
                @csrf

                <div class="space-y-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-400">Seleccionar Destino del Botón WhatsApp *</label>

                    <label class="p-3 rounded-2xl border border-gray-800 bg-[#0b0f19] cursor-pointer hover:border-teal-500/40 flex items-start gap-3">
                        <input type="radio" name="whatsapp_support_preference" value="auto" x-model="currentWhatsAppPref" class="mt-1 text-teal-500 focus:ring-0">
                        <div>
                            <span class="text-xs font-bold text-white block">🤖 Automático (Recomendado)</span>
                            <span class="text-[11px] text-gray-400">Usa el WhatsApp propio del revendedor si configuró teléfono; de lo contrario usa el del proveedor o Super Admin.</span>
                        </div>
                    </label>

                    <label class="p-3 rounded-2xl border border-gray-800 bg-[#0b0f19] cursor-pointer hover:border-teal-500/40 flex items-start gap-3">
                        <input type="radio" name="whatsapp_support_preference" value="reseller" x-model="currentWhatsAppPref" class="mt-1 text-teal-500 focus:ring-0">
                        <div>
                            <span class="text-xs font-bold text-white block">👤 WhatsApp Propio del Revendedor</span>
                            <span class="text-[11px] text-gray-400">Todas las consultas de cuentas de este panel van directo al teléfono de este revendedor.</span>
                        </div>
                    </label>

                    <label class="p-3 rounded-2xl border border-gray-800 bg-[#0b0f19] cursor-pointer hover:border-teal-500/40 flex items-start gap-3">
                        <input type="radio" name="whatsapp_support_preference" value="provider" x-model="currentWhatsAppPref" class="mt-1 text-teal-500 focus:ring-0">
                        <div>
                            <span class="text-xs font-bold text-white block">🛡️ WhatsApp Central del Proveedor Patrocinador</span>
                            <span class="text-[11px] text-gray-400">El proveedor patrocinador atiende las consultas técnicas y garantías de este panel.</span>
                        </div>
                    </label>

                    <label class="p-3 rounded-2xl border border-gray-800 bg-[#0b0f19] cursor-pointer hover:border-teal-500/40 flex items-start gap-3">
                        <input type="radio" name="whatsapp_support_preference" value="admin" x-model="currentWhatsAppPref" class="mt-1 text-teal-500 focus:ring-0">
                        <div>
                            <span class="text-xs font-bold text-white block">⚡ WhatsApp Central del Super Admin</span>
                            <span class="text-[11px] text-gray-400">Centralizar el soporte en el número maestro de la plataforma streaming.</span>
                        </div>
                    </label>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="whatsappPrefModal = false" class="px-4 py-2 text-xs text-gray-400 hover:text-white cursor-pointer">Cancelar</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-500 text-white font-bold text-xs shadow-lg shadow-teal-600/30 cursor-pointer">
                        Guardar Preferencia
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL: AUDITORÍA DE COMPROBANTE MINI NUBE (TRANSFERENCIAS) -->
    <!-- ======================================================== -->
    <div x-show="transferAuditModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-md">
        <div @click.away="transferAuditModal = false" class="w-full max-w-2xl bg-[#111827] border border-gray-800 rounded-3xl shadow-2xl overflow-hidden max-h-[92vh] flex flex-col">
            <!-- Modal Header -->
            <div class="p-5 border-b border-gray-800 flex items-center justify-between bg-[#0e1424]">
                <div>
                    <h3 class="text-sm font-bold text-white flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-cyan-400 animate-pulse"></span>
                        <span>Auditoría de Comprobante (Mini Nube)</span>
                        <span class="font-mono text-cyan-400" x-text="transferRef ? ('Ref: ' + transferRef) : ''"></span>
                    </h3>
                    <p class="text-[11px] text-gray-400 mt-0.5">
                        Revendedor: <strong class="text-white" x-text="transferResellerName"></strong> · Monto Notificado: <strong class="text-emerald-400" x-text="'$' + transferAmount"></strong> · Método: <span class="uppercase text-sky-300 font-mono" x-text="transferMethod"></span>
                    </p>
                </div>
                <button @click="transferAuditModal = false" class="text-gray-400 hover:text-white cursor-pointer">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Modal Content: Preview Comprobante -->
            <div class="p-5 overflow-y-auto flex-1 space-y-4">
                <div class="rounded-2xl border border-gray-800 bg-black/40 overflow-hidden flex flex-col items-center justify-center min-h-[300px] p-3">
                    <template x-if="transferReceiptUrl">
                        <div class="w-full flex flex-col items-center gap-3">
                            <img :src="transferReceiptUrl" alt="Comprobante en Mini Nube" class="max-h-[380px] w-auto object-contain rounded-xl shadow-lg border border-gray-800">
                            <a :href="transferReceiptUrl" target="_blank" class="px-3.5 py-1.5 rounded-xl bg-gray-800 hover:bg-gray-700 text-cyan-300 border border-gray-700 text-xs font-bold flex items-center gap-1.5 transition-all">
                                <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                                <span>Ver Comprobante Completo (Nueva Pestaña)</span>
                            </a>
                        </div>
                    </template>
                    <template x-if="!transferReceiptUrl">
                        <div class="text-center text-gray-500 py-12 text-xs">
                            Sin archivo de comprobante adjunto o ya fue purgado de la Mini Nube tras su auditoría.
                        </div>
                    </template>
                </div>

                <template x-if="transferNotes">
                    <div class="p-3 rounded-xl bg-slate-900 border border-slate-800 text-xs text-gray-300">
                        <strong class="text-gray-400 block mb-0.5 text-[10px] uppercase">Notas del Revendedor:</strong>
                        <span x-text="transferNotes"></span>
                    </div>
                </template>

                <div class="p-3.5 rounded-xl bg-amber-950/20 border border-amber-500/30 text-amber-300 text-xs space-y-1">
                    <div class="flex items-center gap-2 font-bold">
                        <i data-lucide="shield-alert" class="w-4 h-4 text-amber-400"></i>
                        <span>Inspección Segura & Purga Automática Mini Nube:</span>
                    </div>
                    <p class="text-[11px] text-amber-200/90 leading-relaxed">
                        Verifica que el monto y titular del comprobante coincidan. Al presionar <strong>"Aprobar y Acreditar Saldo"</strong> (o Rechazar), el archivo del comprobante <strong>se purgará inmediatamente del servidor</strong> para optimizar el espacio de disco en hosting compartido cPanel.
                    </p>
                </div>
            </div>

            <!-- Modal Footer: Aprobar o Rechazar -->
            <div class="p-5 border-t border-gray-800 bg-[#0e1424] flex items-center justify-between gap-3">
                <!-- Rechazar -->
                <form :action="'/resellers/balance-transfers/' + activeTransfer?.id + '/reject'" method="POST" onsubmit="return confirm('¿Seguro que deseas rechazar esta solicitud de saldo?');">
                    @csrf
                    <button type="submit" class="px-4 py-2.5 rounded-xl bg-rose-600/20 text-rose-400 hover:bg-rose-600 hover:text-white border border-rose-500/30 text-xs font-bold transition-all cursor-pointer">
                        Rechazar Solicitud
                    </button>
                </form>

                <!-- Aprobar -->
                <form :action="'/resellers/balance-transfers/' + activeTransfer?.id + '/approve'" method="POST">
                    @csrf
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs flex items-center gap-2 shadow-lg shadow-emerald-600/30 transition-all cursor-pointer">
                        <i data-lucide="check" class="w-4 h-4"></i>
                        <span>Aprobar y Acreditar Saldo (<span x-text="'$' + transferAmount"></span>)</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection
