<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Ampliar usuarios para Super Panel, Paneles Clonados y Anti-Phishing
        Schema::table('users', function (Blueprint $table) {
            $table->string('anti_phishing_code', 64)->nullable()->after('referral_code');
            $table->text('plain_panel_token')->nullable()->after('password'); // Contraseña reversible cifrada con AES-256 para Super Admin
            $table->string('panel_type', 20)->default('master')->after('role'); // 'master', 'cloned'
            $table->decimal('panel_monthly_fee', 10, 2)->default(15000.00)->after('balance'); // $15.000 / 30 días
            $table->timestamp('panel_expires_at')->nullable()->after('panel_monthly_fee'); // Vencimiento del panel clonado
            $table->decimal('reseller_commission_pct', 5, 2)->default(40.00)->after('panel_expires_at'); // 40% Ganancia Revendedor
            $table->decimal('parent_commission_pct', 5, 2)->default(25.00)->after('reseller_commission_pct'); // 25% Ganancia Panel Patrocinador
        });

        // 2. Ampliar clientes para Biometría y Anti-Phishing en Bóveda
        Schema::table('customers', function (Blueprint $table) {
            $table->string('anti_phishing_phrase', 100)->nullable()->after('password');
            $table->string('security_pin', 60)->nullable()->after('anti_phishing_phrase'); // PIN de desbloqueo de Bóveda
            $table->text('biometric_credential_id')->nullable()->after('security_pin');
            $table->text('biometric_public_key')->nullable()->after('biometric_credential_id');
        });

        // 3. Ampliar ventas para Consignación (48 Horas), Meses Seleccionables y Comisión Residual
        Schema::table('sales', function (Blueprint $table) {
            $table->text('account_password_snapshot')->change(); // Permitir almacenamiento cifrado AES-256
            $table->boolean('is_consignment')->default(false)->after('status');
            $table->boolean('is_consignment_paid')->default(true)->after('is_consignment');
            $table->timestamp('consignment_expires_at')->nullable()->after('is_consignment_paid');
            $table->unsignedTinyInteger('months_duration')->default(1)->after('end_date'); // 1, 2, 3, 6, 12 meses
            $table->decimal('parent_commission_amount', 10, 2)->default(0.00)->after('profit'); // 25% para el panel que clonó el servicio
        });

        // 4. Ampliar cuentas streaming para contraseña cifrada AES-256
        Schema::table('streaming_accounts', function (Blueprint $table) {
            $table->text('account_password')->change();
        });
    }

    public function down(): void
    {
        Schema::table('streaming_accounts', function (Blueprint $table) {
            $table->string('account_password')->change();
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn([
                'is_consignment',
                'is_consignment_paid',
                'consignment_expires_at',
                'months_duration',
                'parent_commission_amount'
            ]);
            $table->string('account_password_snapshot')->change();
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'anti_phishing_phrase',
                'security_pin',
                'biometric_credential_id',
                'biometric_public_key'
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'anti_phishing_code',
                'plain_panel_token',
                'panel_type',
                'panel_monthly_fee',
                'panel_expires_at',
                'reseller_commission_pct',
                'parent_commission_pct'
            ]);
        });
    }
};
