<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('sale_code')->unique(); // e.g. CST-2026-0001
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // Vendedor (Admin o Revendedor)
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->foreignId('service_plan_id')->nullable()->constrained('service_plans')->nullOnDelete();
            $table->foreignId('streaming_account_id')->constrained('streaming_accounts')->cascadeOnDelete();
            $table->foreignId('account_profile_id')->nullable()->constrained('account_profiles')->nullOnDelete();
            
            $table->enum('modality', ['profile', 'full_account'])->default('profile');
            
            // Snapshot inmutable de las credenciales asignadas al momento de venta
            $table->string('account_email_snapshot');
            $table->string('account_password_snapshot');
            $table->string('profile_name_snapshot')->nullable();
            $table->string('profile_pin_snapshot')->nullable();
            $table->text('usage_rules_snapshot')->nullable();
            
            // Finanzas de la venta
            $table->decimal('sale_price', 10, 2);
            $table->decimal('cost_price', 10, 2)->default(0.00);
            $table->decimal('profit', 10, 2)->default(0.00);
            
            // Control de Fechas
            $table->date('start_date');
            $table->date('end_date'); // Fecha de vencimiento para el cliente
            
            $table->enum('status', ['active', 'expiring_soon', 'expired', 'renewed', 'replaced', 'cancelled'])->default('active');
            $table->unsignedInteger('renewal_count')->default(0);
            $table->timestamp('reminder_sent_at')->nullable(); // Registro de recordatorio de cobro enviado por WhatsApp
            
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
