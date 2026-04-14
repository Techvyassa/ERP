<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add product_id to qc_parameters_master so FG (Finished Goods) QC
     * parameters can be configured per product, not just per material.
     *
     * Rule: a row must have EITHER material_id OR product_id (not both).
     * - material_id → used for inbound GRN QC
     * - product_id  → used for production FG QC
     */
    public function up(): void
    {
        Schema::connection('tenant')->table('qc_parameters_master', function (Blueprint $table) {
            // Make material_id nullable (was NOT NULL before)
            $table->unsignedBigInteger('material_id')->nullable()->change();

            // Add product_id for FG QC parameters
            $table->unsignedBigInteger('product_id')->nullable()->after('material_id')
                ->comment('FK → product_master — set for FG QC parameters');

            $table->foreign('product_id')->references('id')->on('product_master')->onDelete('cascade');
            $table->index('product_id');

            // Unique: one parameter code per product
            $table->unique(['product_id', 'parameter_code'], 'uq_product_param_code');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->table('qc_parameters_master', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropIndex(['product_id']);
            $table->dropUnique('uq_product_param_code');
            $table->dropColumn('product_id');

            // Restore material_id as NOT NULL
            $table->unsignedBigInteger('material_id')->nullable(false)->change();
        });
    }
};
