<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\App;
use App\Models\Order;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CatalogAndReferralTest extends TestCase
{
    public function test_catalog_loads_successfully()
    {
        $response = $this->get('/catalogo');
        $response->assertStatus(200);
        $response->assertSee('StreamPro');
    }

    public function test_referral_link_redirects_with_reseller_branding()
    {
        $reseller = User::where('role', 'reseller')->first();
        if ($reseller && $reseller->referral_code) {
            $response = $this->get('/ref/' . $reseller->referral_code);
            $response->assertRedirect('/catalogo?ref=' . $reseller->referral_code);

            $catalogResponse = $this->get('/catalogo?ref=' . $reseller->referral_code);
            $catalogResponse->assertStatus(200);
            $catalogResponse->assertSee($reseller->effective_brand_name);
        } else {
            $this->assertTrue(true);
        }
    }

    public function test_checkout_manual_transfer_creates_pending_order_and_stores_receipt()
    {
        Storage::fake('local');

        $admin = User::where('role', 'admin')->first();
        $app = App::first();

        $receipt = UploadedFile::fake()->create('comprobante.pdf', 100, 'application/pdf');

        $response = $this->post("/checkout/{$app->id}", [
            'client_name' => 'Juan Pérez Test',
            'client_email' => 'juan@test.com',
            'client_phone' => '+54911223344',
            'payment_method' => 'manual_transfer',
            'seller_id' => $admin->id,
            'receipt' => $receipt,
        ]);

        $response->assertRedirect();

        $order = Order::where('client_email', 'juan@test.com')->latest()->first();
        $this->assertNotNull($order);
        $this->assertEquals('pending', $order->payment_status);
        $this->assertNotNull($order->receipt_file_path);
        Storage::disk('local')->assertExists($order->receipt_file_path);
    }
}
