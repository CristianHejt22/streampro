<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Sale;
use Illuminate\Support\Facades\Auth;

echo "--- 1. Testing Customer existence ---" . PHP_EOL;
$customer = Customer::where('email', 'cliente@cstreame.com')->first();
if (!$customer) {
    echo "ERROR: cliente@cstreame.com not found!" . PHP_EOL;
    exit(1);
}
echo "Customer ID: {$customer->id}, Name: {$customer->name}, Password hash: {$customer->password}" . PHP_EOL;
$check = \Illuminate\Support\Facades\Hash::check('cliente123', $customer->password);
echo "Password check for 'cliente123': " . ($check ? 'VALID' : 'INVALID') . PHP_EOL;

echo "--- 2. Testing Auth::guard('customer')->attempt() ---" . PHP_EOL;
$attempt = Auth::guard('customer')->attempt(['email' => 'cliente@cstreame.com', 'password' => 'cliente123']);
echo "Attempt result: " . ($attempt ? 'SUCCESS' : 'FAILED') . PHP_EOL;
if ($attempt) {
    $user = Auth::guard('customer')->user();
    echo "Logged in customer: " . ($user ? $user->name . " (ID: " . $user->id . ")" : 'NULL') . PHP_EOL;
}

echo "--- 3. Testing Active Sales for this customer ---" . PHP_EOL;
$activeSales = Sale::with(['service', 'plan', 'streamingAccount', 'profile'])
    ->where('customer_id', $customer->id)
    ->whereIn('status', ['active', 'expiring_soon'])
    ->latest()
    ->get();
echo "Active sales count: " . $activeSales->count() . PHP_EOL;
foreach ($activeSales as $s) {
    echo "Sale #{$s->id}: {$s->sale_code} - " . ($s->service ? $s->service->name : 'No service') . PHP_EOL;
    echo "  Email: {$s->account_email_snapshot}, Pass: {$s->account_password_snapshot}" . PHP_EOL;
    echo "  Days remaining: {$s->days_remaining}, Status badge: " . json_encode($s->status_badge) . PHP_EOL;
}

echo "--- 4. Testing View Rendering 'portal.boveda' ---" . PHP_EOL;
try {
    $reseller = $customer->user ?: \App\Models\User::where('role', 'admin')->first();
    $historySales = Sale::with(['service', 'plan'])
        ->where('customer_id', $customer->id)
        ->whereNotIn('status', ['active', 'expiring_soon'])
        ->latest()
        ->take(5)
        ->get();
    $catalogServices = \App\Models\Service::where('status', 'active')
        ->with(['plans' => function ($q) {
            $q->where('is_active', true);
        }])
        ->get();

    $html = view('portal.boveda', compact('customer', 'activeSales', 'historySales', 'reseller', 'catalogServices'))->render();
    echo "View portal.boveda rendered successfully! (HTML length: " . strlen($html) . " bytes)" . PHP_EOL;
} catch (\Throwable $e) {
    echo "ERROR rendering view: " . $e->getMessage() . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
}
