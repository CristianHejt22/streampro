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
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('panel_basic_sale_price', 10, 2)->default(4000.00)->after('panel_monthly_fee');
            $table->decimal('panel_pro_sale_price', 10, 2)->default(6000.00)->after('panel_basic_sale_price');
            $table->boolean('offer_panels_on_catalog')->default(true)->after('panel_pro_sale_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['panel_basic_sale_price', 'panel_pro_sale_price', 'offer_panels_on_catalog']);
        });
    }
};
