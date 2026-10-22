<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Instalar {{ $brandName }} | App Oficial</title>

    <!-- PWA Primary Meta Tags -->
    <meta name="theme-color" content="{{ $primaryColor }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="{{ $brandName }}">
    <link rel="manifest" href="{{ route('pwa.manifest', ['ref' => $seller ? $seller->referral_code : '']) }}">
    <link rel="apple-touch-icon" href="{{ $brandIcon }}">
    <link rel="icon" type="image/svg+xml" href="{{ $brandIcon }}">

    <!-- Tailwind CSS CDN & Lucide Icons -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        body {
            background-color: #0b0f19;
            color: #f8fafc;
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            -webkit-tap-highlight-color: transparent;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-between selection:bg-red-500 selection:text-white" x-data="{
    deferredPrompt: null,
    isInstalled: false,
    isIos: false,
    showIosGuide: false,
    copied: false,
    init() {
        // Detect iOS
        const userAgent = window.navigator.userAgent.toLowerCase();
        this.isIos = /iphone|ipad|ipod/.test(userAgent);

        // Detect Standalone mode (already installed)
        if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true) {
            this.isInstalled = true;
        }

        // Capture Chrome / Edge / Android install prompt
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            this.deferredPrompt = e;
        });

        window.addEventListener('appinstalled', () => {
            this.isInstalled = true;
            this.deferredPrompt = null;
        });
    },
    async triggerInstall() {
        if (this.isIos) {
            this.showIosGuide = true;
            return;
        }
        if (this.deferredPrompt) {
            this.deferredPrompt.prompt();
            const { outcome } = await this.deferredPrompt.userChoice;
            if (outcome === 'accepted') {
                this.isInstalled = true;
            }
            this.deferredPrompt = null;
        } else {
            alert('Para instalar la App:\n• En Android/Chrome: Abre el menú de 3 puntos (⋮) y selecciona \'Instalar aplicación\' o \'Agregar a la pantalla principal\'.\n• En PC: Haz clic en el ícono de instalación en la barra de direcciones.');
        }
    },
    copyShareLink() {
        navigator.clipboard.writeText('{{ $shareUrl }}');
        this.copied = true;
        setTimeout(() => { this.copied = false; }, 2500);
    }
}">

    <!-- Background Ambient Glow -->
    <div class="fixed inset-0 pointer-events-none overflow-hidden z-0">
        <div class="absolute -top-40 -left-40 w-96 h-96 rounded-full blur-3xl opacity-20" style="background-color: {{ $primaryColor }}"></div>
        <div class="absolute top-1/2 -right-40 w-80 h-80 rounded-full blur-3xl opacity-15" style="background-color: {{ $accentColor }}"></div>
    </div>

    <!-- Main Content Container -->
    <main class="relative z-10 max-w-lg mx-auto w-full px-5 py-8 flex-1 flex flex-col justify-center space-y-6">

        <!-- Header / Logo -->
        <div class="text-center space-y-4">
            <div class="inline-flex p-4 rounded-3xl bg-slate-900/90 border border-slate-800 shadow-2xl backdrop-blur-md">
                <img src="{{ $brandLogo }}" alt="{{ $brandName }}" class="h-16 w-auto max-w-[200px] object-contain mx-auto drop-shadow-md">
            </div>

            <div>
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-[11px] font-bold font-mono">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>APLICACIÓN WEB OFICIAL PWA</span>
                    @if($seller && $seller->referral_code)
                    <span>• {{ $seller->referral_code }}</span>
                    @endif
                </div>

                <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight mt-2">
                    {{ $brandName }}
                </h1>
                <p class="text-xs sm:text-sm text-slate-400 mt-1 max-w-xs mx-auto">
                    Instala nuestra App en tu teléfono, tablet o computadora sin descargar de tiendas. Rápida, ligera y con acceso directo a tu Bóveda.
                </p>
            </div>
        </div>

        <!-- App Features Pills -->
        <div class="grid grid-cols-3 gap-2.5 text-center text-xs">
            <div class="p-3 rounded-2xl bg-slate-900/80 border border-slate-800">
                <i data-lucide="zap" class="w-5 h-5 text-amber-400 mx-auto mb-1"></i>
                <span class="text-[11px] font-bold text-white block">Súper Ligera</span>
                <span class="text-[9px] text-slate-400">Menos de 2 MB</span>
            </div>
            <div class="p-3 rounded-2xl bg-slate-900/80 border border-slate-800">
                <i data-lucide="shield-check" class="w-5 h-5 text-emerald-400 mx-auto mb-1"></i>
                <span class="text-[11px] font-bold text-white block">Bóveda PIN</span>
                <span class="text-[9px] text-slate-400">Con Biometría</span>
            </div>
            <div class="p-3 rounded-2xl bg-slate-900/80 border border-slate-800">
                <i data-lucide="bell" class="w-5 h-5 text-sky-400 mx-auto mb-1"></i>
                <span class="text-[11px] font-bold text-white block">Entrega 24/7</span>
                <span class="text-[9px] text-slate-400">Sin esperas</span>
            </div>
        </div>

        <!-- Primary Action: Install Button -->
        <div class="space-y-3">
            <template x-if="!isInstalled">
                <button type="button" @click="triggerInstall()" class="w-full py-4 px-6 rounded-2xl bg-gradient-to-r from-red-600 via-rose-600 to-amber-600 hover:opacity-95 text-white font-black text-sm uppercase tracking-wider flex items-center justify-center gap-3 shadow-2xl shadow-red-600/40 transition-all cursor-pointer transform active:scale-[0.98]">
                    <i data-lucide="download" class="w-5 h-5"></i>
                    <span>📲 Instalar Aplicación</span>
                </button>
            </template>

            <template x-if="isInstalled">
                <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-bold text-center flex items-center justify-center gap-2">
                    <i data-lucide="check-circle" class="w-5 h-5"></i>
                    <span>¡App instalada en este dispositivo! Puedes abrirla desde tu pantalla de inicio.</span>
                </div>
            </template>

            <!-- WhatsApp Share Button -->
            <a href="{{ $whatsappShareUrl }}" target="_blank" class="w-full py-3.5 px-6 rounded-2xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs flex items-center justify-center gap-2 shadow-xl shadow-emerald-600/30 transition-all cursor-pointer">
                <i data-lucide="message-circle" class="w-4 h-4"></i>
                <span>💬 Compartir App por WhatsApp</span>
            </a>

            <!-- Copy Short URL Button -->
            <button type="button" @click="copyShareLink()" class="w-full py-3 px-4 rounded-xl bg-slate-900/90 hover:bg-slate-800 border border-slate-800 text-slate-300 font-mono text-xs flex items-center justify-center gap-2 transition-all cursor-pointer">
                <i data-lucide="copy" class="w-3.5 h-3.5 text-sky-400"></i>
                <span x-text="copied ? '✓ ¡Link copiado al portapapeles!' : 'Copiar URL Corta: {{ $shareUrl }}'"></span>
            </button>
        </div>

        <!-- Direct Portal Navigation -->
        <div class="pt-4 border-t border-slate-800 grid grid-cols-2 gap-3 text-xs">
            <a href="{{ $catalogUrl }}" class="p-3 rounded-2xl bg-slate-950/90 border border-slate-800 hover:border-slate-700 text-center transition-all">
                <i data-lucide="tv" class="w-4 h-4 text-red-400 mx-auto mb-1"></i>
                <strong class="text-white block">Catálogo Web</strong>
                <span class="text-[10px] text-slate-400">Ver Cuentas</span>
            </a>

            <a href="{{ $vaultUrl }}" class="p-3 rounded-2xl bg-slate-950/90 border border-slate-800 hover:border-slate-700 text-center transition-all">
                <i data-lucide="key" class="w-4 h-4 text-amber-400 mx-auto mb-1"></i>
                <strong class="text-white block">Bóveda Clientes</strong>
                <span class="text-[10px] text-slate-400">Ver Credenciales</span>
            </a>
        </div>

        @if($supportWhatsapp)
        <!-- Contact Reseller / Support -->
        <div class="text-center pt-2">
            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $supportWhatsapp) }}?text=Hola%20{{ urlencode($brandName) }},%20tengo%20una%20consulta%20sobre%20la%20App%20de%20streaming." target="_blank" class="text-xs text-slate-400 hover:text-emerald-400 transition-colors inline-flex items-center gap-1 font-mono">
                <i data-lucide="help-circle" class="w-3.5 h-3.5"></i>
                <span>¿Necesitas ayuda? Contactar Soporte</span>
            </a>
        </div>
        @endif
    </main>

    <!-- iOS Guide Modal -->
    <div x-show="showIosGuide" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4 bg-black/85 backdrop-blur-md">
        <div @click.away="showIosGuide = false" class="w-full max-w-sm bg-[#111827] border border-gray-800 rounded-3xl p-6 shadow-2xl space-y-4 text-center">
            <div class="w-12 h-12 rounded-2xl bg-sky-500/20 text-sky-400 border border-sky-500/30 flex items-center justify-center mx-auto">
                <i data-lucide="smartphone" class="w-6 h-6"></i>
            </div>
            <div>
                <h3 class="text-base font-bold text-white">Instalar en iPhone / iPad (Safari)</h3>
                <p class="text-xs text-slate-400 mt-1">Apple requiere 2 simples toques para instalar la aplicación:</p>
            </div>
            <div class="p-4 rounded-2xl bg-slate-900 border border-slate-800 text-left text-xs text-slate-300 space-y-2.5">
                <div class="flex items-start gap-2.5">
                    <span class="w-5 h-5 rounded-full bg-sky-500 text-slate-950 font-bold flex items-center justify-center text-[10px] shrink-0 mt-0.5">1</span>
                    <span>Toca el botón <strong>Compartir</strong> <span class="text-sky-400 font-mono text-base">⎋</span> en la barra inferior de Safari.</span>
                </div>
                <div class="flex items-start gap-2.5">
                    <span class="w-5 h-5 rounded-full bg-sky-500 text-slate-950 font-bold flex items-center justify-center text-[10px] shrink-0 mt-0.5">2</span>
                    <span>Desliza y selecciona <strong>"Agregar a pantalla de inicio" ➕</strong>.</span>
                </div>
            </div>
            <button type="button" @click="showIosGuide = false" class="w-full py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs">
                Entendido
            </button>
        </div>
    </div>

    <!-- Footer -->
    <footer class="relative z-10 py-4 text-center text-[11px] text-slate-500">
        © {{ date('Y') }} {{ $brandName }}. Todos los derechos reservados.
    </footer>

    <!-- Service Worker Registration -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then((reg) => console.log('PWA ServiceWorker registrado:', reg.scope))
                    .catch((err) => console.warn('PWA ServiceWorker error:', err));
            });
        }
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) {
                window.lucide.createIcons();
            }
        });
    </script>
</body>
</html>
