<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->enum('role', ['admin', 'reseller'])->default('reseller');
            $table->enum('status', ['active', 'suspended'])->default('active');
            
            // Billetera y Saldo de Revendedor (Monedero prepago / crédito)
            $table->decimal('balance', 12, 2)->default(0.00);
            $table->string('reseller_plan')->default('standard'); // 'standard', 'vip', 'mayorista'
            $table->string('referral_code')->nullable()->unique();
            
            // Marca Blanca y Customización
            $table->string('brand_name')->nullable();
            $table->string('brand_logo')->nullable();
            $table->string('brand_favicon')->nullable();
            $table->string('brand_primary_color')->default('#e50914'); // Rojo Netflix / Streaming
            $table->string('brand_accent_color')->default('#06b6d4');
            $table->string('custom_login_bg')->nullable();
            $table->string('support_whatsapp')->nullable();
            $table->string('support_telegram')->nullable();
            
            // Tesorería y Cobros Independientes
            // Mercado Pago
            $table->text('mp_public_key')->nullable();
            $table->text('mp_access_token')->nullable();
            // Transferencia Bancaria / Billeteras Digitales
            $table->string('bank_cbu')->nullable();
            $table->string('bank_cvu')->nullable();
            $table->string('bank_alias')->nullable();
            $table->string('bank_holder_name')->nullable();
            $table->string('bank_dni')->nullable();
            $table->string('binance_id')->nullable();
            $table->text('bank_instructions')->nullable();
            
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
