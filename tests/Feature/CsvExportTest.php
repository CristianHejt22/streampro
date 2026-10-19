<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;

class CsvExportTest extends TestCase
{
    public function test_licenses_export_csv_streams_file()
    {
        $admin = User::where('role', 'admin')->first();

        $response = $this->actingAs($admin)->get('/licenses/export');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertTrue(str_contains($response->headers->get('Content-Disposition'), 'attachment; filename="licencias_'));
    }

    public function test_treasury_export_csv_streams_file()
    {
        $admin = User::where('role', 'admin')->first();

        $response = $this->actingAs($admin)->get('/treasury/export');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertTrue(str_contains($response->headers->get('Content-Disposition'), 'attachment; filename="ventas_ordenes_'));
    }
}
