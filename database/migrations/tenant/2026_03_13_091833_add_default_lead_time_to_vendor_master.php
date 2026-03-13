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
        Schema::connection('tenant')->table('vendor_master', function (Blueprint $table) {
            $table->smallInteger('default_lead_time_days')->default(30)->after('delivery_terms')->comment('Default lead time in days (fallback for materials without specific lead time)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->table('vendor_master', function (Blueprint $table) {
            $table->dropColumn('default_lead_time_days');
        });
    }
};
