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
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Usuarios Principales (Admin, Super Reseller y Reseller)
        // Super Administrador
        $admin = User::firstOrCreate(
            ['email' => 'admin@cstreame.com'],
            [
                'name' => 'Admin CStreame',
                'password' => Hash::make('admin123'),
                'plain_password' => 'admin123',
                'anti_phishing_code' => 'CST-SEC-ADMIN-99',
                'panel_type' => 'master',
                'reseller_commission_pct' => 40.00,
                'parent_commission_pct' => 25.00,
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

        // Super Revendedor: Carlos Super Reseller
        $superReseller = User::firstOrCreate(
            ['email' => 'super@revendedor.com'],
            [
                'name' => 'Carlos Super Reseller',
                'password' => Hash::make('super123'),
                'plain_password' => 'super123',
                'anti_phishing_code' => 'CARLOS-SAFE-77',
                'panel_type' => 'master',
                'reseller_commission_pct' => 40.00,
                'parent_commission_pct' => 25.00,
                'role' => 'super_reseller',
                'status' => 'active',
                'phone' => '+54 9 11 8877-6655',
                'balance' => 500.00,
                'reseller_plan' => 'mayorista',
                'referral_code' => 'SUPER-CARLOS',
                'brand_name' => 'Carlos Streaming Global',
                'brand_primary_color' => '#0284C7',
                'brand_accent_color' => '#38BDF8',
                'support_whatsapp' => '5491188776655',
                'support_telegram' => 'carlos_super_stream',
                'bank_alias' => 'CARLOS.STREAM.MP',
                'bank_cvu' => '0000005511223344556677',
                'bank_holder_name' => 'Carlos Alberto Méndez',
                'bank_dni' => '33.456.789',
                'bank_instructions' => 'Transferir a Mercado Pago y avisar por WhatsApp con comprobante.',
            ]
        );

        // Revendedor 1: Marcos Cuentas VIP
        $reseller1 = User::firstOrCreate(
            ['email' => 'marcos@revendedor.com'],
            [
                'name' => 'Marcos Cuentas VIP',
                'password' => Hash::make('reseller123'),
                'plain_password' => 'reseller123',
                'anti_phishing_code' => 'MARCOS-VIP-SEC',
                'panel_type' => 'cloned',
                'panel_monthly_fee' => 15000.00,
                'panel_expires_at' => Carbon::now()->addDays(30),
                'reseller_commission_pct' => 40.00,
                'parent_commission_pct' => 25.00,
                'role' => 'reseller',
                'parent_id' => $superReseller->id,
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
                'notes' => 'Proveedor principal de Netflix, HBO y Disney. Entrega inmediata y garantía 30 días.',
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
                'notes' => 'Cuentas Disney Premium y Max Platino con soporte 24/7.',
                'status' => 'active',
            ]
        );

        // 3. Plataformas / Servicios de Streaming Solicitados
        // 1. NETFLIX
        $netflix = Service::firstOrCreate(
            ['slug' => 'netflix'],
            [
                'name' => 'NETFLIX 4K UHD',
                'category' => 'video',
                'color_hex' => '#E50914',
                'max_profiles_default' => 5,
                'description' => 'Planes Ultra HD 4K con perfiles individuales con PIN y cuentas completas.',
                'default_rules' => "- Prohibido cambiar correo o contraseña de la cuenta.\n- Ingresar ÚNICAMENTE al perfil asignado con tu PIN.\n- No modificar perfiles de otros clientes.\n- Cuenta válida para 1 solo dispositivo simultáneo.",
                'status' => 'active',
            ]
        );

        // 2. HBO (MAX)
        $hbo = Service::firstOrCreate(
            ['slug' => 'max-hbo'],
            [
                'name' => 'HBO (MAX Platino)',
                'category' => 'video',
                'color_hex' => '#002BE7',
                'max_profiles_default' => 5,
                'description' => 'Todo el contenido de Warner Bros, HBO, Discovery y Cartoon Network en 4K.',
                'default_rules' => "- Ingresar únicamente a tu perfil con PIN.\n- No compartir credenciales con terceros.\n- Prohibido cambiar la contraseña de acceso.",
                'status' => 'active',
            ]
        );

        // 3. DISNEY STANDAR
        $disneyStd = Service::firstOrCreate(
            ['slug' => 'disney-standard'],
            [
                'name' => 'DISNEY+ ESTÁNDAR',
                'category' => 'video',
                'color_hex' => '#0C2D80',
                'max_profiles_default' => 4,
                'description' => 'Películas y series familiares en calidad Full HD 1080p con perfiles protegidos.',
                'default_rules' => "- Usar únicamente el perfil asignado.\n- Prohibido cambiar datos de cuenta.\n- Válido para 1 pantalla activa.",
                'status' => 'active',
            ]
        );

        // 4. DISNEY PREMIUM
        $disneyPrem = Service::firstOrCreate(
            ['slug' => 'disney-premium'],
            [
                'name' => 'DISNEY+ PREMIUM',
                'category' => 'video',
                'color_hex' => '#113CCF',
                'max_profiles_default' => 7,
                'description' => 'Catálogo completo 4K UHD con deportes en vivo de ESPN y producciones de Star+.',
                'default_rules' => "- Usar únicamente el perfil asignado con PIN.\n- No modificar perfiles de otros usuarios.\n- Prohibido alterar correo o clave.",
                'status' => 'active',
            ]
        );

        // 4. Planes de Suscripción para cada Plataforma
        // NETFLIX
        $pNet1 = ServicePlan::firstOrCreate(
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
        $pNetFull = ServicePlan::firstOrCreate(
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

        // HBO (MAX)
        $pHbo1 = ServicePlan::firstOrCreate(
            ['service_id' => $hbo->id, 'name' => 'HBO Max - 1 Pantalla con PIN (30 días)'],
            [
                'modality' => 'profile',
                'duration_days' => 30,
                'price' => 3500.00,
                'reseller_price' => 2400.00,
                'cost_estimate' => 1400.00,
                'screens_count' => 1,
                'is_active' => true,
                'is_featured' => true,
            ]
        );

        // DISNEY ESTÁNDAR
        $pDsnStd1 = ServicePlan::firstOrCreate(
            ['service_id' => $disneyStd->id, 'name' => 'Disney+ Estándar - 1 Pantalla (30 días)'],
            [
                'modality' => 'profile',
                'duration_days' => 30,
                'price' => 3200.00,
                'reseller_price' => 2200.00,
                'cost_estimate' => 1300.00,
                'screens_count' => 1,
                'is_active' => true,
                'is_featured' => true,
            ]
        );

        // DISNEY PREMIUM
        $pDsnPrem1 = ServicePlan::firstOrCreate(
            ['service_id' => $disneyPrem->id, 'name' => 'Disney+ Premium (Star & ESPN) - 1 Pantalla con PIN (30 días)'],
            [
                'modality' => 'profile',
                'duration_days' => 30,
                'price' => 4200.00,
                'reseller_price' => 2900.00,
                'cost_estimate' => 1800.00,
                'screens_count' => 1,
                'is_active' => true,
                'is_featured' => true,
            ]
        );

        // 5. Cuentas Maestras de Inventario (Stock Inicial)
        // NETFLIX
        $accNet = StreamingAccount::firstOrCreate(
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
                'notes' => 'Cuenta comprada a Mayorista Streaming Pro. Excelente estabilidad.',
                'created_by' => $admin->id,
            ]
        );

        $netProfiles = [
            ['num' => 1, 'name' => 'Pantalla 1 - Rojo', 'pin' => '1249', 'status' => 'assigned'],
            ['num' => 2, 'name' => 'Pantalla 2 - Azul', 'pin' => '8834', 'status' => 'assigned'],
            ['num' => 3, 'name' => 'Pantalla 3 - Verde', 'pin' => '5521', 'status' => 'available'],
            ['num' => 4, 'name' => 'Pantalla 4 - Blanco', 'pin' => '9940', 'status' => 'available'],
            ['num' => 5, 'name' => 'Pantalla 5 - VIP', 'pin' => '3318', 'status' => 'available'],
        ];

        $netProfObjs = [];
        foreach ($netProfiles as $p) {
            $netProfObjs[$p['num']] = AccountProfile::updateOrCreate(
                ['streaming_account_id' => $accNet->id, 'profile_number' => $p['num']],
                [
                    'profile_name' => $p['name'],
                    'pin' => $p['pin'],
                    'status' => $p['status'],
                ]
            );
        }

        // HBO (MAX)
        $accHbo = StreamingAccount::firstOrCreate(
            ['account_email' => 'hbomax.latam01@gmail.com'],
            [
                'service_id' => $hbo->id,
                'supplier_id' => $sup1->id,
                'account_password' => 'MaxPlatino2026#',
                'recovery_email' => 'recov.hbo@gmail.com',
                'modality' => 'profile',
                'total_profiles' => 5,
                'supplier_cost' => 8500.00,
                'supplier_expiration_date' => Carbon::now()->addDays(29),
                'status' => 'active',
                'notes' => 'Max Platino 4K activo.',
                'created_by' => $admin->id,
            ]
        );

        $hboProfiles = [
            ['num' => 1, 'name' => 'Pantalla 1 - HBO', 'pin' => '4190', 'status' => 'assigned'],
            ['num' => 2, 'name' => 'Pantalla 2 - Cine', 'pin' => '6720', 'status' => 'available'],
            ['num' => 3, 'name' => 'Pantalla 3 - Max', 'pin' => '1904', 'status' => 'available'],
            ['num' => 4, 'name' => 'Pantalla 4 - Plus', 'pin' => '5219', 'status' => 'available'],
            ['num' => 5, 'name' => 'Pantalla 5 - Gold', 'pin' => '8811', 'status' => 'available'],
        ];

        $hboProfObjs = [];
        foreach ($hboProfiles as $p) {
            $hboProfObjs[$p['num']] = AccountProfile::updateOrCreate(
                ['streaming_account_id' => $accHbo->id, 'profile_number' => $p['num']],
                [
                    'profile_name' => $p['name'],
                    'pin' => $p['pin'],
                    'status' => $p['status'],
                ]
            );
        }

        // DISNEY ESTÁNDAR
        $accDsnStd = StreamingAccount::firstOrCreate(
            ['account_email' => 'disney.std01@gmail.com'],
            [
                'service_id' => $disneyStd->id,
                'supplier_id' => $sup2->id,
                'account_password' => 'DisneyStd2026*',
                'recovery_email' => null,
                'modality' => 'profile',
                'total_profiles' => 4,
                'supplier_cost' => 6000.00,
                'supplier_expiration_date' => Carbon::now()->addDays(26),
                'status' => 'active',
                'notes' => 'Disney Estándar 4 pantallas.',
                'created_by' => $admin->id,
            ]
        );

        for ($i = 1; $i <= 4; $i++) {
            AccountProfile::updateOrCreate(
                ['streaming_account_id' => $accDsnStd->id, 'profile_number' => $i],
                [
                    'profile_name' => 'Pantalla ' . $i,
                    'pin' => str_pad((string)(2000 + $i * 111), 4, '0', STR_PAD_LEFT),
                    'status' => 'available',
                ]
            );
        }

        // DISNEY PREMIUM
        $accDsnPrem = StreamingAccount::firstOrCreate(
            ['account_email' => 'disney.prem01@gmail.com'],
            [
                'service_id' => $disneyPrem->id,
                'supplier_id' => $sup2->id,
                'account_password' => 'DisneyPrem2026!',
                'recovery_email' => null,
                'modality' => 'profile',
                'total_profiles' => 7,
                'supplier_cost' => 9000.00,
                'supplier_expiration_date' => Carbon::now()->addDays(27),
                'status' => 'active',
                'notes' => 'Disney Premium con ESPN y Star+.',
                'created_by' => $admin->id,
            ]
        );

        $dsnPremProfObjs = [];
        for ($i = 1; $i <= 7; $i++) {
            $dsnPremProfObjs[$i] = AccountProfile::updateOrCreate(
                ['streaming_account_id' => $accDsnPrem->id, 'profile_number' => $i],
                [
                    'profile_name' => 'Perfil VIP ' . $i,
                    'pin' => str_pad((string)(8000 + $i * 12), 4, '0', STR_PAD_LEFT),
                    'status' => $i === 1 ? 'assigned' : 'available',
                ]
            );
        }

        // 6. Clientes Registrados con Correo y Contraseña
        // Cliente 1: Gonzalo Benítez (Registrado bajo Admin)
        $c1 = Customer::updateOrCreate(
            ['email' => 'cliente@cstreame.com'],
            [
                'user_id' => $admin->id,
                'customer_code' => 'CLI-1001',
                'name' => 'Gonzalo Benítez',
                'phone' => '+54 9 11 9988-1122',
                'password' => Hash::make('cliente123'),
                'security_pin' => '1234',
                'anti_phishing_phrase' => 'Halcon Dorado 2026',
                'status' => 'active',
                'country' => 'Argentina',
                'notes' => 'Cliente regular con cuenta activa en Netflix y Disney Premium.',
            ]
        );

        // Cliente 2: María Gómez (Registrada bajo Super Reseller Carlos)
        $c2 = Customer::updateOrCreate(
            ['email' => 'maria.gomez@gmail.com'],
            [
                'user_id' => $superReseller->id,
                'customer_code' => 'CLI-1002',
                'name' => 'María Gómez',
                'phone' => '+54 9 11 4433-2211',
                'password' => Hash::make('cliente123'),
                'security_pin' => '5678',
                'anti_phishing_phrase' => 'Rio Azul Seguro',
                'status' => 'active',
                'country' => 'Argentina',
                'notes' => 'Cliente registrada desde el enlace referenciado de Carlos Super Reseller.',
            ]
        );

        // Cliente 3: Lucas Romero (Registrado bajo Revendedor Marcos)
        $c3 = Customer::updateOrCreate(
            ['email' => 'lucas.romero@hotmail.com'],
            [
                'user_id' => $reseller1->id,
                'customer_code' => 'CLI-1003',
                'name' => 'Lucas Romero',
                'phone' => '+54 9 351 7766554',
                'password' => Hash::make('cliente123'),
                'security_pin' => '9999',
                'anti_phishing_phrase' => 'Estrella Polar VIP',
                'status' => 'active',
                'country' => 'Argentina',
                'notes' => 'Cliente de Marcos VIP.',
            ]
        );

        // 7. Ventas / Activaciones Activas
        // Venta 1: Netflix Pantalla 1 para Gonzalo
        Sale::firstOrCreate(
            ['sale_code' => 'CST-2026-0001'],
            [
                'user_id' => $admin->id,
                'customer_id' => $c1->id,
                'service_id' => $netflix->id,
                'service_plan_id' => $pNet1->id,
                'streaming_account_id' => $accNet->id,
                'account_profile_id' => $netProfObjs[1]->id,
                'modality' => 'profile',
                'account_email_snapshot' => $accNet->account_email,
                'account_password_snapshot' => $accNet->account_password,
                'profile_name_snapshot' => $netProfObjs[1]->profile_name,
                'profile_pin_snapshot' => $netProfObjs[1]->pin,
                'usage_rules_snapshot' => $netflix->default_rules,
                'sale_price' => 4500.00,
                'cost_price' => 2000.00,
                'profit' => 2500.00,
                'start_date' => Carbon::now()->subDays(6)->toDateString(),
                'end_date' => Carbon::now()->addDays(24)->toDateString(),
                'status' => 'active',
                'notes' => 'Activación oficial por correo y contraseña.',
            ]
        );

        // Venta 2: HBO Max Pantalla 1 para María Gómez
        Sale::firstOrCreate(
            ['sale_code' => 'CST-2026-0002'],
            [
                'user_id' => $superReseller->id,
                'customer_id' => $c2->id,
                'service_id' => $hbo->id,
                'service_plan_id' => $pHbo1->id,
                'streaming_account_id' => $accHbo->id,
                'account_profile_id' => $hboProfObjs[1]->id,
                'modality' => 'profile',
                'account_email_snapshot' => $accHbo->account_email,
                'account_password_snapshot' => $accHbo->account_password,
                'profile_name_snapshot' => $hboProfObjs[1]->profile_name,
                'profile_pin_snapshot' => $hboProfObjs[1]->pin,
                'usage_rules_snapshot' => $hbo->default_rules,
                'sale_price' => 3500.00,
                'cost_price' => 1400.00,
                'profit' => 2100.00,
                'start_date' => Carbon::now()->subDays(3)->toDateString(),
                'end_date' => Carbon::now()->addDays(27)->toDateString(),
                'status' => 'active',
                'notes' => 'Activado por Carlos Super Reseller.',
            ]
        );

        // Venta 3: Disney Premium para Gonzalo
        Sale::firstOrCreate(
            ['sale_code' => 'CST-2026-0003'],
            [
                'user_id' => $admin->id,
                'customer_id' => $c1->id,
                'service_id' => $disneyPrem->id,
                'service_plan_id' => $pDsnPrem1->id,
                'streaming_account_id' => $accDsnPrem->id,
                'account_profile_id' => $dsnPremProfObjs[1]->id,
                'modality' => 'profile',
                'account_email_snapshot' => $accDsnPrem->account_email,
                'account_password_snapshot' => $accDsnPrem->account_password,
                'profile_name_snapshot' => $dsnPremProfObjs[1]->profile_name,
                'profile_pin_snapshot' => $dsnPremProfObjs[1]->pin,
                'usage_rules_snapshot' => $disneyPrem->default_rules,
                'sale_price' => 4200.00,
                'cost_price' => 1800.00,
                'profit' => 2400.00,
                'start_date' => Carbon::now()->subDays(2)->toDateString(),
                'end_date' => Carbon::now()->addDays(28)->toDateString(),
                'status' => 'active',
                'notes' => 'Segunda suscripción activa de Gonzalo.',
            ]
        );

        // 8. Ajustes del Sistema y Atajos
        SystemSetting::set('site_name', 'CStreame Streaming Platform');
        SystemSetting::set('currency_symbol', '$');
        SystemSetting::set('currency_code', 'ARS');

        Shortcut::firstOrCreate(
            ['user_id' => $admin->id, 'title' => 'Netflix Partner Portal'],
            ['url' => 'https://netflix.com', 'icon' => 'tv', 'color' => '#E50914']
        );
        Shortcut::firstOrCreate(
            ['user_id' => $admin->id, 'title' => 'WhatsApp Web'],
            ['url' => 'https://web.whatsapp.com', 'icon' => 'message-circle', 'color' => '#25D366']
        );
        Shortcut::firstOrCreate(
            ['user_id' => $admin->id, 'title' => 'Mercado Pago Cobros'],
            ['url' => 'https://mercadopago.com.ar', 'icon' => 'credit-card', 'color' => '#009EE3']
        );
    }
}
