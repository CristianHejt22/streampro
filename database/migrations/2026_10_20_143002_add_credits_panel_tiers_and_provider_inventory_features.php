<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Modificar tabla users con sistema de créditos, tiers de panel y soporte de proveedor
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'panel_tier')) {
                $table->enum('panel_tier', ['basic', 'pro'])->default('pro')->after('panel_type');
            }
            if (!Schema::hasColumn('users', 'credits_basic')) {
                $table->integer('credits_basic')->default(0)->after('balance');
            }
            if (!Schema::hasColumn('users', 'credits_pro')) {
                $table->integer('credits_pro')->default(0)->after('credits_basic');
            }
            if (!Schema::hasColumn('users', 'provider_override_support_whatsapp')) {
                $table->boolean('provider_override_support_whatsapp')->default(false)->after('support_whatsapp');
            }
        });

        // 2. Modificar tabla services para inventario individual por proveedor / revendedor
        Schema::table('services', function (Blueprint $table) {
            if (!Schema::hasColumn('services', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            }
        });

        // 3. Crear tabla de transacciones de créditos vendidas por el Super Admin
        if (!Schema::hasTable('credit_transactions')) {
            Schema::create('credit_transactions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('admin_id')->nullable();
                $table->enum('type', ['purchase', 'activation_use', 'refund', 'admin_adjustment'])->default('purchase');
                $table->enum('panel_tier', ['basic', 'pro'])->default('basic');
                $table->integer('credits_amount'); // Ej: +10, +5, -1
                $table->decimal('unit_price', 10, 2)->nullable(); // Ej: 2500, 3000, 4000, 5000
                $table->decimal('total_price', 10, 2)->nullable();
                $table->string('target_panel_name')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('admin_id')->references('id')->on('users')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_transactions');

        Schema::table('services', function (Blueprint $table) {
            if (Schema::hasColumn('services', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'panel_tier',
                'credits_basic',
                'credits_pro',
                'provider_override_support_whatsapp',
            ]);
        });
    }
};
