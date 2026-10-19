<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\App;
use App\Models\Order;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class TreasuryAndReceiptPurgeTest extends TestCase
{
    public function test_approve_and_close_activates_license_and_permanently_deletes_receipt()
    {
        Storage::fake('local');

        $admin = User::where('role', 'admin')->first();
        $app = App::where('type', 'basic')->first() ?? App::first();

        // Create a dummy receipt file in fake storage
        $receiptPath = 'receipts_temp/test_recibo_' . time() . '.jpg';
        Storage::disk('local')->put($receiptPath, 'fake-image-bytes');
        Storage::disk('local')->assertExists($receiptPath);

        // Create pending order
        $order = Order::create([
            'order_code' => 'ORD-TEST-' . time() . '-' . rand(1000, 9999),
            'seller_id' => $admin->id,
            'app_id' => $app->id,
            'client_name' => 'Cliente Auditoría',
            'client_email' => 'cliente.auditoria@test.com',
            'amount' => $app->price,
            'currency' => 'ARS',
            'payment_method' => 'manual_transfer',
            'payment_status' => 'pending',
            'receipt_file_path' => $receiptPath,
            'receipt_file_name' => 'test_recibo.jpg',
        ]);

        $this->assertEquals('pending', $order->payment_status);

        // Approve and Close as Admin
        $response = $this->actingAs($admin)->post("/treasury/orders/{$order->id}/approve", [
            'notes' => 'Aprobado en test unitario.'
        ]);

        $response->assertSessionHas('success');

        $order->refresh();
        $this->assertEquals('approved', $order->payment_status);
        $this->assertNotNull($order->license_id);
        $this->assertNotNull($order->license);

        // VERIFICACIÓN CRÍTICA: El archivo del comprobante debe estar borrado del disco físico
        $this->assertNull($order->receipt_file_path);
        Storage::disk('local')->assertMissing($receiptPath);
    }

    public function test_reject_and_close_marks_rejected_and_deletes_receipt()
    {
        Storage::fake('local');

        $admin = User::where('role', 'admin')->first();
        $app = App::first();

        $receiptPath = 'receipts_temp/test_recibo_reject_' . time() . '.jpg';
        Storage::disk('local')->put($receiptPath, 'fake-image-bytes');

        $order = Order::create([
            'order_code' => 'ORD-TEST-REJECT-' . time() . '-' . rand(1000, 9999),
            'seller_id' => $admin->id,
            'app_id' => $app->id,
            'client_name' => 'Cliente Rechazado',
            'client_email' => 'cliente.rechazo@test.com',
            'amount' => $app->price,
            'payment_method' => 'manual_transfer',
            'payment_status' => 'pending',
            'receipt_file_path' => $receiptPath,
        ]);

        $response = $this->actingAs($admin)->post("/treasury/orders/{$order->id}/reject", [
            'reason' => 'Comprobante adulterado'
        ]);

        $response->assertSessionHas('success');

        $order->refresh();
        $this->assertEquals('rejected', $order->payment_status);
        $this->assertNull($order->receipt_file_path);
        Storage::disk('local')->assertMissing($receiptPath);
    }
}
