<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->string('name'); // e.g. "1 Pantalla con PIN", "Cuenta Completa Familiar"
            $table->enum('modality', ['profile', 'full_account'])->default('profile');
            $table->integer('duration_days')->default(30);
            $table->decimal('price', 10, 2); // Precio cliente final
            $table->decimal('reseller_price', 10, 2); // Precio mayorista
            $table->decimal('cost_estimate', 10, 2)->default(0.00); // Costo aproximado
            $table->integer('screens_count')->default(1);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_plans');
    }
};
