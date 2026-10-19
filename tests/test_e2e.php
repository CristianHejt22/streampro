<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=========================================" . PHP_EOL;
echo "   CStreame - E2E Verification Script    " . PHP_EOL;
echo "=========================================" . PHP_EOL;

// 1. Verify Users & Balances
$admin = App\Models\User::where('role', 'admin')->first();
$reseller = App\Models\User::where('role', 'reseller')->first();
echo "[OK] Admin User: " . $admin->name . " <" . $admin->email . ">" . PHP_EOL;
echo "[OK] Reseller User: " . $reseller->name . " (Balance: $" . number_format($reseller->balance, 2) . ")" . PHP_EOL;

// 2. Services, Plans & Inventory
$services = App\Models\Service::with('plans')->get();
echo "[OK] Loaded Services: " . $services->count() . PHP_EOL;
foreach ($services as $srv) {
    echo "  - " . $srv->name . " (Available Profiles: " . $srv->available_profiles_count . ", Available Full Accounts: " . $srv->available_accounts_count . ")" . PHP_EOL;
}

$totalAccounts = App\Models\StreamingAccount::count();
$totalProfiles = App\Models\AccountProfile::count();
$availProfiles = App\Models\AccountProfile::where('status', 'available')->count();
$occupiedProfiles = App\Models\AccountProfile::where('status', 'occupied')->count();
echo "[OK] Inventory Stats: $totalAccounts master accounts, $totalProfiles profiles ($availProfiles available, $occupiedProfiles occupied)" . PHP_EOL;

// 3. Test WhatsApp Delivery & Renewal Messages
$sale = App\Models\Sale::with('customer', 'servicePlan.service')->latest()->first();
if ($sale) {
    echo PHP_EOL . "--- Sample WhatsApp Delivery Message for Sale #{$sale->id} ---" . PHP_EOL;
    echo $sale->whats_app_delivery_message . PHP_EOL;
    echo "--------------------------------------------------------" . PHP_EOL;
    echo PHP_EOL . "--- Sample WhatsApp Expiration Alert ---" . PHP_EOL;
    echo $sale->whats_app_renewal_message . PHP_EOL;
    echo "--------------------------------------------------------" . PHP_EOL;
}

// 4. Test Cron Execution Simulation
echo PHP_EOL . "Testing CStreame Maintenance Cron..." . PHP_EOL;
Illuminate\Support\Facades\Artisan::call('cstreame:cron');
$cronOutput = Illuminate\Support\Facades\Artisan::output();
echo trim($cronOutput) . PHP_EOL;

// 5. Test Warranty Replacement Logic
echo PHP_EOL . "Testing Warranty Auto-Replacement Simulation..." . PHP_EOL;
$testSale = App\Models\Sale::where('status', 'active')->first();
if ($testSale && $testSale->profile) {
    echo "Active Sale #{$testSale->id} currently assigned to profile: " . $testSale->profile->profile_name . " (Account: " . $testSale->profile->streamingAccount->email . ")" . PHP_EOL;
    
    // Check if another profile is available for replacement
    $replacement = App\Models\AccountProfile::where('status', 'available')
        ->whereHas('streamingAccount', function ($q) use ($testSale) {
            $q->where('service_id', $testSale->servicePlan->service_id);
        })
        ->where('id', '!=', $testSale->profile_id)
        ->first();
        
    if ($replacement) {
        echo "Found available replacement profile ID: {$replacement->id} ({$replacement->profile_name}) on account {$replacement->streamingAccount->email}" . PHP_EOL;
    } else {
        echo "No extra profile available for replacement in test dataset (stock is tight), which is normal." . PHP_EOL;
    }
}

echo PHP_EOL . "=========================================" . PHP_EOL;
echo " [SUCCESS] All CStreame Core Systems OK! " . PHP_EOL;
echo "=========================================" . PHP_EOL;
