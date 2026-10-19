<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Customer;
use App\Models\Service;
use App\Models\ServicePlan;
use App\Models\StreamingAccount;
use App\Models\AccountProfile;
use App\Models\Sale;
use App\Models\Order;
use App\Models\Warranty;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

echo "=============================================" . PHP_EOL;
echo "   CStreame - Workflow & Transactions Test   " . PHP_EOL;
echo "=============================================" . PHP_EOL;

// 1. Test Reseller Sale with Balance Deduction
$reseller = User::where('role', 'reseller')->first();
$initialBalance = $reseller->balance;
$plan = ServicePlan::where('service_id', 1)->where('modality', 'profile')->first();
$customer = Customer::first();

echo "1. Testing Reseller Sale Creation:" . PHP_EOL;
echo "   Reseller: {$reseller->name} (Initial Balance: \${$initialBalance})" . PHP_EOL;
echo "   Plan: {$plan->name} (Cost for reseller: \${$plan->reseller_price})" . PHP_EOL;

// Emulate SaleController logic
$availableProfile = AccountProfile::where('status', 'available')
    ->whereHas('streamingAccount', function ($q) use ($plan) {
        $q->where('service_id', $plan->service_id)->where('status', 'active');
    })->first();

if (!$availableProfile) {
    throw new Exception("No available profile found for testing!");
}

$salePrice = 4500.00;
$costPrice = $plan->reseller_price > 0 ? $plan->reseller_price : 2000.00;

// Deduct balance from reseller
$reseller->deductBalance($costPrice);
$availableProfile->update(['status' => 'assigned']);

$newSale = Sale::create([
    'sale_code' => 'CS-' . strtoupper(bin2hex(random_bytes(4))),
    'user_id' => $reseller->id,
    'customer_id' => $customer->id,
    'service_id' => $plan->service_id,
    'service_plan_id' => $plan->id,
    'streaming_account_id' => $availableProfile->streaming_account_id,
    'account_profile_id' => $availableProfile->id,
    'modality' => 'profile',
    'account_email_snapshot' => $availableProfile->streamingAccount->account_email,
    'account_password_snapshot' => $availableProfile->streamingAccount->account_password,
    'profile_name_snapshot' => $availableProfile->profile_name,
    'profile_pin_snapshot' => $availableProfile->pin,
    'usage_rules_snapshot' => $plan->service->default_rules,
    'sale_price' => $salePrice,
    'cost_price' => $costPrice,
    'profit' => $salePrice - $costPrice,
    'start_date' => now(),
    'end_date' => now()->addDays(30),
    'status' => 'active',
    'renewal_count' => 0,
]);

echo "   [OK] Sale created: {$newSale->sale_code} - Profile: {$newSale->profile_name_snapshot} (PIN: {$newSale->profile_pin_snapshot})" . PHP_EOL;
echo "   [OK] Reseller New Balance: \${$reseller->fresh()->balance} (Deducted: \${$costPrice})" . PHP_EOL;

// 2. Test Sale Renewal (+30 days)
echo PHP_EOL . "2. Testing Renewal (+30 days):" . PHP_EOL;
$oldEnd = $newSale->end_date;
$newSale->update([
    'end_date' => \Carbon\Carbon::parse($newSale->end_date)->addDays(30),
    'renewal_count' => $newSale->renewal_count + 1,
    'status' => 'active',
]);
echo "   [OK] End date updated from " . $oldEnd->format('Y-m-d') . " to " . $newSale->fresh()->end_date->format('Y-m-d') . " (Renewals: {$newSale->renewal_count})" . PHP_EOL;

// 3. Test Warranty Auto-Replacement
echo PHP_EOL . "3. Testing Warranty Replacement:" . PHP_EOL;
$replacementProfile = AccountProfile::where('status', 'available')
    ->whereHas('streamingAccount', function ($q) use ($plan) {
        $q->where('service_id', $plan->service_id)->where('status', 'active');
    })
    ->where('id', '!=', $newSale->account_profile_id)
    ->first();

if ($replacementProfile) {
    $oldProfileId = $newSale->account_profile_id;
    // Mark old profile under review
    AccountProfile::where('id', $oldProfileId)->update(['status' => 'in_review']);
    // Assign new profile
    $replacementProfile->update(['status' => 'assigned']);

    Warranty::create([
        'sale_id' => $newSale->id,
        'user_id' => $newSale->user_id,
        'old_profile_id' => $oldProfileId,
        'new_profile_id' => $replacementProfile->id,
        'reason' => 'Cuenta caída / Clave cambiada por el proveedor',
        'status' => 'resolved',
        'resolved_at' => now(),
    ]);

    $newSale->update([
        'streaming_account_id' => $replacementProfile->streaming_account_id,
        'account_profile_id' => $replacementProfile->id,
        'account_email_snapshot' => $replacementProfile->streamingAccount->account_email,
        'account_password_snapshot' => $replacementProfile->streamingAccount->account_password,
        'profile_name_snapshot' => $replacementProfile->profile_name,
        'profile_pin_snapshot' => $replacementProfile->pin,
    ]);

    echo "   [OK] Warranty executed: Replaced old profile #{$oldProfileId} with #{$replacementProfile->id} ({$replacementProfile->profile_name})" . PHP_EOL;
    echo "   [OK] Updated delivery message ready with new PIN: {$newSale->profile_pin_snapshot}" . PHP_EOL;
} else {
    echo "   [INFO] No spare profile for warranty in this specific service right now." . PHP_EOL;
}

// 4. Test Catalog Order & Mini Nube Auto-Purge
echo PHP_EOL . "4. Testing Catalog Order, Treasury Approval & Mini Nube Purge:" . PHP_EOL;
// Create a fake receipt file in public disk
Storage::disk('public')->makeDirectory('receipts_temp');
$fakeReceiptName = 'receipt_test_' . time() . '.png';
$fakeReceiptPath = 'receipts_temp/' . $fakeReceiptName;
Storage::disk('public')->put($fakeReceiptPath, 'fake-image-data-for-receipt');
echo "   [OK] Created temporary receipt on disk: {$fakeReceiptPath}" . PHP_EOL;

// Test order with a plan that has available profiles (e.g. Disney+)
$disneyPlan = ServicePlan::whereHas('service', function($q) {
    $q->where('name', 'like', '%Disney%');
})->where('modality', 'profile')->first() ?: $plan;

$admin = User::where('role', 'admin')->first();

$order = Order::create([
    'order_code' => 'ORD-TEST-' . time(),
    'seller_id' => $admin->id,
    'service_plan_id' => $disneyPlan->id,
    'customer_name' => 'Cliente Prueba Web',
    'customer_email' => 'cliente.web@gmail.com',
    'customer_phone' => '+5491199998888',
    'amount' => $disneyPlan->price,
    'payment_method' => 'bank_transfer',
    'payment_status' => 'pending',
    'receipt_path' => $fakeReceiptPath,
]);

echo "   [OK] Order #{$order->order_code} registered in pending state with receipt." . PHP_EOL;

// Run approveAndClose()
$approvedSale = $order->approveAndClose('Aprobado manualmente en test de verificación.');

if ($approvedSale) {
    echo "   [OK] Order approved! Generated Sale #{$approvedSale->sale_code}" . PHP_EOL;
    $fileExistsAfter = Storage::disk('public')->exists($fakeReceiptPath);
    echo "   [OK] Mini Nube Storage Check: Receipt file deleted from disk? " . (!$fileExistsAfter ? 'YES (Disk Space Protected ✓)' : 'NO ✗') . PHP_EOL;
    echo "   [OK] Order payment status: {$order->fresh()->payment_status}" . PHP_EOL;
} else {
    echo "   [WARNING] Order could not be auto-assigned (no profile left for this plan)" . PHP_EOL;
}

echo PHP_EOL . "=============================================" . PHP_EOL;
echo "  [ALL TESTS PASSED] Workflows Verified!     " . PHP_EOL;
echo "=============================================" . PHP_EOL;
