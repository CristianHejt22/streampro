<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. Netflix 4K UHD, Disney+ Premium, Max, Prime Video, Spotify
            $table->string('slug')->unique();
            $table->enum('category', ['video', 'music', 'anime', 'iptv', 'combo', 'other'])->default('video');
            $table->string('color_hex')->default('#E50914');
            $table->string('icon_url')->nullable();
            $table->integer('max_profiles_default')->default(5);
            $table->text('description')->nullable();
            $table->text('default_rules')->nullable(); // Reglas por defecto enviadas al cliente
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
