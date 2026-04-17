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
        Schema::connection('tenant')->table('qc_parameters_master', function (Blueprint $table) {
            $table->decimal('sample_size', 14, 3)->nullable()->after('display_order')->comment('Recommended sample size for this parameter');
        });

        Schema::connection('tenant')->table('inspection_results', function (Blueprint $table) {
            $table->decimal('sample_size', 14, 3)->nullable()->after('observed_value')->comment('Actual sample size used for this test result');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->table('inspection_results', function (Blueprint $table) {
            $table->dropColumn('sample_size');
        });

        Schema::connection('tenant')->table('qc_parameters_master', function (Blueprint $table) {
            $table->dropColumn('sample_size');
        });
    }
};
