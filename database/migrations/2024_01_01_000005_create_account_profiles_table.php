<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('streaming_account_id')->constrained('streaming_accounts')->cascadeOnDelete();
            $table->unsignedInteger('profile_number')->default(1);
            $table->string('profile_name')->default('Pantalla 1');
            $table->string('pin', 10)->nullable(); // e.g. "1234"
            $table->enum('status', ['available', 'assigned', 'in_review', 'disabled'])->default('available');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_profiles');
    }
};
