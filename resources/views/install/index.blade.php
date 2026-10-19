<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalador Web | StreamPro Master</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-[#070b14] text-gray-100 min-h-screen flex items-center justify-center p-4 antialiased font-sans">

    <div class="w-full max-w-2xl bg-gray-900 border border-gray-800 rounded-3xl p-6 sm:p-10 shadow-2xl space-y-8"
        x-data="{
            step: 1,
            dbTesting: false,
            dbStatus: null,
            licenseTesting: false,
            licenseStatus: null,
            testDb() {
                this.dbTesting = true;
                this.dbStatus = null;
                fetch('{{ route('install.test_db') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        db_host: document.getElementById('db_host').value,
                        db_port: document.getElementById('db_port').value,
                        db_database: document.getElementById('db_database').value,
                        db_username: document.getElementById('db_username').value,
                        db_password: document.getElementById('db_password').value
                    })
                })
                .then(res => res.json())
                .then(data => {
                    this.dbTesting = false;
                    this.dbStatus = data;
                })
                .catch(err => {
                    this.dbTesting = false;
                    this.dbStatus = { success: false, message: 'Error de conexión.' };
                });
            },
            verifyLic() {
                this.licenseTesting = true;
                this.licenseStatus = null;
                fetch('{{ route('install.verify_license') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        license_key: document.getElementById('license_key').value
                    })
                })
                .then(res => res.json())
                .then(data => {
                    this.licenseTesting = false;
                    this.licenseStatus = data;
                })
                .catch(err => {
                    this.licenseTesting = false;
                    this.licenseStatus = { valid: false, message: 'Licencia no válida.' };
                });
            }
        }">

        <!-- Header -->
        <div class="text-center">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-indigo-600 to-cyan-400 mx-auto flex items-center justify-center text-white text-2xl font-black shadow-lg shadow-indigo-500/25 mb-3">
                ⚡
            </div>
            <h1 class="text-2xl font-black text-white">Asistente de Instalación en Hosting</h1>
            <p class="text-xs text-gray-400 mt-1">Configuración rápida optimizada para cPanel / Hostinger (PHP 8.2 + MySQL)</p>
        </div>

        <!-- Stepper Indicator -->
        <div class="flex items-center justify-between border-b border-gray-800 pb-4 text-xs font-bold">
            <button @click="step = 1" :class="step === 1 ? 'text-indigo-400 border-b-2 border-indigo-400 pb-2' : 'text-gray-500'">1. Requisitos</button>
            <button @click="step = 2" :class="step === 2 ? 'text-indigo-400 border-b-2 border-indigo-400 pb-2' : 'text-gray-500'">2. Base de Datos</button>
            <button @click="step = 3" :class="step === 3 ? 'text-indigo-400 border-b-2 border-indigo-400 pb-2' : 'text-gray-500'">3. Licencia Anti-Clon</button>
            <button @click="step = 4" :class="step === 4 ? 'text-indigo-400 border-b-2 border-indigo-400 pb-2' : 'text-gray-500'">4. Super Admin</button>
        </div>

        <form method="POST" action="{{ route('install.process') }}">
            @csrf

            <!-- STEP 1: Requisitos del Servidor -->
            <div x-show="step === 1" class="space-y-4">
                <h3 class="text-sm font-bold text-white mb-2">Comprobación de Extensiones y Servidor</h3>
                <div class="space-y-2 bg-gray-950 p-4 rounded-2xl border border-gray-800">
                    @foreach($requirements as $label => $passed)
                    <div class="flex items-center justify-between text-xs py-1">
                        <span class="text-gray-300">{{ $label }}</span>
                        @if($passed)
                            <span class="px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-400 font-bold flex items-center gap-1">
                                <i data-lucide="check" class="w-3.5 h-3.5"></i> OK
                            </span>
                        @else
                            <span class="px-2 py-0.5 rounded bg-rose-500/20 text-rose-400 font-bold flex items-center gap-1">
                                <i data-lucide="x" class="w-3.5 h-3.5"></i> Falta
                            </span>
                        @endif
                    </div>
                    @endforeach
                </div>

                <div class="pt-4 flex justify-end">
                    <button type="button" @click="step = 2" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs flex items-center gap-2">
                        <span>Continuar a Base de Datos</span>
                        <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>

            <!-- STEP 2: Conexión MySQL -->
            <div x-show="step === 2" x-cloak class="space-y-4">
                <h3 class="text-sm font-bold text-white mb-2">Credenciales de Base de Datos MySQL</h3>
                <div class="grid grid-cols-2 gap-3 text-xs">
                    <div>
                        <label class="block text-gray-400 font-semibold mb-1">Host MySQL</label>
                        <input type="text" id="db_host" value="127.0.0.1" class="w-full px-3 py-2 bg-gray-950 border border-gray-800 rounded-xl text-white">
                    </div>
                    <div>
                        <label class="block text-gray-400 font-semibold mb-1">Puerto</label>
                        <input type="text" id="db_port" value="3306" class="w-full px-3 py-2 bg-gray-950 border border-gray-800 rounded-xl text-white">
                    </div>
                    <div>
                        <label class="block text-gray-400 font-semibold mb-1">Nombre Base de Datos</label>
                        <input type="text" id="db_database" value="streameshop" class="w-full px-3 py-2 bg-gray-950 border border-gray-800 rounded-xl text-white">
                    </div>
                    <div>
                        <label class="block text-gray-400 font-semibold mb-1">Usuario MySQL</label>
                        <input type="text" id="db_username" value="root" class="w-full px-3 py-2 bg-gray-950 border border-gray-800 rounded-xl text-white">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-gray-400 font-semibold mb-1">Contraseña MySQL</label>
                        <input type="password" id="db_password" value="" placeholder="En blanco si es XAMPP local" class="w-full px-3 py-2 bg-gray-950 border border-gray-800 rounded-xl text-white">
                    </div>
                </div>

                <div class="flex items-center justify-between pt-2">
                    <button type="button" @click="testDb()" class="px-3.5 py-2 rounded-xl bg-gray-800 hover:bg-gray-700 text-xs font-bold text-cyan-400 flex items-center gap-1.5 border border-gray-700">
                        <span x-show="!dbTesting">Probar Conexión en Vivo</span>
                        <span x-show="dbTesting">Comprobando...</span>
                    </button>

                    <button type="button" @click="step = 3" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs flex items-center gap-2">
                        <span>Paso Siguiente</span>
                        <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    </button>
                </div>

                <div x-show="dbStatus" class="p-3 rounded-xl text-xs font-medium" :class="dbStatus?.success ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20'" x-text="dbStatus?.message"></div>
            </div>

            <!-- STEP 3: Licencia Anti-Clonación -->
            <div x-show="step === 3" x-cloak class="space-y-4">
                <h3 class="text-sm font-bold text-white mb-2">Clave de Licencia del Script (Anti-Piratería)</h3>
                <p class="text-xs text-gray-400">Introduce la clave de licencia provista con tu compra para vincular tu dominio.</p>

                <div>
                    <label class="block text-xs font-semibold text-gray-300 uppercase tracking-wider mb-2">Clave de Activación Oficial</label>
                    <input type="text" id="license_key" name="license_key" value="MYSP-STREAMPRO-7729-A92B-2026" required
                        class="w-full px-4 py-3 bg-gray-950 border border-gray-800 rounded-xl text-cyan-300 font-mono text-sm focus:ring-2 focus:ring-indigo-500">
                </div>

                <div class="flex items-center justify-between pt-2">
                    <button type="button" @click="verifyLic()" class="px-3.5 py-2 rounded-xl bg-gray-800 hover:bg-gray-700 text-xs font-bold text-emerald-400 flex items-center gap-1.5 border border-gray-700">
                        <span x-show="!licenseTesting">Verificar con Servidor Maestro</span>
                        <span x-show="licenseTesting">Verificando...</span>
                    </button>

                    <button type="button" @click="step = 4" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs flex items-center gap-2">
                        <span>Paso Siguiente</span>
                        <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    </button>
                </div>

                <div x-show="licenseStatus" class="p-3 rounded-xl text-xs font-medium" :class="licenseStatus?.valid ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20'">
                    <span x-show="licenseStatus?.valid" x-text="'✓ Licencia válida: ' + licenseStatus?.tier + ' (' + licenseStatus?.status + ')'"></span>
                    <span x-show="!licenseStatus?.valid" x-text="licenseStatus?.message"></span>
                </div>
            </div>

            <!-- STEP 4: Crear Super Admin -->
            <div x-show="step === 4" x-cloak class="space-y-4">
                <h3 class="text-sm font-bold text-white mb-2">Crear Cuenta de Super Administrador</h3>

                <div class="space-y-3 text-xs">
                    <div>
                        <label class="block text-gray-400 font-semibold mb-1">Nombre Completo</label>
                        <input type="text" name="admin_name" value="Super Admin Master" required class="w-full px-3 py-2 bg-gray-950 border border-gray-800 rounded-xl text-white">
                    </div>
                    <div>
                        <label class="block text-gray-400 font-semibold mb-1">Email de Acceso</label>
                        <input type="email" name="admin_email" value="admin@streampro.com" required class="w-full px-3 py-2 bg-gray-950 border border-gray-800 rounded-xl text-white">
                    </div>
                    <div>
                        <label class="block text-gray-400 font-semibold mb-1">Contraseña</label>
                        <input type="password" name="admin_password" value="admin123456" required class="w-full px-3 py-2 bg-gray-950 border border-gray-800 rounded-xl text-white">
                    </div>
                    <div>
                        <label class="block text-gray-400 font-semibold mb-1">Nombre de la Plataforma / Marca</label>
                        <input type="text" name="brand_name" value="StreamPro Master" required class="w-full px-3 py-2 bg-gray-950 border border-gray-800 rounded-xl text-white">
                    </div>
                </div>

                <div class="pt-4 flex justify-end">
                    <button type="submit" class="w-full py-3.5 rounded-xl bg-gradient-to-r from-emerald-500 to-cyan-500 hover:opacity-95 text-gray-950 font-black text-sm shadow-xl shadow-emerald-500/25 flex items-center justify-center gap-2 transition-all">
                        <i data-lucide="check-circle" class="w-5 h-5"></i>
                        <span>Instalar y Configurar Sistema Ahora</span>
                    </button>
                </div>
            </div>

        </form>

    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
