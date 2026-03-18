<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Table: qc_parameters_master
     * Module: Inward > Quality Control (Master Data)
     * Depends on: material_master
     *
     * Defines the standard test specifications for each material.
     * Used to auto-populate inspection_results when a new lot is created.
     */
    public function up(): void
    {
        Schema::connection('tenant')->create('qc_parameters_master', function (Blueprint $table) {
            $table->id();

            // --- Scope ---
            $table->unsignedBigInteger('material_id')->comment('FK → material_master');
            $table->string('parameter_code', 50)->comment('API-friendly code: e.g. PURITY, MOISTURE');
            $table->string('parameter_name', 100)->comment('e.g. Purity %, Moisture, Hardness, Tensile Strength');
            $table->string('parameter_category', 50)->nullable()
                ->comment('PHYSICAL / CHEMICAL / MICROBIOLOGICAL / DIMENSIONAL');

            // --- Data Type ---
            $table->enum('data_type', ['NUMERIC', 'TEXT', 'BOOLEAN'])->default('NUMERIC')
                ->comment('Type of QC measurement');
            $table->enum('tolerance_type', ['RANGE', 'MIN_ONLY', 'MAX_ONLY', 'EXACT'])->default('RANGE')
                ->comment('How the acceptance criteria is evaluated');

            // --- Specification Limits ---
            $table->string('standard_min', 50)->nullable()->comment('Minimum acceptable value');
            $table->string('standard_max', 50)->nullable()->comment('Maximum acceptable value');
            $table->string('standard_value', 100)->nullable()
                ->comment('Exact target value (e.g. 99.5%) where range does not apply');
            $table->string('unit_of_measurement', 30)->nullable()->comment('e.g. %, mm, kN/m², CFU/g');

            // --- Test Method ---
            $table->string('test_method', 100)->nullable()
                ->comment('Standard method reference e.g. IS:1367, ASTM D638');
            $table->boolean('is_critical')->default(false)
                ->comment('TRUE = a FAIL on this param auto-rejects the whole lot');

            // --- Ordering ---
            $table->unsignedSmallInteger('display_order')->default(0)
                ->comment('Sequence order on QC inspection form');

            // --- Status ---
            $table->boolean('is_active')->default(true);

            // --- Audit ---
            $table->unsignedBigInteger('created_by')->nullable()->comment('FK → users (QC Manager who set up spec)');
            $table->timestampsTz();

            // Foreign Keys
            $table->foreign('material_id')->references('id')->on('material_master')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index('material_id');
            $table->index('is_active');
            $table->index('is_critical');
            $table->unique(['material_id', 'parameter_code'], 'uq_material_param_code');
            $table->unique(['material_id', 'parameter_name'], 'uq_material_parameter');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('qc_parameters_master');
    }
};
