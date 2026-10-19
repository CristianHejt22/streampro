<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // Vendedor (Admin, Super Reseller o Revendedor)
            $table->string('customer_code')->unique()->nullable(); // ej CLI-8942
            $table->string('name');
            $table->string('phone')->nullable(); // WhatsApp (con código de país ej +549...)
            $table->string('email')->nullable();
            $table->string('password')->nullable(); // Contraseña de acceso a su Bóveda / Portal
            $table->enum('status', ['active', 'pending', 'suspended'])->default('active');
            $table->string('country')->nullable();
            $table->text('notes')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
