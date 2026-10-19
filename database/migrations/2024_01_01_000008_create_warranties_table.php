<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warranties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('issue_type', ['password_changed', 'account_disabled', 'screen_occupied', 'geo_blocked', 'other'])->default('password_changed');
            $table->text('description')->nullable();
            
            // Cuentas / Perfiles antes y después
            $table->foreignId('old_streaming_account_id')->nullable()->constrained('streaming_accounts')->nullOnDelete();
            $table->foreignId('old_account_profile_id')->nullable()->constrained('account_profiles')->nullOnDelete();
            $table->foreignId('new_streaming_account_id')->nullable()->constrained('streaming_accounts')->nullOnDelete();
            $table->foreignId('new_account_profile_id')->nullable()->constrained('account_profiles')->nullOnDelete();
            
            $table->enum('status', ['pending', 'resolved', 'rejected'])->default('pending');
            $table->text('resolution_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warranties');
    }
};
