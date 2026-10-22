<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('streaming_accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('streaming_accounts', 'account_name')) {
                $table->string('account_name')->nullable()->after('supplier_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('streaming_accounts', function (Blueprint $table) {
            if (Schema::hasColumn('streaming_accounts', 'account_name')) {
                $table->dropColumn('account_name');
            }
        });
    }
};
