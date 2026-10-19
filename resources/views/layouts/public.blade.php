<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Catálogo de Cuentas') | {{ $brandName ?? 'CStreame Streaming' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-[#0b0f19] text-gray-100 min-h-screen flex flex-col font-sans antialiased selection:bg-red-600 selection:text-white">

    <!-- Top Glow Effect -->
    <div class="fixed top-0 left-1/2 -translate-x-1/2 w-full max-w-7xl h-96 bg-gradient-to-b from-red-600/15 via-rose-500/10 to-transparent blur-3xl pointer-events-none -z-10"></div>

    <!-- Navigation Header -->
    <header class="border-b border-gray-800/80 bg-[#0e1424]/80 backdrop-blur-xl sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <!-- Brand / Logo -->
            <a href="{{ route('catalog.index') }}" class="flex items-center gap-3">
                @if(isset($brandLogo) && $brandLogo)
                    <img src="{{ $brandLogo }}" alt="{{ $brandName }}" class="h-10 w-auto object-contain">
                @else
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-red-600 via-rose-500 to-amber-500 flex items-center justify-center text-white text-xl font-black shadow-lg shadow-red-600/20">
                        🍿
                    </div>
                @endif
                <div>
                    <span class="text-xl font-extrabold tracking-tight bg-gradient-to-r from-white via-gray-100 to-red-300 bg-clip-text text-transparent">
                        {{ $brandName ?? 'CStreame Streaming' }}
                    </span>
                    <span class="text-[10px] text-red-400 block font-semibold uppercase tracking-wider">Tienda Oficial de Cuentas Streaming</span>
                </div>
            </a>

            <!-- Right Actions -->
            <div class="flex items-center gap-3">
                @if(isset($supportWhatsapp) && $supportWhatsapp)
                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $supportWhatsapp) }}?text=Hola,%20quisiera%20consultar%20por%20cuentas%20streaming" target="_blank" class="hidden sm:flex items-center gap-2 px-3.5 py-2 rounded-xl bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 text-xs font-bold transition-all">
                    <i data-lucide="message-circle" class="w-4 h-4"></i>
                    <span>Atención WhatsApp</span>
                </a>
                @endif

                <a href="{{ route('login') }}" class="px-4 py-2 rounded-xl bg-gray-800 hover:bg-gray-700 text-gray-200 border border-gray-700 text-xs font-bold transition-all flex items-center gap-1.5 shadow-sm">
                    <i data-lucide="lock" class="w-3.5 h-3.5 text-red-400"></i>
                    <span>Panel / Revendedores</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1">
        @yield('content')
    </main>

    <!-- Floating WhatsApp Pill Button -->
    @if(isset($supportWhatsapp) && $supportWhatsapp)
    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $supportWhatsapp) }}?text=Hola,%20necesito%20asistencia%20en%20mi%20compra%20de%20cuenta" target="_blank" class="fixed bottom-6 right-6 z-50 px-4 py-3 rounded-full bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-xs shadow-2xl shadow-emerald-500/40 flex items-center gap-2.5 transition-all hover:scale-105 active:scale-95 group">
        <i data-lucide="message-circle" class="w-5 h-5"></i>
        <span>¿Dudas? Hablá con nosotros</span>
    </a>
    @endif

    <!-- Footer -->
    <footer class="border-t border-gray-800/80 bg-[#0a0f1c] text-gray-400 text-xs py-8 mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row items-center justify-between gap-4">
            <div>
                © {{ date('Y') }} <strong>{{ $brandName ?? 'CStreame Streaming' }}</strong>. Plataforma integral de gestión y venta de cuentas streaming.
            </div>
            <div class="flex items-center gap-4">
                <span class="flex items-center gap-1.5 text-emerald-400">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                    Servidores en línea
                </span>
                <span class="text-gray-600">|</span>
                <span>Optimizado para cPanel</span>
            </div>
        </div>
    </footer>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
