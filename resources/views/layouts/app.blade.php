<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Panel') | {{ Auth::user()->brand_name ?: config('app.name', 'CStreame') }}</title>
    
    <!-- Tailwind CSS CDN & Alpine.js -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#fff1f2',
                            500: '{{ Auth::user()->brand_primary_color ?: "#e50914" }}',
                            600: '{{ Auth::user()->brand_primary_color ?: "#dc2626" }}',
                            700: '#b91c1c',
                        },
                        accent: {
                            500: '{{ Auth::user()->brand_accent_color ?: "#06b6d4" }}',
                        },
                        darkBg: '#0b0f19',
                        darkCard: '#111827',
                        darkBorder: '#1f2937',
                    }
                }
            }
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #0b0f19; }
        ::-webkit-scrollbar-thumb { background: #374151; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #4b5563; }
    </style>
</head>
<body class="bg-[#0b0f19] text-gray-100 min-h-screen flex antialiased font-sans" x-data="{ sidebarOpen: false }">

    <!-- Mobile Sidebar Backdrop -->
    <div x-show="sidebarOpen" @click="sidebarOpen = false" x-cloak class="fixed inset-0 z-40 bg-black/80 backdrop-blur-sm lg:hidden"></div>

    <!-- Sidebar -->
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-50 w-72 bg-[#0e1424] border-r border-gray-800 flex flex-col transition-transform duration-300 lg:static lg:translate-x-0">
        <!-- Logo / Brand Header -->
        <div class="h-20 flex items-center px-6 border-b border-gray-800/80 justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-rose-600 via-red-500 to-amber-500 flex items-center justify-center shadow-lg shadow-rose-600/20 text-white font-black text-xl">
                    🍿
                </div>
                <div class="overflow-hidden">
                    <h1 class="font-bold text-base leading-tight tracking-wide bg-gradient-to-r from-white via-gray-200 to-red-300 bg-clip-text text-transparent truncate">
                        {{ Auth::user()->brand_name ?: 'CStreame Master' }}
                    </h1>
                    <span class="text-[10px] px-2 py-0.5 rounded-full {{ Auth::user()->isAdmin() ? 'bg-red-500/20 text-red-400 border border-red-500/30' : 'bg-cyan-500/20 text-cyan-400 border border-cyan-500/30' }} font-semibold uppercase tracking-wider">
                        {{ Auth::user()->isAdmin() ? 'ADMIN STREAMING' : 'REVENDEDOR (' . strtoupper(Auth::user()->reseller_plan ?? 'STANDARD') . ')' }}
                    </span>
                </div>
            </div>
            <button @click="sidebarOpen = false" class="lg:hidden text-gray-400 hover:text-white">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
        </div>

        <!-- Billetera de Saldo para Revendedores -->
        @if(Auth::user()->isReseller())
        <div class="m-4 p-4 rounded-xl bg-gradient-to-b from-gray-800/60 to-gray-900/90 border border-cyan-500/30 relative overflow-hidden shadow-lg">
            <div class="absolute -right-6 -bottom-6 w-20 h-20 bg-cyan-500/10 rounded-full blur-xl pointer-events-none"></div>
            <div class="flex items-center justify-between text-xs text-gray-400 font-semibold mb-2 uppercase tracking-wider">
                <span>Billetera Prepago</span>
                <i data-lucide="wallet" class="w-4 h-4 text-cyan-400"></i>
            </div>
            <div class="bg-gray-950/70 p-3 rounded-lg border border-gray-800 text-center">
                <span class="text-[11px] text-gray-400 block uppercase font-medium">Saldo Disponible</span>
                <span class="text-2xl font-black text-emerald-400">${{ number_format(Auth::user()->balance, 2) }}</span>
            </div>
            <div class="mt-2.5 pt-2 border-t border-gray-800 flex items-center justify-between text-[11px]">
                <span class="text-gray-400">Ref: <strong class="text-white">{{ Auth::user()->referral_code }}</strong></span>
                <a href="{{ route('catalog.referral', ['code' => Auth::user()->referral_code]) }}" target="_blank" class="text-cyan-400 hover:text-cyan-300 flex items-center gap-1">
                    Mi Tienda <i data-lucide="external-link" class="w-3 h-3"></i>
                </a>
            </div>
        </div>
        @endif

        <!-- Navigation Links -->
        <nav class="flex-1 px-4 py-3 space-y-1 overflow-y-auto">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('dashboard') ? 'bg-red-600 text-white shadow-lg shadow-red-600/30' : 'text-gray-400 hover:text-white hover:bg-gray-800/60' }}">
                <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
                <span>Escritorio General</span>
            </a>

            <!-- Ventas & Control de Credenciales -->
            <a href="{{ route('sales.index') }}" class="flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('sales.*') ? 'bg-red-600 text-white shadow-lg shadow-red-600/30' : 'text-gray-400 hover:text-white hover:bg-gray-800/60' }}">
                <div class="flex items-center gap-3">
                    <i data-lucide="key" class="w-5 h-5"></i>
                    <span>Ventas & Credenciales</span>
                </div>
                @php
                    $salesCount = Auth::user()->isAdmin() ? \App\Models\Sale::whereIn('status', ['active', 'expiring_soon'])->count() : Auth::user()->sales()->whereIn('status', ['active', 'expiring_soon'])->count();
                @endphp
                <span class="text-xs px-2 py-0.5 rounded-full bg-gray-800 text-emerald-400 font-bold border border-gray-700">
                    {{ $salesCount }}
                </span>
            </a>

            <!-- Inventario de Cuentas y Pantallas -->
            <a href="{{ route('accounts.index') }}" class="flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('accounts.*') ? 'bg-red-600 text-white shadow-lg shadow-red-600/30' : 'text-gray-400 hover:text-white hover:bg-gray-800/60' }}">
                <div class="flex items-center gap-3">
                    <i data-lucide="server" class="w-5 h-5"></i>
                    <span>Inventario / Stock</span>
                </div>
                @php
                    $availProfiles = \App\Models\AccountProfile::where('status', 'available')->whereHas('streamingAccount', function($q) { $q->where('status', 'active'); })->count();
                @endphp
                <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 font-bold border border-emerald-500/30">
                    {{ $availProfiles }} libres
                </span>
            </a>

            <!-- Plataformas de Streaming -->
            <a href="{{ route('services.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('services.*') ? 'bg-red-600 text-white shadow-lg shadow-red-600/30' : 'text-gray-400 hover:text-white hover:bg-gray-800/60' }}">
                <i data-lucide="tv" class="w-5 h-5"></i>
                <span>Servicios & Planes</span>
            </a>

            <!-- Clientes CRM -->
            <a href="{{ route('customers.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('customers.*') ? 'bg-red-600 text-white shadow-lg shadow-red-600/30' : 'text-gray-400 hover:text-white hover:bg-gray-800/60' }}">
                <i data-lucide="users" class="w-5 h-5"></i>
                <span>Clientes CRM</span>
            </a>

            <!-- Proveedores -->
            @if(Auth::user()->isAdmin())
            <a href="{{ route('suppliers.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('suppliers.*') ? 'bg-red-600 text-white shadow-lg shadow-red-600/30' : 'text-gray-400 hover:text-white hover:bg-gray-800/60' }}">
                <i data-lucide="truck" class="w-5 h-5"></i>
                <span>Proveedores Cuentas</span>
            </a>
            @endif

            <!-- Garantías y Caídas -->
            <a href="{{ route('warranties.index') }}" class="flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('warranties.*') ? 'bg-red-600 text-white shadow-lg shadow-red-600/30' : 'text-gray-400 hover:text-white hover:bg-gray-800/60' }}">
                <div class="flex items-center gap-3">
                    <i data-lucide="shield-alert" class="w-5 h-5"></i>
                    <span>Garantías & Caídas</span>
                </div>
                @php
                    $pendingWarranties = \App\Models\Warranty::where('status', 'pending')->count();
                @endphp
                @if($pendingWarranties > 0)
                <span class="text-xs px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-400 font-bold border border-amber-500/30">
                    {{ $pendingWarranties }}
                </span>
                @endif
            </a>

            <!-- Tesorería & Mini Nube -->
            <a href="{{ route('treasury.index') }}" class="flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('treasury.*') ? 'bg-red-600 text-white shadow-lg shadow-red-600/30' : 'text-gray-400 hover:text-white hover:bg-gray-800/60' }}">
                <div class="flex items-center gap-3">
                    <i data-lucide="credit-card" class="w-5 h-5"></i>
                    <span>Tesorería & Mini Nube</span>
                </div>
                @php
                    $pendingOrdersCount = Auth::user()->isAdmin()
                        ? \App\Models\Order::where('payment_status', 'pending')->count()
                        : \App\Models\Order::where('seller_id', Auth::id())->where('payment_status', 'pending')->count();
                @endphp
                @if($pendingOrdersCount > 0)
                <span class="text-xs px-2 py-0.5 rounded-full bg-rose-500/20 text-rose-400 border border-rose-500/40 animate-pulse font-bold">
                    {{ $pendingOrdersCount }}
                </span>
                @endif
            </a>

            <!-- Revendedores (Admin) -->
            @if(Auth::user()->isAdmin())
            <a href="{{ route('resellers.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('resellers.*') ? 'bg-red-600 text-white shadow-lg shadow-red-600/30' : 'text-gray-400 hover:text-white hover:bg-gray-800/60' }}">
                <i data-lucide="user-plus" class="w-5 h-5"></i>
                <span>Revendedores & Saldo</span>
            </a>
            @endif

            <!-- Atajos Rápidos -->
            <a href="{{ route('shortcuts.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('shortcuts.*') ? 'bg-red-600 text-white shadow-lg shadow-red-600/30' : 'text-gray-400 hover:text-white hover:bg-gray-800/60' }}">
                <i data-lucide="external-link" class="w-5 h-5"></i>
                <span>Atajos Rápidos (Dock)</span>
            </a>

            <!-- Marca Blanca -->
            @if(Auth::user()->isAdmin() || Auth::user()->reseller_plan === 'vip' || Auth::user()->reseller_plan === 'mayorista')
            <a href="{{ route('whitelabel.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('whitelabel.*') ? 'bg-red-600 text-white shadow-lg shadow-red-600/30' : 'text-gray-400 hover:text-white hover:bg-gray-800/60' }}">
                <i data-lucide="palette" class="w-5 h-5"></i>
                <span>Marca Blanca</span>
            </a>
            @endif

            <div class="pt-3 mt-3 border-t border-gray-800/80">
                <span class="px-3 text-[10px] font-bold uppercase tracking-wider text-gray-400 block mb-1.5">Tienda Digital</span>

                <a href="{{ route('catalog.index') }}" target="_blank" class="flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-medium text-emerald-400 hover:bg-emerald-500/10 transition-all">
                    <div class="flex items-center gap-3">
                        <i data-lucide="store" class="w-5 h-5"></i>
                        <span>Ver Catálogo Streaming</span>
                    </div>
                    <i data-lucide="arrow-up-right" class="w-4 h-4"></i>
                </a>
            </div>
        </nav>

        <!-- User Profile Footer -->
        <div class="p-4 border-t border-gray-800/80 bg-[#0a0f1c] flex items-center justify-between">
            <div class="flex items-center gap-3 overflow-hidden">
                <div class="w-9 h-9 rounded-full bg-red-600/20 text-red-400 border border-red-500/30 flex items-center justify-center font-bold text-sm shrink-0">
                    {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                </div>
                <div class="truncate">
                    <div class="text-sm font-semibold truncate">{{ Auth::user()->name }}</div>
                    <div class="text-xs text-gray-400 truncate">{{ Auth::user()->email }}</div>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" title="Cerrar Sesión" class="p-2 text-gray-400 hover:text-rose-400 hover:bg-rose-500/10 rounded-lg transition-colors">
                    <i data-lucide="log-out" class="w-5 h-5"></i>
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0">
        <!-- Top Navigation Bar -->
        <header class="h-20 bg-[#0e1424]/80 backdrop-blur-md border-b border-gray-800 px-6 flex items-center justify-between sticky top-0 z-30">
            <div class="flex items-center gap-4">
                <button @click="sidebarOpen = true" class="lg:hidden p-2 text-gray-400 hover:text-white rounded-lg">
                    <i data-lucide="menu" class="w-6 h-6"></i>
                </button>
                <div class="flex flex-col">
                    <h2 class="text-xl font-bold text-white tracking-tight">@yield('page_title', 'Escritorio')</h2>
                    <span class="text-xs text-gray-400">@yield('page_subtitle', 'Control y Venta de Cuentas Streaming')</span>
                </div>
            </div>

            <!-- Quick Action Dock / Shortcuts -->
            <div class="flex items-center gap-3">
                <a href="{{ route('shortcuts.index') }}" class="hidden md:flex items-center gap-2 px-3 py-1.5 rounded-lg bg-gray-800/70 border border-gray-700/60 text-xs text-gray-300 hover:text-white hover:bg-gray-700/60 transition-all">
                    <i data-lucide="zap" class="w-3.5 h-3.5 text-amber-400"></i>
                    <span>Atajos Rápidos</span>
                </a>

                <a href="{{ route('treasury.settings') }}" class="p-2.5 rounded-xl bg-gray-800/80 border border-gray-700 text-gray-300 hover:text-white hover:bg-gray-700 transition-all" title="Configurar Cobros y Pasarelas">
                    <i data-lucide="settings" class="w-4 h-4"></i>
                </a>

                <a href="{{ route('catalog.index') }}" target="_blank" class="flex items-center gap-2 px-3.5 py-1.5 rounded-xl bg-gradient-to-r from-red-600 to-rose-500 text-white font-medium text-xs hover:opacity-95 shadow-md shadow-red-600/20 transition-all">
                    <i data-lucide="shopping-bag" class="w-3.5 h-3.5"></i>
                    <span class="hidden sm:inline">Tienda Streaming</span>
                </a>
            </div>
        </header>

        <!-- Flash Messages -->
        <div class="px-6 pt-4">
            @if(session('success'))
            <div class="mb-4 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 flex items-center justify-between text-sm shadow-lg shadow-emerald-500/5">
                <div class="flex items-center gap-3">
                    <i data-lucide="check-circle" class="w-5 h-5 text-emerald-400 shrink-0"></i>
                    <span>{{ session('success') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-emerald-400/70 hover:text-emerald-300">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
            @endif

            @if(session('error'))
            <div class="mb-4 p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 flex items-center justify-between text-sm shadow-lg shadow-rose-500/5">
                <div class="flex items-center gap-3">
                    <i data-lucide="alert-triangle" class="w-5 h-5 text-rose-400 shrink-0"></i>
                    <span>{{ session('error') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-rose-400/70 hover:text-rose-300">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
            @endif

            @if($errors->any())
            <div class="mb-4 p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-sm shadow-lg">
                <div class="flex items-center gap-2 font-bold mb-1">
                    <i data-lucide="alert-circle" class="w-4 h-4"></i>
                    <span>Por favor corrige los siguientes errores:</span>
                </div>
                <ul class="list-disc list-inside space-y-1 text-xs">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
        </div>

        <!-- Main Body Content -->
        <main class="flex-1 p-6">
            @yield('content')
        </main>

        <!-- Footer -->
        <footer class="px-6 py-4 border-t border-gray-800 text-xs text-gray-400 flex flex-col sm:flex-row items-center justify-between gap-2">
            <div>
                © {{ date('Y') }} <strong>{{ Auth::user()->brand_name ?: 'CStreame Streaming Platform' }}</strong>. Todos los derechos reservados.
            </div>
            <div class="flex items-center gap-4">
                <span>Versión: <strong class="text-red-400">1.0.0 Streaming Master</strong></span>
                <span class="inline-block w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                <span class="text-emerald-400 font-semibold">Sistema Online</span>
            </div>
        </footer>
    </div>

    <!-- Initialize Lucide Icons -->
    <script>
        lucide.createIcons();
    </script>
    @stack('scripts')
</body>
</html>
