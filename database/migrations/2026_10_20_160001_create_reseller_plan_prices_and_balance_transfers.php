<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabla de Precios Personalizados por Plan (Costo de Proveedor y PVP del Revendedor)
        if (!Schema::hasTable('reseller_plan_prices')) {
            Schema::create('reseller_plan_prices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('parent_id')->nullable()->constrained('users')->cascadeOnDelete(); // Proveedor patrocinador
                $table->foreignId('reseller_id')->constrained('users')->cascadeOnDelete(); // Revendedor
                $table->foreignId('service_plan_id')->constrained('service_plans')->cascadeOnDelete(); // Plan de streaming
                $table->decimal('provider_cost', 10, 2)->nullable(); // Costo mayorista que le cobra el proveedor al revendedor
                $table->decimal('reseller_pvp', 10, 2)->nullable(); // Precio final de venta al público fijado por el revendedor
                $table->timestamps();

                $table->unique(['reseller_id', 'service_plan_id'], 'reseller_plan_unique');
            });
        }

        // 2. Tabla de Transferencias de Fondos entre Revendedor y Proveedor
        if (!Schema::hasTable('balance_transfers')) {
            Schema::create('balance_transfers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('reseller_id')->constrained('users')->cascadeOnDelete(); // Quien transfiere / solicita saldo
                $table->foreignId('provider_id')->constrained('users')->cascadeOnDelete(); // Quien recibe / proveedor
                $table->decimal('amount', 10, 2);
                $table->string('payment_method')->default('bank_transfer'); // bank_transfer, mercadopago, binance, cash
                $table->string('receipt_path')->nullable(); // Imagen del comprobante de transferencia
                $table->string('reference_code')->nullable(); // Nro de comprobante / operación
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                $table->text('reseller_notes')->nullable();
                $table->text('provider_notes')->nullable();
                $table->timestamp('processed_at')->nullable();
                $table->timestamps();
            });
        }

        // 3. Columna de preferencia de WhatsApp por panel en `users`
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'whatsapp_support_preference')) {
                // 'auto' (según regla global), 'reseller' (whatsapp propio del panel), 'provider' (del proveedor), 'admin' (del super admin)
                $table->string('whatsapp_support_preference')->default('auto')->after('provider_override_support_whatsapp');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'whatsapp_support_preference')) {
                $table->dropColumn('whatsapp_support_preference');
            }
        });

        Schema::dropIfExists('balance_transfers');
        Schema::dropIfExists('reseller_plan_prices');
    }
};
