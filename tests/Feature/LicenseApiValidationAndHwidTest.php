<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\License;
use App\Models\App;
use App\Models\User;
use Carbon\Carbon;

class LicenseApiValidationAndHwidTest extends TestCase
{
    public function test_api_ping()
    {
        $response = $this->getJson('/api/v1/ping');
        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'online',
        ]);
    }

    public function test_license_validation_and_hwid_binding()
    {
        $admin = User::where('role', 'admin')->first();
        $app = App::first();

        // Create an un-bound license
        $license = License::create([
            'license_key' => 'TEST-KEY-' . strtoupper(bin2hex(random_bytes(4))),
            'app_id' => $app->id,
            'reseller_id' => $admin->id,
            'client_name' => 'Cliente Dispositivo Test',
            'client_email' => 'tvbox@cliente.com',
            'type' => 'basic',
            'status' => 'active',
            'device_hwid' => null, // Not yet bound
            'expires_at' => Carbon::now()->addDays(30),
        ]);

        $firstHwid = 'HWID-TVBOX-ANDROID-998877';

        // 1st Call: Should succeed and bind HWID
        $response1 = $this->postJson('/api/v1/license/validate', [
            'license_key' => $license->license_key,
            'device_hwid' => $firstHwid,
            'app_slug' => $app->slug,
            'device_name' => 'Xiaomi Mi Box 4K',
        ]);

        $response1->assertStatus(200);
        $response1->assertJson([
            'success' => true,
            'valid' => true,
            'status' => 'active',
            'license' => [
                'device_hwid' => $firstHwid,
                'hwid_newly_bound' => true,
            ]
        ]);

        $license->refresh();
        $this->assertEquals($firstHwid, $license->device_hwid);

        // 2nd Call with same HWID: Should succeed
        $response2 = $this->postJson('/api/v1/license/validate', [
            'license_key' => $license->license_key,
            'device_hwid' => $firstHwid,
        ]);
        $response2->assertStatus(200);
        $response2->assertJson(['valid' => true]);

        // 3rd Call with DIFFERENT HWID: Must reject with HWID_MISMATCH
        $differentHwid = 'HWID-DIFFERENT-UNAUTHORIZED-112233';
        $response3 = $this->postJson('/api/v1/license/validate', [
            'license_key' => $license->license_key,
            'device_hwid' => $differentHwid,
        ]);

        $response3->assertStatus(403);
        $response3->assertJson([
            'success' => false,
            'valid' => false,
            'error_code' => 'HWID_MISMATCH',
        ]);
    }

    public function test_expired_license_is_rejected()
    {
        $admin = User::where('role', 'admin')->first();
        $app = App::first();

        $expiredLicense = License::create([
            'license_key' => 'TEST-EXP-' . strtoupper(bin2hex(random_bytes(4))),
            'app_id' => $app->id,
            'reseller_id' => $admin->id,
            'client_name' => 'Cliente Vencido',
            'type' => 'basic',
            'status' => 'active',
            'expires_at' => Carbon::now()->subDays(2), // Expired 2 days ago
        ]);

        $response = $this->postJson('/api/v1/license/validate', [
            'license_key' => $expiredLicense->license_key,
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'valid' => false,
            'error_code' => 'LICENSE_EXPIRED',
        ]);

        $expiredLicense->refresh();
        $this->assertEquals('expired', $expiredLicense->status);
    }

    public function test_app_check_update_endpoint()
    {
        $app = App::first();
        $app->update([
            'version' => '5.0.0',
            'changelog' => 'Mejoras de rendimiento y compatibilidad Android 14',
        ]);

        $response = $this->getJson("/api/v1/app/{$app->slug}/check-update?version=4.0.0");
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'has_update' => true,
            'latest_version' => '5.0.0',
            'current_version' => '4.0.0',
        ]);
    }
}
