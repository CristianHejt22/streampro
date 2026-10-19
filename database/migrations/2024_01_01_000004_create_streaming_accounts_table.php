<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('streaming_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('account_email'); // Correo / Usuario
            $table->string('account_password'); // Contraseña
            $table->string('recovery_email')->nullable(); // Correo alternativo / pin parental / notas clave
            $table->enum('modality', ['profile', 'full_account'])->default('profile');
            $table->unsignedInteger('total_profiles')->default(5);
            $table->decimal('supplier_cost', 10, 2)->default(0.00);
            $table->date('supplier_expiration_date')->nullable(); // Vencimiento con el proveedor
            $table->enum('status', ['active', 'in_review', 'dropped', 'expired', 'paused'])->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('streaming_accounts');
    }
};
