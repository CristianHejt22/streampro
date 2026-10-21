<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'payment_method')) {
                $table->string('payment_method')->default('wallet')->after('months_duration');
            }
            if (!Schema::hasColumn('sales', 'payment_proof')) {
                $table->string('payment_proof')->nullable()->after('payment_method');
            }
            if (!Schema::hasColumn('sales', 'transfer_accepted_at')) {
                $table->timestamp('transfer_accepted_at')->nullable()->after('payment_proof');
            }
            if (!Schema::hasColumn('sales', 'transfer_accepted_by')) {
                $table->unsignedBigInteger('transfer_accepted_by')->nullable()->after('transfer_accepted_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn([
                'payment_method',
                'payment_proof',
                'transfer_accepted_at',
                'transfer_accepted_by',
            ]);
        });
    }
};
