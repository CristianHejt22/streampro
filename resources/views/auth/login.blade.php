<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión | {{ $brandName ?? 'CStreame Streaming' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-[#0b0f19] text-gray-100 min-h-screen flex items-center justify-center p-4 relative overflow-hidden font-sans">

    <!-- Ambient Glow Effects -->
    <div class="absolute top-1/4 -left-20 w-96 h-96 bg-red-600/15 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute bottom-1/4 -right-20 w-96 h-96 bg-purple-600/15 rounded-full blur-3xl pointer-events-none"></div>

    <div class="w-full max-w-md relative z-10">
        <!-- Logo & Title -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-tr from-red-600 via-rose-500 to-amber-500 text-white text-3xl font-black shadow-xl shadow-red-600/25 mb-4">
                🍿
            </div>
            <h1 class="text-3xl font-black tracking-tight bg-gradient-to-r from-white via-gray-100 to-red-300 bg-clip-text text-transparent">
                {{ $brandName ?? 'CStreame Master' }}
            </h1>
            <p class="text-sm text-gray-400 mt-1">Control y Venta de Cuentas Streaming</p>
        </div>

        <!-- Quick 1-Click Demo Logins -->
        <div class="mb-6 bg-[#111827] border border-gray-800 rounded-2xl p-4 backdrop-blur-md">
            <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block mb-2 text-center">
                Acceso Rápido de Prueba (1 Clic)
            </span>
            <div class="grid grid-cols-2 gap-2">
                <button type="button" onclick="fillLogin('admin@cstreame.com', 'admin123')" class="px-3 py-2 rounded-xl bg-red-600/20 hover:bg-red-600/30 border border-red-500/30 text-red-300 text-xs font-bold text-center transition-all hover:scale-[1.02]">
                    👑 Administrador
                </button>
                <button type="button" onclick="fillLogin('marcos@revendedor.com', 'reseller123')" class="px-3 py-2 rounded-xl bg-cyan-600/20 hover:bg-cyan-600/30 border border-cyan-500/30 text-cyan-300 text-xs font-bold text-center transition-all hover:scale-[1.02]">
                    💼 Revendedor Marcos
                </button>
            </div>
        </div>

        <!-- Login Form Card -->
        <div class="bg-[#111827] border border-gray-800 rounded-3xl p-8 shadow-2xl">
            @if(session('success'))
                <div class="mb-6 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-6 p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}" class="space-y-5">
                @csrf

                <div>
                    <label for="email" class="block text-xs font-semibold text-gray-300 uppercase tracking-wider mb-2">
                        Correo Electrónico
                    </label>
                    <div class="relative">
                        <i data-lucide="mail" class="w-5 h-5 text-gray-500 absolute left-3.5 top-3.5"></i>
                        <input type="email" id="email" name="email" value="{{ old('email', 'admin@cstreame.com') }}" required autofocus
                            class="w-full pl-11 pr-4 py-3 bg-[#0b0f19] border border-gray-800 rounded-xl text-white placeholder-gray-500 text-sm focus:outline-none focus:border-red-500 transition-all font-mono">
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-xs font-semibold text-gray-300 uppercase tracking-wider mb-2">
                        Contraseña
                    </label>
                    <div class="relative">
                        <i data-lucide="lock" class="w-5 h-5 text-gray-500 absolute left-3.5 top-3.5"></i>
                        <input type="password" id="password" name="password" value="admin123" required
                            class="w-full pl-11 pr-4 py-3 bg-[#0b0f19] border border-gray-800 rounded-xl text-white placeholder-gray-500 text-sm focus:outline-none focus:border-red-500 transition-all font-mono">
                    </div>
                </div>

                <div class="flex items-center justify-between text-xs text-gray-400">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="remember" class="rounded bg-[#0b0f19] border-gray-700 text-red-600 focus:ring-red-500">
                        <span>Recordar sesión</span>
                    </label>
                    <a href="{{ route('catalog.index') }}" class="text-red-400 hover:text-red-300">
                        Ver Catálogo Web
                    </a>
                </div>

                <button type="submit" class="w-full py-3.5 px-4 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold text-sm shadow-xl shadow-red-600/30 flex items-center justify-center gap-2 transition-all">
                    <span>Ingresar al Panel</span>
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </button>
            </form>
        </div>
    </div>

    <script>
        function fillLogin(email, pass) {
            document.getElementById('email').value = email;
            document.getElementById('password').value = pass;
        }
        lucide.createIcons();
    </script>
</body>
</html>
