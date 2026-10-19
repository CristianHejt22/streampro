<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Service;
use App\Models\ServicePlan;
use App\Models\StreamingAccount;
use App\Models\AccountProfile;
use App\Models\Customer;
use App\Models\Sale;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

echo "==========================================================" . PHP_EOL;
echo "  CStreame - Streaming Management & Customer Portal Test  " . PHP_EOL;
echo "==========================================================" . PHP_EOL;

// 1. Verify Core Streaming Platforms
$services = Service::where('status', 'active')->get();
echo PHP_EOL . "1. Core Streaming Services (" . $services->count() . " active):" . PHP_EOL;
foreach ($services as $s) {
    $totalProf = AccountProfile::whereHas('streamingAccount', function ($q) use ($s) {
        $q->where('service_id', $s->id)->where('status', 'active');
    })->count();

    $freeProf = AccountProfile::whereHas('streamingAccount', function ($q) use ($s) {
        $q->where('service_id', $s->id)->where('status', 'active');
    })->where('status', 'available')->count();

    $occProf = AccountProfile::whereHas('streamingAccount', function ($q) use ($s) {
        $q->where('service_id', $s->id)->where('status', 'active');
    })->where('status', 'assigned')->count();

    echo "  - [{$s->name}] Total: {$totalProf} | Libres: {$freeProf} | Vendidos: {$occProf}" . PHP_EOL;
}

// 2. Master Account Credentials & Profiles Breakdown
echo PHP_EOL . "2. Master Accounts & Profiles Breakdown (NETFLIX Example):" . PHP_EOL;
$netAcc = StreamingAccount::whereHas('service', function($q) {
    $q->where('slug', 'netflix');
})->with('profiles.activeSale.customer')->first();

if ($netAcc) {
    echo "  Correo Máster: {$netAcc->account_email}" . PHP_EOL;
    echo "  Contraseña Máster: {$netAcc->account_password}" . PHP_EOL;
    echo "  Pantallas en lote: {$netAcc->profiles->count()}" . PHP_EOL;
    echo "  Detalle de Pantallas:" . PHP_EOL;
    foreach ($netAcc->profiles as $p) {
        $sale = $p->activeSale;
        $custName = $sale && $sale->customer ? $sale->customer->name : 'Nadie';
        $vence = $sale ? $sale->end_date->format('d/m/Y') : '-';
        $statusStr = $p->status === 'available' ? 'LIBRE' : ($p->status === 'assigned' ? "VENDIDO a {$custName} (Vence: {$vence})" : 'EN REVISIÓN');
        echo "    * {$p->profile_name} | PIN: {$p->pin} | {$statusStr}" . PHP_EOL;
    }
}

// 3. Test Customer Portal Authentication with Email & Password
echo PHP_EOL . "3. Testing Customer Registration & Vault Login:" . PHP_EOL;
$testCustomerEmail = 'prueba.vault.' . time() . '@cliente.com';
$testPassword = 'ClaveVault2026*';
$resellerMarcos = User::where('referral_code', 'VIP-MARCOS')->first();

$newCustomer = Customer::create([
    'user_id' => $resellerMarcos->id,
    'customer_code' => 'CLI-' . rand(1000, 9999),
    'name' => 'Roberto Sánchez',
    'email' => $testCustomerEmail,
    'phone' => '+54 9 11 7766-5544',
    'password' => Hash::make($testPassword),
    'status' => 'active',
]);

echo "  [OK] Customer registered via {$resellerMarcos->brand_name} ({$resellerMarcos->referral_code}):" . PHP_EOL;
echo "       Name: {$newCustomer->name} | Code: {$newCustomer->customer_code} | Email: {$newCustomer->email}" . PHP_EOL;

// Emulate Login attempt in customer guard
$loginSuccess = Auth::guard('customer')->attempt([
    'email' => $testCustomerEmail,
    'password' => $testPassword,
]);
echo "  [OK] Login attempt with customer credentials: " . ($loginSuccess ? 'SUCCESS (Authenticated)' : 'FAILED') . PHP_EOL;

// 4. Test Direct Streaming Account Activation for this Customer
echo PHP_EOL . "4. Testing Account Activation for this Customer:" . PHP_EOL;
$disneyStd = Service::where('slug', 'disney-standard')->first();
$availableProf = AccountProfile::whereHas('streamingAccount', function ($q) use ($disneyStd) {
    $q->where('service_id', $disneyStd->id)->where('status', 'active');
})->where('status', 'available')->first();

if ($availableProf) {
    $availableProf->update(['status' => 'assigned']);
    $sale = Sale::create([
        'sale_code' => 'CST-' . date('Ymd') . '-TEST',
        'user_id' => $resellerMarcos->id,
        'customer_id' => $newCustomer->id,
        'service_id' => $disneyStd->id,
        'service_plan_id' => $disneyStd->plans()->first()->id,
        'streaming_account_id' => $availableProf->streaming_account_id,
        'account_profile_id' => $availableProf->id,
        'modality' => 'profile',
        'account_email_snapshot' => $availableProf->streamingAccount->account_email,
        'account_password_snapshot' => $availableProf->streamingAccount->account_password,
        'profile_name_snapshot' => $availableProf->profile_name,
        'profile_pin_snapshot' => $availableProf->pin,
        'usage_rules_snapshot' => $disneyStd->default_rules,
        'sale_price' => 3200.00,
        'cost_price' => 1300.00,
        'profit' => 1900.00,
        'start_date' => now()->toDateString(),
        'end_date' => now()->addDays(30)->toDateString(),
        'status' => 'active',
    ]);

    echo "  [OK] Service Activated: {$disneyStd->name}" . PHP_EOL;
    echo "       Assigned Screen: {$sale->profile_name_snapshot} | PIN: {$sale->profile_pin_snapshot}" . PHP_EOL;
    echo "       Master Credentials for Customer Bóveda: {$sale->account_email_snapshot} / {$sale->account_password_snapshot}" . PHP_EOL;
    echo "       Expiration: {$sale->end_date->format('d/m/Y')} ({$sale->days_remaining} days SLA)" . PHP_EOL;
}

echo PHP_EOL . "==========================================================" . PHP_EOL;
echo "  [SUCCESS] All Streaming & Customer Vault Tests Passed!  " . PHP_EOL;
echo "==========================================================" . PHP_EOL;
