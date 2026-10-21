<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('renewal_requests', function (Blueprint $table) {
            $table->boolean('review_requested_to_provider')->default(false)->after('status');
            $table->unsignedBigInteger('provider_id')->nullable()->after('review_requested_to_provider');
            $table->text('provider_review_notes')->nullable()->after('provider_id');
            $table->timestamp('provider_review_requested_at')->nullable()->after('provider_review_notes');
            $table->text('provider_response_notes')->nullable()->after('provider_review_requested_at');

            $table->foreign('provider_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('renewal_requests', function (Blueprint $table) {
            $table->dropForeign(['provider_id']);
            $table->dropColumn([
                'review_requested_to_provider',
                'provider_id',
                'provider_review_notes',
                'provider_review_requested_at',
                'provider_response_notes',
            ]);
        });
    }
};
