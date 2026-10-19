<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\App;
use App\Models\License;

class ResellerWalletAndPlansTest extends TestCase
{
    public function test_create_reseller_with_basic_plan_receives_correct_inventory()
    {
        $admin = User::where('role', 'admin')->first();

        $email = 'nuevo_basico_' . time() . '@test.com';

        $response = $this->actingAs($admin)->post('/resellers', [
            'name' => 'Revendedor Básico Test',
            'email' => $email,
            'password' => 'password123',
            'reseller_plan' => 'basic',
            'phone' => '+54911000000',
        ]);

        $response->assertRedirect('/resellers');

        $reseller = User::where('email', $email)->first();
        $this->assertNotNull($reseller);
        $this->assertEquals(2, $reseller->credits_basic);
        $this->assertEquals(1, $reseller->credits_pro);
        $this->assertEquals('basic', $reseller->reseller_plan);
    }

    public function test_create_reseller_with_pro_plan_receives_correct_inventory()
    {
        $admin = User::where('role', 'admin')->first();

        $email = 'nuevo_pro_' . time() . '@test.com';

        $response = $this->actingAs($admin)->post('/resellers', [
            'name' => 'Revendedor Pro Test',
            'email' => $email,
            'password' => 'password123',
            'reseller_plan' => 'pro',
            'phone' => '+54911111111',
        ]);

        $response->assertRedirect('/resellers');

        $reseller = User::where('email', $email)->first();
        $this->assertNotNull($reseller);
        $this->assertEquals(5, $reseller->credits_basic);
        $this->assertEquals(3, $reseller->credits_pro);
        $this->assertEquals('pro', $reseller->reseller_plan);
    }

    public function test_reseller_wallet_decrements_when_generating_license()
    {
        $reseller = User::create([
            'name' => 'Wallet Reseller Test',
            'email' => 'wallet_' . time() . '@test.com',
            'password' => bcrypt('password123'),
            'role' => 'reseller',
            'status' => 'active',
            'credits_basic' => 3,
            'credits_pro' => 2,
            'reseller_plan' => 'basic',
            'referral_code' => 'TEST-WALLET-' . time(),
        ]);

        $basicApp = App::where('type', 'basic')->first() ?? App::create([
            'name' => 'App Basica Test',
            'slug' => 'app-basica-test-' . time(),
            'version' => '1.0.0',
            'category' => 'Test',
            'type' => 'basic',
            'price' => 2000,
            'is_active' => true,
        ]);

        // Reseller generates a basic license
        $response = $this->actingAs($reseller)->post('/licenses', [
            'app_id' => $basicApp->id,
            'client_name' => 'Cliente de Billetera',
            'client_email' => 'wallet.client@test.com',
            'duration_months' => 1,
            'max_devices' => 1,
        ]);

        $response->assertRedirect('/licenses');

        $reseller->refresh();
        $this->assertEquals(2, $reseller->credits_basic); // 3 - 1 = 2
    }

    public function test_reseller_cannot_generate_license_without_enough_credits()
    {
        $reseller = User::create([
            'name' => 'Zero Credits Reseller',
            'email' => 'zero_' . time() . '@test.com',
            'password' => bcrypt('password123'),
            'role' => 'reseller',
            'status' => 'active',
            'credits_basic' => 0,
            'credits_pro' => 0,
            'reseller_plan' => 'basic',
        ]);

        $basicApp = App::where('type', 'basic')->first();

        $response = $this->actingAs($reseller)->post('/licenses', [
            'app_id' => $basicApp->id,
            'client_name' => 'Cliente Fallido',
            'duration_months' => 1,
            'max_devices' => 1,
        ]);

        $response->assertSessionHas('error');
    }
}
