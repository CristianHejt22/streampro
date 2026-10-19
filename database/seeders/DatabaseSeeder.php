<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Service;
use App\Models\ServicePlan;
use App\Models\Supplier;
use App\Models\StreamingAccount;
use App\Models\AccountProfile;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\Order;
use App\Models\Shortcut;
use App\Models\SystemSetting;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Usuarios Principales
        // Super Administrador
        $admin = User::firstOrCreate(
            ['email' => 'admin@cstreame.com'],
            [
                'name' => 'Admin CStreame',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'status' => 'active',
                'phone' => '+54 9 11 9988-7766',
                'balance' => 9999.00,
                'reseller_plan' => 'mayorista',
                'referral_code' => 'ADMIN-STREAM',
                'brand_name' => 'CStreame Master',
                'brand_primary_color' => '#E50914',
                'brand_accent_color' => '#06B6D4',
                'support_whatsapp' => '5491199887766',
                'support_telegram' => 'cstreame_admin',
                'bank_cbu' => '0000003100012345678901',
                'bank_cvu' => '0000003100012345678902',
                'bank_alias' => 'CSTREAME.PAGOS.MP',
                'bank_holder_name' => 'CStreame Streaming SRL',
                'bank_dni' => '30-71829384-9',
                'binance_id' => '192837465',
                'bank_instructions' => 'Transferir monto exacto y subir captura legible del comprobante para entrega inmediata.',
                'mp_public_key' => 'APP_USR-CSTREAME-PUBLIC',
                'mp_access_token' => 'APP_USR-CSTREAME-ACCESS',
            ]
        );

        // Revendedor 1: Marcos Cuentas VIP
        $reseller1 = User::firstOrCreate(
            ['email' => 'marcos@revendedor.com'],
            [
                'name' => 'Marcos Cuentas VIP',
                'password' => Hash::make('reseller123'),
                'role' => 'reseller',
                'status' => 'active',
                'phone' => '+54 9 351 555-1234',
                'balance' => 150.00,
                'reseller_plan' => 'vip',
                'referral_code' => 'VIP-MARCOS',
                'brand_name' => 'Marcos Streaming VIP',
                'brand_primary_color' => '#8B5CF6',
                'brand_accent_color' => '#EC4899',
                'support_whatsapp' => '5493515551234',
                'support_telegram' => 'marcos_stream',
                'bank_alias' => 'MARCOS.CUENTAS.MP',
                'bank_cvu' => '0000007900223344556677',
                'bank_holder_name' => 'Marcos Gabriel Torres',
                'bank_dni' => '36.892.110',
                'bank_instructions' => 'Enviar comprobante por el sistema con nombre de comprador.',
            ]
        );

        // 2. Proveedores de Cuentas (Suppliers)
        $sup1 = Supplier::firstOrCreate(
            ['name' => 'Mayorista Streaming Pro Latam'],
            [
                'whatsapp' => '+54 9 11 3344-5566',
                'telegram' => '@mayoristapro_cuentas',
                'email' => 'ventas@mayoristapro.com',
                'payment_info' => 'USDT Binance Pay / Transferencia ARS Banco Galicia',
                'rating' => 5,
                'notes' => 'Proveedor principal de Netflix y Disney+. Entrega inmediata y garantía 30 días.',
                'status' => 'active',
            ]
        );

        $sup2 = Supplier::firstOrCreate(
            ['name' => 'Cuentas Global Digital Hub'],
            [
                'whatsapp' => '+57 310 555-6677',
                'telegram' => '@globalhub_digital',
                'email' => 'soporte@globaldigital.com',
                'payment_info' => 'PayPal / Binance USDT',
                'rating' => 4,
                'notes' => 'Excelente para Spotify, YouTube Premium y Max. Reposición rápida.',
                'status' => 'active',
            ]
        );

        // 3. Plataformas / Servicios de Streaming
        $netflix = Service::firstOrCreate(
            ['slug' => 'netflix'],
            [
                'name' => 'Netflix 4K UHD',
                'category' => 'video',
                'color_hex' => '#E50914',
                'max_profiles_default' => 5,
                'description' => 'Planes Ultra HD 4K con perfiles individuales y cuentas completas.',
                'default_rules' => "- Prohibido cambiar correo o contraseña de la cuenta.\n- Ingresar ÚNICAMENTE al perfil asignado con tu PIN.\n- No modificar perfiles de otros clientes.\n- Cuenta válida para 1 solo dispositivo simultáneo.",
                'status' => 'active',
            ]
        );

        $disney = Service::firstOrCreate(
            ['slug' => 'disney-plus'],
            [
                'name' => 'Disney+ Premium',
                'category' => 'video',
                'color_hex' => '#113CCF',
                'max_profiles_default' => 7,
                'description' => 'Incluye todo el catálogo de Disney, Pixar, Marvel, Star Wars y deportes de ESPN.',
                'default_rules' => "- Usar únicamente el perfil asignado.\n- Prohibido cambiar credenciales de acceso.\n- Reportar caídas de inmediato al soporte de WhatsApp.",
                'status' => 'active',
            ]
        );

        $max = Service::firstOrCreate(
            ['slug' => 'max-hbo'],
            [
                'name' => 'Max (HBO Max)',
                'category' => 'video',
                'color_hex' => '#002BE7',
                'max_profiles_default' => 5,
                'description' => 'Todo el contenido de Warner Bros, HBO, Discovery y Cartoon Network en 4K.',
                'default_rules' => "- Solo ingresar al perfil con tu PIN.\n- No compartir credenciales con terceros.",
                'status' => 'active',
            ]
        );

        $prime = Service::firstOrCreate(
            ['slug' => 'prime-video'],
            [
                'name' => 'Amazon Prime Video',
                'category' => 'video',
                'color_hex' => '#00A8E1',
                'max_profiles_default' => 6,
                'description' => 'Películas, series exclusivas de Amazon Originals y canales en vivo.',
                'default_rules' => "- No alterar perfiles ni configuración de facturación.",
                'status' => 'active',
            ]
        );

        $spotify = Service::firstOrCreate(
            ['slug' => 'spotify'],
            [
                'name' => 'Spotify Familiar',
                'category' => 'music',
                'color_hex' => '#1DB954',
                'max_profiles_default' => 6,
                'description' => 'Música sin anuncios, descargas offline y calidad de audio máxima.',
                'default_rules' => "- Ingresar al enlace de invitación con la dirección postal proporcionada.",
                'status' => 'active',
            ]
        );

        $magis = Service::firstOrCreate(
            ['slug' => 'magistv'],
            [
                'name' => 'MagisTV / IPTV Pro',
                'category' => 'iptv',
                'color_hex' => '#9333EA',
                'max_profiles_default' => 3,
                'description' => 'Más de 1200 canales en vivo, eventos deportivos y catálogo VOD completo.',
                'default_rules' => "- Prohibido usar en más pantallas de las contratadas simultáneamente.",
                'status' => 'active',
            ]
        );

        // 4. Planes de Servicios (Service Plans)
        $p1 = ServicePlan::firstOrCreate(
            ['service_id' => $netflix->id, 'name' => 'Netflix 4K - 1 Pantalla con PIN (30 días)'],
            [
                'modality' => 'profile',
                'duration_days' => 30,
                'price' => 4500.00,
                'reseller_price' => 3200.00,
                'cost_estimate' => 2000.00,
                'screens_count' => 1,
                'is_active' => true,
                'is_featured' => true,
            ]
        );

        $p2 = ServicePlan::firstOrCreate(
            ['service_id' => $netflix->id, 'name' => 'Netflix 4K - Cuenta Completa 5 Pantallas (30 días)'],
            [
                'modality' => 'full_account',
                'duration_days' => 30,
                'price' => 18000.00,
                'reseller_price' => 14000.00,
                'cost_estimate' => 10000.00,
                'screens_count' => 5,
                'is_active' => true,
                'is_featured' => false,
            ]
        );

        $p3 = ServicePlan::firstOrCreate(
            ['service_id' => $disney->id, 'name' => 'Disney+ Premium - 1 Pantalla (30 días)'],
            [
                'modality' => 'profile',
                'duration_days' => 30,
                'price' => 3500.00,
                'reseller_price' => 2500.00,
                'cost_estimate' => 1500.00,
                'screens_count' => 1,
                'is_active' => true,
                'is_featured' => true,
            ]
        );

        $p4 = ServicePlan::firstOrCreate(
            ['service_id' => $max->id, 'name' => 'Max (HBO) - 1 Pantalla con PIN (30 días)'],
            [
                'modality' => 'profile',
                'duration_days' => 30,
                'price' => 3200.00,
                'reseller_price' => 2400.00,
                'cost_estimate' => 1400.00,
                'screens_count' => 1,
                'is_active' => true,
                'is_featured' => true,
            ]
        );

        $p5 = ServicePlan::firstOrCreate(
            ['service_id' => $magis->id, 'name' => 'MagisTV - 1 Dispositivo (30 días)'],
            [
                'modality' => 'profile',
                'duration_days' => 30,
                'price' => 5000.00,
                'reseller_price' => 3800.00,
                'cost_estimate' => 2500.00,
                'screens_count' => 1,
                'is_active' => true,
                'is_featured' => false,
            ]
        );

        // 5. Inventario de Cuentas Maestras (Stock)
        // Cuenta Netflix 1 (Dividida en 5 perfiles)
        $accNet1 = StreamingAccount::firstOrCreate(
            ['account_email' => 'netstream.latam01@gmail.com'],
            [
                'service_id' => $netflix->id,
                'supplier_id' => $sup1->id,
                'account_password' => 'LatamNflx2026#',
                'recovery_email' => 'recov.netlatam@gmail.com',
                'modality' => 'profile',
                'total_profiles' => 5,
                'supplier_cost' => 10000.00,
                'supplier_expiration_date' => Carbon::now()->addDays(28),
                'status' => 'active',
                'notes' => 'Cuenta comprada a Mayorista Streaming Pro el 18/10. Excelente estabilidad.',
                'created_by' => $admin->id,
            ]
        );

        // Perfiles de la cuenta Netflix 1
        $profilesNet1 = [
            ['num' => 1, 'name' => 'Pantalla 1 - Rojo', 'pin' => '1249', 'status' => 'assigned'],
            ['num' => 2, 'name' => 'Pantalla 2 - Azul', 'pin' => '8834', 'status' => 'assigned'],
            ['num' => 3, 'name' => 'Pantalla 3 - Verde', 'pin' => '5521', 'status' => 'available'],
            ['num' => 4, 'name' => 'Pantalla 4 - Blanco', 'pin' => '9940', 'status' => 'available'],
            ['num' => 5, 'name' => 'Pantalla 5 - VIP', 'pin' => '3318', 'status' => 'available'],
        ];

        $profObj = [];
        foreach ($profilesNet1 as $p) {
            $profObj[$p['num']] = AccountProfile::updateOrCreate(
                ['streaming_account_id' => $accNet1->id, 'profile_number' => $p['num']],
                [
                    'profile_name' => $p['name'],
                    'pin' => $p['pin'],
                    'status' => $p['status'],
                ]
            );
        }

        // Cuenta Netflix 2 (Cuenta Completa disponible en stock)
        $accNetFull = StreamingAccount::firstOrCreate(
            ['account_email' => 'netflix.completa.ar01@gmail.com'],
            [
                'service_id' => $netflix->id,
                'supplier_id' => $sup1->id,
                'account_password' => 'FullMaster999$',
                'recovery_email' => 'pin.parental.4455@gmail.com',
                'modality' => 'full_account',
                'total_profiles' => 5,
                'supplier_cost' => 10000.00,
                'supplier_expiration_date' => Carbon::now()->addDays(29),
                'status' => 'active',
                'notes' => 'Cuenta completa disponible para entrega inmediata.',
                'created_by' => $admin->id,
            ]
        );

        // Cuenta Disney+ (7 perfiles)
        $accDisney1 = StreamingAccount::firstOrCreate(
            ['account_email' => 'disneyplus.vip01@outlook.com'],
            [
                'service_id' => $disney->id,
                'supplier_id' => $sup2->id,
                'account_password' => 'DsnUltra2026!',
                'recovery_email' => null,
                'modality' => 'profile',
                'total_profiles' => 7,
                'supplier_cost' => 7000.00,
                'supplier_expiration_date' => Carbon::now()->addDays(25),
                'status' => 'active',
                'notes' => 'Disney+ sin caídas.',
                'created_by' => $admin->id,
            ]
        );

        for ($i = 1; $i <= 7; $i++) {
            AccountProfile::firstOrCreate(
                ['streaming_account_id' => $accDisney1->id, 'profile_number' => $i],
                [
                    'profile_name' => 'Perfil ' . $i,
                    'pin' => str_pad((string)(1000 + $i * 123), 4, '0', STR_PAD_LEFT),
                    'status' => $i === 1 ? 'assigned' : 'available',
                ]
            );
        }

        // 6. Clientes y Ventas Históricas / Activas
        // Cliente 1: Activo vigente
        $c1 = Customer::firstOrCreate(
            ['phone' => '+5491144556677'],
            [
                'user_id' => $admin->id,
                'name' => 'Gonzalo Benítez',
                'email' => 'gonzalo.benitez@gmail.com',
                'country' => 'Argentina',
                'notes' => 'Cliente puntual. Paga siempre por transferencia.',
            ]
        );

        Sale::firstOrCreate(
            ['sale_code' => 'CST-2026-0001'],
            [
                'user_id' => $admin->id,
                'customer_id' => $c1->id,
                'service_id' => $netflix->id,
                'service_plan_id' => $p1->id,
                'streaming_account_id' => $accNet1->id,
                'account_profile_id' => $profObj[1]->id,
                'modality' => 'profile',
                'account_email_snapshot' => $accNet1->account_email,
                'account_password_snapshot' => $accNet1->account_password,
                'profile_name_snapshot' => $profObj[1]->profile_name,
                'profile_pin_snapshot' => $profObj[1]->pin,
                'usage_rules_snapshot' => $netflix->default_rules,
                'sale_price' => 4500.00,
                'cost_price' => 2000.00,
                'profit' => 2500.00,
                'start_date' => Carbon::now()->subDays(8)->toDateString(),
                'end_date' => Carbon::now()->addDays(22)->toDateString(), // Faltan 22 días (Verde)
                'status' => 'active',
                'notes' => 'Venta regular perfil 1.',
            ]
        );

        // Cliente 2: Por vencer en 2 días (Amarillo / Alerta) -> Ideal para probar recordatorio de WhatsApp
        $c2 = Customer::firstOrCreate(
            ['phone' => '+549351223344'],
            [
                'user_id' => $admin->id,
                'name' => 'María Valentina Gómez',
                'email' => 'maria.gomez@hotmail.com',
                'country' => 'Argentina',
                'notes' => 'Le gusta que le avisen 48hs antes.',
            ]
        );

        Sale::firstOrCreate(
            ['sale_code' => 'CST-2026-0002'],
            [
                'user_id' => $admin->id,
                'customer_id' => $c2->id,
                'service_id' => $netflix->id,
                'service_plan_id' => $p1->id,
                'streaming_account_id' => $accNet1->id,
                'account_profile_id' => $profObj[2]->id,
                'modality' => 'profile',
                'account_email_snapshot' => $accNet1->account_email,
                'account_password_snapshot' => $accNet1->account_password,
                'profile_name_snapshot' => $profObj[2]->profile_name,
                'profile_pin_snapshot' => $profObj[2]->pin,
                'usage_rules_snapshot' => $netflix->default_rules,
                'sale_price' => 4500.00,
                'cost_price' => 2000.00,
                'profit' => 2500.00,
                'start_date' => Carbon::now()->subDays(28)->toDateString(),
                'end_date' => Carbon::now()->addDays(2)->toDateString(), // Faltan 2 días (Alerta Vencimiento)
                'status' => 'expiring_soon',
                'notes' => 'Avisar vencimiento por WhatsApp.',
            ]
        );

        // Cliente 3: Vencido ayer (Rojo)
        $c3 = Customer::firstOrCreate(
            ['phone' => '+5491188776655'],
            [
                'user_id' => $reseller1->id,
                'name' => 'Lucas Damián Silva',
                'email' => 'lucas.silva@gmail.com',
                'country' => 'Argentina',
            ]
        );

        Sale::firstOrCreate(
            ['sale_code' => 'CST-2026-0003'],
            [
                'user_id' => $reseller1->id,
                'customer_id' => $c3->id,
                'service_id' => $disney->id,
                'service_plan_id' => $p3->id,
                'streaming_account_id' => $accDisney1->id,
                'account_profile_id' => 1,
                'modality' => 'profile',
                'account_email_snapshot' => $accDisney1->account_email,
                'account_password_snapshot' => $accDisney1->account_password,
                'profile_name_snapshot' => 'Perfil 1',
                'profile_pin_snapshot' => '1123',
                'usage_rules_snapshot' => $disney->default_rules,
                'sale_price' => 3500.00,
                'cost_price' => 1500.00,
                'profit' => 2000.00,
                'start_date' => Carbon::now()->subDays(31)->toDateString(),
                'end_date' => Carbon::now()->subDays(1)->toDateString(), // Vencido ayer
                'status' => 'expired',
                'notes' => 'Esperando confirmación de renovación.',
            ]
        );

        // 7. Mini Nube y Orden Pendiente de Auditoría
        Storage::disk('local')->makeDirectory('receipts_temp');
        $dummyReceipt = 'receipts_temp/comprobante_streaming_demo.svg';

        $svgContent = '<svg xmlns="http://www.w3.org/2000/svg" width="600" height="800" viewBox="0 0 600 800">
            <rect width="600" height="800" fill="#0f172a"/>
            <rect x="30" y="30" width="540" height="740" rx="16" fill="#1e293b" stroke="#334155" stroke-width="2"/>
            <circle cx="300" cy="110" r="40" fill="#22c55e"/>
            <path d="M285 110 l10 10 l25 -25" fill="none" stroke="#ffffff" stroke-width="5" stroke-linecap="round"/>
            <text x="300" y="180" font-family="Arial, sans-serif" font-size="22" font-weight="bold" fill="#f8fafc" text-anchor="middle">¡Transferencia Recibida!</text>
            <text x="300" y="215" font-family="Arial, sans-serif" font-size="16" fill="#94a3b8" text-anchor="middle">Comprobante de Cuenta Streaming</text>
            <line x1="60" y1="245" x2="540" y2="245" stroke="#334155" stroke-width="1"/>
            <text x="70" y="290" font-family="Arial, sans-serif" font-size="14" fill="#94a3b8">Plan Solicitado:</text>
            <text x="530" y="290" font-family="Arial, sans-serif" font-size="18" font-weight="bold" fill="#38bdf8" text-anchor="end">Netflix 4K - 1 Pantalla PIN</text>
            <text x="70" y="340" font-family="Arial, sans-serif" font-size="14" fill="#94a3b8">Importe Total:</text>
            <text x="530" y="340" font-family="Arial, sans-serif" font-size="22" font-weight="bold" fill="#22c55e" text-anchor="end">$ 4.500,00 ARS</text>
            <text x="70" y="390" font-family="Arial, sans-serif" font-size="14" fill="#94a3b8">Comprador:</text>
            <text x="530" y="390" font-family="Arial, sans-serif" font-size="15" font-weight="bold" fill="#f8fafc" text-anchor="end">Nicolás Morales</text>
            <text x="70" y="430" font-family="Arial, sans-serif" font-size="14" fill="#94a3b8">WhatsApp:</text>
            <text x="530" y="430" font-family="Arial, sans-serif" font-size="14" fill="#cbd5e1" text-anchor="end">+54 9 11 6543-2109</text>
            <rect x="60" y="500" width="480" height="190" rx="12" fill="#0f172a" stroke="#475569" stroke-width="1"/>
            <text x="300" y="540" font-family="Arial, sans-serif" font-size="13" font-weight="bold" fill="#f59e0b" text-anchor="middle">MINI NUBE TEMPORAL CSTREAME</text>
            <text x="300" y="575" font-family="Arial, sans-serif" font-size="12" fill="#cbd5e1" text-anchor="middle">Este archivo se almacena temporalmente para auditoría visual.</text>
            <text x="300" y="605" font-family="Arial, sans-serif" font-size="12" fill="#ef4444" font-weight="bold" text-anchor="middle">Al presionar "Aprobar y Entregar Credenciales", este comprobante</text>
            <text x="300" y="630" font-family="Arial, sans-serif" font-size="12" fill="#ef4444" font-weight="bold" text-anchor="middle">SE ELIMINA PERMANENTEMENTE para ahorrar espacio en cPanel.</text>
            <text x="300" y="660" font-family="Arial, sans-serif" font-size="12" fill="#94a3b8" text-anchor="middle">Fecha de subida: ' . date('d/m/Y H:i') . ' hs</text>
        </svg>';

        Storage::disk('local')->put($dummyReceipt, $svgContent);

        Order::firstOrCreate(
            ['order_code' => 'ORD-STR-9941'],
            [
                'seller_id' => $admin->id,
                'service_plan_id' => $p1->id,
                'customer_name' => 'Nicolás Morales',
                'customer_phone' => '+54 9 11 6543-2109',
                'customer_email' => 'nicolas.morales@gmail.com',
                'amount' => 4500.00,
                'payment_method' => 'bank_transfer',
                'payment_status' => 'pending',
                'receipt_path' => $dummyReceipt,
                'notes' => 'El cliente subió el comprobante desde el catálogo público. Listo para auditar.',
            ]
        );

        // 8. Atajos del Dock de Productividad
        $shortcuts = [
            [
                'title' => 'Netflix Hogar / Códigos',
                'url' => 'https://www.netflix.com/youraccount',
                'icon' => 'tv',
                'category' => 'Streaming',
                'color' => '#E50914',
                'badge' => 'Netflix',
                'order_index' => 1,
            ],
            [
                'title' => 'Disney+ Login',
                'url' => 'https://www.disneyplus.com/login',
                'icon' => 'play-circle',
                'category' => 'Streaming',
                'color' => '#113CCF',
                'badge' => 'Disney',
                'order_index' => 2,
            ],
            [
                'title' => 'WhatsApp Web',
                'url' => 'https://web.whatsapp.com',
                'icon' => 'message-circle',
                'category' => 'Mensajería',
                'color' => '#22C55E',
                'badge' => 'Ventas',
                'order_index' => 3,
            ],
            [
                'title' => 'Canal Proveedores Telegram',
                'url' => 'https://t.me/cstreame_canal',
                'icon' => 'send',
                'category' => 'Proveedores',
                'color' => '#0EA5E9',
                'badge' => 'Stock',
                'order_index' => 4,
            ],
            [
                'title' => 'Test de Velocidad',
                'url' => 'https://fast.com',
                'icon' => 'gauge',
                'category' => 'Herramientas',
                'color' => '#F59E0B',
                'badge' => 'Mbps',
                'order_index' => 5,
            ],
        ];

        foreach ($shortcuts as $sc) {
            Shortcut::firstOrCreate(
                ['title' => $sc['title']],
                array_merge($sc, ['user_id' => null])
            );
        }

        // 9. Configuraciones del Sistema
        SystemSetting::set('site_name', 'CStreame - Control de Cuentas Streaming');
        SystemSetting::set('currency_symbol', '$');
        SystemSetting::set('system_version', '1.0.0');
        SystemSetting::set('support_whatsapp', '+5491199887766');
        SystemSetting::set('installed', '1');

        // 10. Auditoría Inicial
        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'system_install',
            'description' => 'Plataforma CStreame inicializada con éxito. Catálogo de streaming, inventario y cuentas cargadas.',
            'ip_address' => '127.0.0.1',
        ]);
    }
}
