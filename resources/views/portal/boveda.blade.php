@extends('layouts.public')

@section('title', 'Bóveda Privada de Credenciales de Streaming')

@section('content')
@php
    $brand = ($reseller && $reseller->brand_name) ? $reseller->brand_name : config('app.name', 'CStreame Master');
    $whatsapp = ($reseller && $reseller->support_whatsapp) ? $reseller->support_whatsapp : '5491199887766';
    $referralCode = ($reseller && $reseller->referral_code) ? $reseller->referral_code : '';
    $antiPhishingCode = $customer->anti_phishing_phrase ?: 'CST-SAFE-' . ($customer->customer_code ?: $customer->id);
@endphp

<div class="max-w-6xl mx-auto py-8 px-4 sm:px-6 space-y-8" x-data="{
    reportModal: false,
    unlockModal: false,
    vaultUnlocked: false,
    unlockTab: 'biometric',
    unlockPassword: '',
    unlockError: '',
    isVerifying: false,
    selectedSale: null,
    showPasswords: {},
    togglePassword(id) {
        if (!this.vaultUnlocked) {
            this.unlockModal = true;
            return;
        }
        this.showPasswords[id] = !this.showPasswords[id];
    },
    copyToClipboard(text, label) {
        if (!this.vaultUnlocked && (label === 'Contraseña' || label === 'PIN')) {
            this.unlockModal = true;
            return;
        }
        navigator.clipboard.writeText(text);
        alert(label + ' copiado al portapapeles: ' + text);
    },
    openReportModal(sale) {
        this.selectedSale = sale;
        this.reportModal = true;
    },
    lockVault() {
        this.vaultUnlocked = false;
        this.showPasswords = {};
    },
    async triggerBiometric() {
        this.isVerifying = true;
        this.unlockError = '';
        try {
            // Intentar autenticación biométrica en el dispositivo (WebAuthn / Sensor de huella / FaceID)
            if (window.PublicKeyCredential && PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable) {
                await PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable();
            }

            const response = await fetch('{{ route('boveda.verify_unlock') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ type: 'biometric' })
            });

            const data = await response.json();
            if (data.success) {
                this.vaultUnlocked = true;
                this.unlockModal = false;
            } else {
                this.unlockError = data.message || 'Fallo en la verificación biométrica.';
            }
        } catch (err) {
            this.unlockError = 'No se pudo comunicar con el sensor biométrico del dispositivo.';
        } finally {
            this.isVerifying = false;
        }
    },
    async verifyPassword() {
        if (!this.unlockPassword) {
            this.unlockError = 'Ingresa tu contraseña o PIN de Bóveda.';
            return;
        }
        this.isVerifying = true;
        this.unlockError = '';
        try {
            const response = await fetch('{{ route('boveda.verify_unlock') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    type: 'password',
                    password: this.unlockPassword
                })
            });

            const data = await response.json();
            if (data.success) {
                this.vaultUnlocked = true;
                this.unlockModal = false;
                this.unlockPassword = '';
            } else {
                this.unlockError = data.message || 'Contraseña incorrecta.';
            }
        } catch (err) {
            this.unlockError = 'Error de conexión al validar contraseña.';
        } finally {
            this.isVerifying = false;
        }
    }
}">

    <!-- Top Client & Brand Banner con Sello Anti-Phishing y Desbloqueo Biométrico -->
    <div class="bg-gradient-to-r from-[#0f172a] via-[#1e293b] to-[#0f172a] p-6 sm:p-8 rounded-3xl border border-slate-800 shadow-2xl relative overflow-hidden space-y-4">
        <!-- Glow effects -->
        <div class="absolute -top-12 -left-12 w-48 h-48 bg-red-600/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-12 -right-12 w-48 h-48 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 relative z-10">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-red-600 to-amber-500 flex items-center justify-center text-white shadow-xl shadow-red-600/20 text-2xl font-black">
                    <i data-lucide="shield-check" class="w-8 h-8"></i>
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-xs font-mono font-bold px-2.5 py-0.5 rounded-full bg-red-600/20 text-red-400 border border-red-500/30">
                            {{ $customer->customer_code ?: 'CLI-' . $customer->id }}
                        </span>
                        <!-- Sello Anti-Phishing Oficial -->
                        <span class="text-[11px] font-mono font-bold px-2.5 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 flex items-center gap-1">
                            <i data-lucide="shield-alert" class="w-3 h-3"></i>
                            <span>Anti-Phishing: <strong>{{ $antiPhishingCode }}</strong></span>
                        </span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-black text-white mt-1">¡Hola, {{ $customer->name }}!</h1>
                    <p class="text-xs text-slate-400">Distribuidor autorizado: <strong class="text-amber-400">{{ $brand }}</strong></p>
                </div>
            </div>

            <!-- Botones de Acción y Desbloqueo Biométrico -->
            <div class="flex flex-wrap items-center gap-3">
                <!-- Botón de Estado de Bloqueo / Desbloqueo Biométrico -->
                <template x-if="!vaultUnlocked">
                    <button @click="unlockModal = true" class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-amber-600 to-red-600 hover:opacity-90 text-white font-bold text-xs flex items-center gap-2 shadow-lg shadow-red-600/20 transition-all cursor-pointer">
                        <i data-lucide="fingerprint" class="w-4 h-4"></i>
                        <span>Desbloquear Credenciales</span>
                    </button>
                </template>

                <template x-if="vaultUnlocked">
                    <button @click="lockVault()" class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs flex items-center gap-2 shadow-lg transition-all cursor-pointer">
                        <i data-lucide="lock" class="w-4 h-4"></i>
                        <span>Bóveda Desbloqueada (Bloquear)</span>
                    </button>
                </template>

                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $whatsapp) }}?text=Hola,%20soy%20{{ urlencode($customer->name) }}%20(N°%20Cliente:%20{{ $customer->customer_code }})%20y%20tengo%20una%20consulta%20sobre%20mi%20boveda%20de%20streaming." target="_blank" class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs flex items-center gap-2 shadow-lg transition-all">
                    <i data-lucide="message-circle" class="w-4 h-4"></i>
                    <span>Soporte WhatsApp</span>
                </a>

                <form method="POST" action="{{ route('boveda.logout') }}">
                    @csrf
                    <button type="submit" class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs flex items-center gap-1.5 border border-slate-700 transition-all cursor-pointer">
                        <i data-lucide="log-out" class="w-4 h-4"></i>
                        <span>Cerrar Sesión</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Banner de Estado de Seguridad Anti-Phishing -->
        <div class="pt-3 border-t border-slate-800/80 flex flex-wrap items-center justify-between gap-2 text-[11px] text-slate-400">
            <span class="flex items-center gap-1.5 text-emerald-400">
                <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
                <span>Servidor Cifrado AES-256 en Base de Datos & Conexión Certificada</span>
            </span>
            <span class="text-slate-500 font-mono">
                Autenticación biométrica WebAuthn habilitada
            </span>
        </div>
    </div>

    @if(session('success'))
    <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs flex items-center gap-2.5">
        <i data-lucide="check-circle" class="w-5 h-5 shrink-0"></i>
        <span class="font-semibold">{{ session('success') }}</span>
    </div>
    @endif

    <!-- SECCIÓN: CUENTAS ACTIVAS CON CREDENCIALES OFICIALES -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="w-3 h-3 rounded-full bg-emerald-400 animate-ping"></div>
                <h2 class="text-xl font-black text-white">Tus Servicios & Credenciales Activas</h2>
            </div>
            <span class="text-xs font-mono font-bold text-slate-400 bg-slate-900 px-3 py-1 rounded-xl border border-slate-800">
                {{ $activeSales->count() }} Suscripción(es) Activa(s)
            </span>
        </div>

        @if($activeSales->isEmpty())
        <div class="text-center py-12 px-6 rounded-3xl bg-[#0f172a]/60 border border-slate-800 border-dashed space-y-3">
            <div class="w-16 h-16 mx-auto rounded-2xl bg-slate-800/80 flex items-center justify-center text-slate-400 text-2xl">
                🍿
            </div>
            <h3 class="text-lg font-bold text-white">Aún no tienes suscripciones activas en tu Bóveda</h3>
            <p class="text-xs text-slate-400 max-w-md mx-auto">
                Elige cualquiera de las plataformas líderes a continuación (Netflix, HBO Max, Disney Estándar, Disney Premium) para solicitar tu cuenta oficial de inmediato.
            </p>
        </div>
        @else
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @foreach($activeSales as $sale)
            @php
                $srv = $sale->service;
                $srvName = $srv ? $srv->name : 'Streaming Oficial';
                $srvColor = $srv ? ($srv->color_hex ?? '#E50914') : '#E50914';
                $planName = $sale->plan ? $sale->plan->name : 'Plan Estándar';
                $days = $sale->days_remaining;
                $badge = $sale->status_badge ?? [
                    'label' => 'Activa (Garantía OK)',
                    'class' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
                    'icon' => 'check-circle'
                ];
                $endDate = $sale->end_date ? \Carbon\Carbon::parse($sale->end_date)->format('d/m/Y') : '30 días';
                $isConsignment = $sale->is_consignment && !$sale->is_consignment_paid;
                $hoursLeft = $sale->consignment_hours_remaining;
            @endphp
            <div class="bg-[#0f172a] rounded-3xl border border-slate-800 shadow-xl overflow-hidden hover:border-slate-700 transition-all flex flex-col justify-between">
                <!-- Card Header -->
                <div class="p-5 pb-4 border-b border-slate-800/80 flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="w-4 h-4 rounded-full shrink-0 shadow" style="background-color: {{ $srvColor }}"></span>
                        <div>
                            <h3 class="font-black text-white text-base">{{ $srvName }}</h3>
                            <span class="text-xs text-sky-400 font-mono">{{ $planName }} ({{ $sale->months_duration ?: 1 }} mes/es)</span>
                        </div>
                    </div>
                    <span class="text-[10px] font-bold px-2.5 py-1 rounded-full border {{ $badge['class'] }}">
                        {{ $badge['label'] }}
                    </span>
                </div>

                <!-- ALERTA DE CONSIGNACIÓN 48H -->
                @if($isConsignment)
                <div class="p-3 mx-5 mt-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-300 text-xs flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i data-lucide="clock" class="w-4 h-4 text-amber-400 animate-pulse"></i>
                        <span><strong>Cuenta en Consignación:</strong> {{ $hoursLeft ?? 48 }} horas restantes para liquidación por tu revendedor.</span>
                    </div>
                </div>
                @endif

                <!-- Credenciales Oficiales -->
                <div class="p-5 space-y-4 text-xs font-mono">
                    <!-- Correo Oficial -->
                    <div class="bg-slate-900/90 p-3 rounded-2xl border border-slate-800">
                        <span class="text-[10px] uppercase font-bold text-slate-400 font-sans block mb-1">Correo Electrónico Oficial</span>
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-white text-sm font-bold truncate">{{ $sale->account_email_snapshot }}</span>
                            <button @click="copyToClipboard('{{ $sale->account_email_snapshot }}', 'Correo')" class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-sky-400 hover:text-white transition-colors text-xs font-sans font-bold flex items-center gap-1 cursor-pointer">
                                <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                                <span>Copiar</span>
                            </button>
                        </div>
                    </div>

                    <!-- Contraseña Oficial (Protegida por Biometría) -->
                    <div class="bg-slate-900/90 p-3 rounded-2xl border border-slate-800">
                        <span class="text-[10px] uppercase font-bold text-slate-400 font-sans block mb-1">Contraseña de Acceso</span>
                        <div class="flex items-center justify-between gap-2">
                            <!-- Si la Bóveda está Desbloqueada -->
                            <template x-if="vaultUnlocked">
                                <div class="flex items-center justify-between w-full">
                                    <div>
                                        <span x-show="!showPasswords[{{ $sale->id }}]" class="text-amber-400 text-sm font-bold tracking-widest">••••••••</span>
                                        <span x-show="showPasswords[{{ $sale->id }}]" class="text-amber-400 text-sm font-bold">{{ $sale->account_password_snapshot }}</span>
                                    </div>
                                    <div class="flex items-center gap-1.5 font-sans">
                                        <button type="button" @click="togglePassword({{ $sale->id }})" class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition-colors text-xs font-bold cursor-pointer">
                                            <span x-text="showPasswords[{{ $sale->id }}] ? 'Ocultar' : 'Ver'"></span>
                                        </button>
                                        <button @click="copyToClipboard('{{ $sale->account_password_snapshot }}', 'Contraseña')" class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-amber-400 hover:text-white transition-colors text-xs font-bold flex items-center gap-1 cursor-pointer">
                                            <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                                            <span>Copiar</span>
                                        </button>
                                    </div>
                                </div>
                            </template>

                            <!-- Si la Bóveda está Bloqueada -->
                            <template x-if="!vaultUnlocked">
                                <div class="flex items-center justify-between w-full">
                                    <span class="text-slate-500 font-bold tracking-wider">🔒 Clave Bloqueada</span>
                                    <button @click="unlockModal = true" class="px-2.5 py-1 rounded-lg bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 text-xs font-sans font-bold flex items-center gap-1 cursor-pointer">
                                        <i data-lucide="fingerprint" class="w-3.5 h-3.5"></i>
                                        <span>Desbloquear</span>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Perfil y PIN Asignado (Protegido por Biometría) -->
                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-slate-900/90 p-3 rounded-2xl border border-slate-800">
                            <span class="text-[10px] uppercase font-bold text-slate-400 font-sans block mb-1">Perfil Asignado</span>
                            <span class="text-white font-bold text-xs block truncate">{{ $sale->profile_name_snapshot ?: 'Perfil 1' }}</span>
                        </div>

                        <div class="bg-slate-900/90 p-3 rounded-2xl border border-slate-800">
                            <span class="text-[10px] uppercase font-bold text-slate-400 font-sans block mb-1">PIN de Seguridad</span>
                            <template x-if="vaultUnlocked">
                                <div class="flex items-center justify-between">
                                    <span class="text-emerald-400 font-black text-sm">{{ $sale->profile_pin_snapshot ?: 'Sin PIN' }}</span>
                                    @if($sale->profile_pin_snapshot)
                                    <button @click="copyToClipboard('{{ $sale->profile_pin_snapshot }}', 'PIN')" class="text-slate-400 hover:text-emerald-400 cursor-pointer">
                                        <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                                    </button>
                                    @endif
                                </div>
                            </template>
                            <template x-if="!vaultUnlocked">
                                <span class="text-slate-500 font-bold">🔒 PIN Oculto</span>
                            </template>
                        </div>
                    </div>

                    <!-- Vencimiento SLA -->
                    <div class="bg-slate-950 p-3 rounded-2xl border border-slate-900 flex items-center justify-between text-xs font-sans">
                        <div>
                            <span class="text-[10px] text-slate-500 uppercase block">Vencimiento Oficial</span>
                            <span class="font-bold text-slate-200">{{ $endDate }}</span>
                        </div>
                        <div class="text-right">
                            <span class="text-[11px] font-bold {{ $days <= 3 ? 'text-amber-400 animate-pulse' : 'text-emerald-400' }}">
                                {{ $days < 0 ? 'Expiró hace ' . abs($days) . ' días' : ($days === 0 ? '¡Vence hoy!' : 'Vence en ' . $days . ' días') }}
                            </span>
                        </div>
                    </div>

                    <!-- Reglas de Uso -->
                    <div class="p-2.5 rounded-xl bg-amber-500/5 border border-amber-500/20 text-[11px] text-amber-300 font-sans leading-relaxed">
                        ⚠️ <strong>Reglas de Uso:</strong> Prohibido modificar correo o contraseña. Ingresar ÚNICAMENTE al perfil asignado con tu PIN. Válido para 1 dispositivo a la vez.
                    </div>
                </div>

                <!-- Footer Card Actions -->
                <div class="p-4 bg-slate-950/60 border-t border-slate-800/80 flex items-center justify-between gap-2">
                    <button @click="openReportModal({{ json_encode($sale) }})" class="px-3.5 py-2 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer">
                        <i data-lucide="alert-triangle" class="w-3.5 h-3.5"></i>
                        <span>Reportar Falla</span>
                    </button>

                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $whatsapp) }}?text=Hola,%20deseo%20renovar%20mi%20servicio%20de%20*{{ urlencode($srvName) }}*%20(Cliente:%20{{ urlencode($customer->name) }},%20Código:%20{{ $customer->customer_code }})." target="_blank" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition-all flex items-center gap-1.5 shadow">
                        <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                        <span>Renovar Cuenta</span>
                    </a>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    @if(isset($historySales) && $historySales->isNotEmpty())
    <!-- SECCIÓN: HISTORIAL DE SERVICIOS ANTERIORES -->
    <div class="space-y-4 pt-4 border-t border-slate-800/80" x-data="{ showHistory: false }">
        <div class="flex items-center justify-between cursor-pointer" @click="showHistory = !showHistory">
            <div class="flex items-center gap-2">
                <i data-lucide="history" class="w-4 h-4 text-slate-400"></i>
                <h3 class="text-sm font-bold text-slate-300">Historial de Cuentas Anteriores / Vencidas</h3>
                <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-slate-800 text-slate-400">
                    {{ $historySales->count() }}
                </span>
            </div>
            <button type="button" class="text-xs text-sky-400 hover:text-sky-300 font-semibold flex items-center gap-1">
                <span x-text="showHistory ? 'Ocultar' : 'Ver Historial'"></span>
                <i data-lucide="chevron-down" class="w-3.5 h-3.5 transition-transform" :class="{ 'rotate-180': showHistory }"></i>
            </button>
        </div>

        <div x-show="showHistory" x-cloak class="space-y-2">
            @foreach($historySales as $hSale)
            @php
                $hSrv = $hSale->service;
                $hSrvName = $hSrv ? $hSrv->name : 'Streaming';
                $hEndDate = $hSale->end_date ? \Carbon\Carbon::parse($hSale->end_date)->format('d/m/Y') : '-';
            @endphp
            <div class="p-3.5 rounded-2xl bg-[#0f172a]/50 border border-slate-800/80 flex items-center justify-between gap-4 text-xs">
                <div class="flex items-center gap-3">
                    <span class="w-2.5 h-2.5 rounded-full bg-slate-600"></span>
                    <div>
                        <span class="font-bold text-white">{{ $hSrvName }}</span>
                        <span class="text-slate-500 font-mono text-[11px] block">{{ $hSale->plan ? $hSale->plan->name : 'Individual' }} • Venció el {{ $hEndDate }}</span>
                    </div>
                </div>
                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $whatsapp) }}?text=Hola,%20deseo%20reactivar%20mi%20cuenta%20de%20*{{ urlencode($hSrvName) }}*%20(Cliente:%20{{ urlencode($customer->name) }})." target="_blank" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-emerald-600 text-slate-300 hover:text-white font-bold text-[11px] transition-all flex items-center gap-1">
                    <i data-lucide="refresh-cw" class="w-3 h-3"></i>
                    <span>Reactivar</span>
                </a>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- SECCIÓN: ACTIVAR OTRAS PLATAFORMAS (CATÁLOGO RÁPIDO) -->
    <div class="space-y-4 pt-6 border-t border-slate-800">
        <div>
            <h2 class="text-xl font-black text-white">Activar Nuevas Plataformas de Streaming</h2>
            <p class="text-xs text-slate-400">Agrega más pantallas a tu Bóveda con activación garantizada</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach($catalogServices as $csrv)
            <div class="bg-[#0f172a] p-5 rounded-2xl border border-slate-800 space-y-3 flex flex-col justify-between hover:border-slate-700 transition-all">
                <div class="space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="w-3.5 h-3.5 rounded-full shrink-0" style="background-color: {{ $csrv->color_hex ?? '#E50914' }}"></span>
                        <h4 class="font-bold text-white text-sm">{{ $csrv->name }}</h4>
                    </div>
                    <p class="text-[11px] text-slate-400 line-clamp-2">{{ $csrv->description }}</p>
                </div>

                <div class="pt-2 border-t border-slate-800/80">
                    <a href="{{ route('catalog.index', array_filter(['ref' => $referralCode])) }}" class="w-full py-2.5 rounded-xl bg-slate-800 hover:bg-red-600 hover:text-white text-slate-200 text-xs font-bold transition-all flex items-center justify-center gap-1.5">
                        <i data-lucide="shopping-cart" class="w-3.5 h-3.5"></i>
                        <span>Ver Planes & Activar</span>
                    </a>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL DE DESBLOQUEO BIOMÉTRICO O CONTRASEÑA -->
    <!-- ========================================== -->
    <div x-show="unlockModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-md" @click.self="unlockModal = false">
        <div class="bg-[#0f172a] p-6 sm:p-8 rounded-3xl border border-slate-800 shadow-2xl max-w-md w-full space-y-6 relative overflow-hidden">
            <!-- Glow background -->
            <div class="absolute -top-16 -right-16 w-36 h-36 bg-amber-500/20 rounded-full blur-2xl pointer-events-none"></div>

            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <div class="flex items-center gap-2.5 text-amber-400">
                    <i data-lucide="shield-lock" class="w-6 h-6"></i>
                    <h3 class="font-black text-white text-base">Desbloquear Credenciales</h3>
                </div>
                <button @click="unlockModal = false" class="text-slate-400 hover:text-white">✕</button>
            </div>

            <p class="text-xs text-slate-400 leading-relaxed">
                Por seguridad anti-phishing, las contraseñas maestras y PINs de streaming requieren validación biométrica en tu dispositivo o confirmación de tu contraseña de Bóveda.
            </p>

            <!-- Pestañas de Selección: Biometría vs Contraseña -->
            <div class="grid grid-cols-2 gap-2 bg-slate-900/80 p-1 rounded-2xl border border-slate-800">
                <button type="button" @click="unlockTab = 'biometric'" :class="unlockTab === 'biometric' ? 'bg-red-600 text-white shadow' : 'text-slate-400 hover:text-white'" class="py-2 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                    <i data-lucide="fingerprint" class="w-4 h-4"></i>
                    <span>Biometría</span>
                </button>
                <button type="button" @click="unlockTab = 'password'" :class="unlockTab === 'password' ? 'bg-red-600 text-white shadow' : 'text-slate-400 hover:text-white'" class="py-2 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                    <i data-lucide="key" class="w-4 h-4"></i>
                    <span>Contraseña / PIN</span>
                </button>
            </div>

            <!-- Error message if any -->
            <div x-show="unlockError" x-cloak class="p-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-xs">
                <span x-text="unlockError"></span>
            </div>

            <!-- OPCIÓN 1: BIOMETRÍA -->
            <div x-show="unlockTab === 'biometric'" class="space-y-4 text-center py-2">
                <div class="w-20 h-20 mx-auto rounded-3xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400 text-3xl shadow-lg">
                    <i data-lucide="fingerprint" class="w-10 h-10 animate-pulse"></i>
                </div>
                <div>
                    <h4 class="font-bold text-white text-sm">Autenticación Biométrica</h4>
                    <p class="text-[11px] text-slate-400 mt-1">Usa tu Huella Dactilar, Face ID o Windows Hello registrado en tu dispositivo.</p>
                </div>
                <button type="button" @click="triggerBiometric()" :disabled="isVerifying" class="w-full py-3.5 rounded-xl bg-gradient-to-r from-amber-600 to-red-600 hover:opacity-90 text-white font-black text-xs tracking-wide shadow-lg shadow-red-600/30 transition-all flex items-center justify-center gap-2 cursor-pointer">
                    <i data-lucide="scan" class="w-4 h-4"></i>
                    <span x-text="isVerifying ? 'Verificando huella...' : 'Escanear Huella / Face ID'"></span>
                </button>
            </div>

            <!-- OPCIÓN 2: CONTRASEÑA O PIN -->
            <div x-show="unlockTab === 'password'" class="space-y-4 py-2">
                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Contraseña o PIN de Bóveda *</label>
                    <input type="password" x-model="unlockPassword" @keydown.enter.prevent="verifyPassword()" placeholder="••••••••" class="w-full p-3 rounded-xl bg-slate-900 border border-slate-700 text-sm text-white placeholder-slate-500 outline-none focus:border-red-500">
                </div>
                <button type="button" @click="verifyPassword()" :disabled="isVerifying" class="w-full py-3 rounded-xl bg-red-600 hover:bg-red-500 text-white font-black text-xs tracking-wide shadow-lg transition-all flex items-center justify-center gap-2 cursor-pointer">
                    <i data-lucide="unlock" class="w-4 h-4"></i>
                    <span x-text="isVerifying ? 'Verificando...' : 'Desbloquear Credenciales'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL DE REPORTE DE INCIDENCIA / CAÍDA -->
    <div x-show="reportModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm" @click.self="reportModal = false">
        <div class="bg-[#0f172a] p-6 rounded-3xl border border-slate-800 shadow-2xl max-w-md w-full space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <div class="flex items-center gap-2 text-rose-400">
                    <i data-lucide="alert-octagon" class="w-5 h-5"></i>
                    <h3 class="font-black text-white text-base">Reportar Problema en la Cuenta</h3>
                </div>
                <button @click="reportModal = false" class="text-slate-400 hover:text-white">✕</button>
            </div>

            <p class="text-xs text-slate-400">
                Si la contraseña fue cambiada por el proveedor o la pantalla no te permite ingresar, escribe los detalles para que tu distribuidor verifique el stock y te asigne reposición de garantía.
            </p>

            <form x-bind:action="'/boveda/report-issue/' + (selectedSale ? selectedSale.id : '')" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Motivo del Reporte *</label>
                    <select name="reason_type" class="w-full p-2.5 rounded-xl bg-slate-900 border border-slate-700 text-xs text-white outline-none mb-2" onchange="document.getElementById('reason_text').value = this.value">
                        <option value="Contraseña incorrecta al iniciar sesión">Contraseña incorrecta al iniciar sesión</option>
                        <option value="Perfil ocupado por otra persona">Perfil ocupado por otra persona</option>
                        <option value="Cuenta con suscripción pausada o caída">Cuenta con suscripción pausada o caída</option>
                        <option value="PIN no coincide con el perfil">PIN no coincide con el perfil</option>
                        <option value="Otro inconveniente">Otro inconveniente</option>
                    </select>
                    <textarea id="reason_text" name="reason" rows="3" required placeholder="Describe brevemente lo que sucede..." class="w-full p-3 rounded-xl bg-slate-900 border border-slate-700 text-xs text-white placeholder-slate-500 outline-none focus:border-red-500">Contraseña incorrecta al iniciar sesión</textarea>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="reportModal = false" class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 text-xs font-bold hover:bg-slate-700">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-xs font-black shadow-lg">
                        Enviar Reporte de Garantía
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
