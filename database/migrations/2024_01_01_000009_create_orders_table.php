<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_code')->unique(); // e.g. ORD-STR-8492
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('service_plan_id')->constrained('service_plans')->cascadeOnDelete();
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('customer_email')->nullable();
            $table->decimal('amount', 10, 2);
            $table->enum('payment_method', ['mercadopago', 'bank_transfer', 'binance', 'reseller_balance'])->default('bank_transfer');
            $table->enum('payment_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('receipt_path')->nullable(); // Mini nube temporal de comprobantes
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
