<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabla de Solicitudes de Renovación desde Bóveda de Cliente
        if (!Schema::hasTable('renewal_requests')) {
            Schema::create('renewal_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
                $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // Vendedor / Revendedor asignado
                $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
                $table->foreignId('service_plan_id')->constrained('service_plans')->cascadeOnDelete();
                $table->integer('months_duration')->default(1);
                $table->decimal('amount', 10, 2)->default(0.00);
                $table->string('payment_method')->default('bank_transfer'); // bank_transfer, mercadopago, binance, cash
                $table->string('receipt_path')->nullable(); // Comprobante en Mini Nube
                $table->text('customer_notes')->nullable();
                $table->text('admin_notes')->nullable();
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                $table->timestamp('processed_at')->nullable();
                $table->timestamps();
            });
        }

        // 2. Tabla de Comisiones Específicas por Servicio / Cuenta configuradas por el Proveedor para el Revendedor
        if (!Schema::hasTable('reseller_service_commissions')) {
            Schema::create('reseller_service_commissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('parent_id')->constrained('users')->cascadeOnDelete(); // El Proveedor / Patrocinador
                $table->foreignId('reseller_id')->constrained('users')->cascadeOnDelete(); // El Revendedor
                $table->foreignId('service_id')->constrained('services')->cascadeOnDelete(); // Plataforma (Netflix, HBO, etc.)
                $table->decimal('commission_pct', 5, 2)->default(40.00); // Porcentaje específico para este servicio
                $table->timestamps();

                $table->unique(['reseller_id', 'service_id'], 'reseller_service_unique');
            });
        }

        // 3. Nuevos campos de control para Clonación de Tienda en `users`
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'cloned_store_paid_to_admin')) {
                $table->boolean('cloned_store_paid_to_admin')->default(false)->after('panel_expires_at');
            }
            if (!Schema::hasColumn('users', 'cloned_store_paid_by_reseller')) {
                $table->boolean('cloned_store_paid_by_reseller')->default(false)->after('cloned_store_paid_to_admin');
            }
            if (!Schema::hasColumn('users', 'cloned_store_last_payment_date')) {
                $table->timestamp('cloned_store_last_payment_date')->nullable()->after('cloned_store_paid_by_reseller');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reseller_service_commissions');
        Schema::dropIfExists('renewal_requests');

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'cloned_store_last_payment_date')) {
                $table->dropColumn('cloned_store_last_payment_date');
            }
            if (Schema::hasColumn('users', 'cloned_store_paid_by_reseller')) {
                $table->dropColumn('cloned_store_paid_by_reseller');
            }
            if (Schema::hasColumn('users', 'cloned_store_paid_to_admin')) {
                $table->dropColumn('cloned_store_paid_to_admin');
            }
        });
    }
};
