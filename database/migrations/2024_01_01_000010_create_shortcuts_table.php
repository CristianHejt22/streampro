<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shortcuts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('url');
            $table->string('icon')->default('link'); // lucide icon identifier
            $table->string('category')->default('General');
            $table->string('color')->default('#6366f1');
            $table->string('badge')->nullable();
            $table->boolean('is_external')->default(true);
            $table->integer('order_index')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shortcuts');
    }
};
