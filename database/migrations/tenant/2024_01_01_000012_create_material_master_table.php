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
        Schema::create('material_master', function (Blueprint $table) {
            $table->id('material_id');
            $table->string('material_code', 30)->unique()->comment('RM-0001 – system code');
            $table->string('material_name', 200)->comment('Cinnamon Bark, Dhaniya...');
            $table->string('material_type', 20)->comment('RAW / PACKAGING / CONSUMABLE / SEMI');
            $table->unsignedBigInteger('uom_id')->comment('Stock UOM');
            $table->unsignedBigInteger('purchase_uom_id')->nullable()->comment('Buying UOM (can differ)');
            $table->unsignedBigInteger('hsn_code_id');
            $table->unsignedBigInteger('default_warehouse_id')->nullable();
            $table->decimal('reorder_level', 12, 3)->default(0)->comment('Stock qty triggering auto PR');
            $table->decimal('safety_stock', 12, 3)->default(0)->comment('Minimum buffer stock');
            $table->smallInteger('lead_time_days')->default(0)->comment('Default procurement lead time');
            $table->smallInteger('shelf_life_days')->nullable()->comment('For FEFO expiry tracking');
            $table->boolean('qc_required')->default(true)->comment('Trigger QC on GRN');
            $table->string('inspection_type', 10)->default('AQL')->comment('100PCT / AQL / SKIP');
            $table->boolean('is_batch_tracked')->default(false)->comment('Batch/lot control enabled');
            $table->decimal('standard_cost', 12, 4)->default(0)->comment('Standard cost per base UOM');
            $table->string('valuation_method', 10)->default('FIFO')->comment('FIFO / AVG / STD');
            $table->boolean('is_active')->default(true);
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            
            // Foreign keys
            $table->foreign('uom_id')->references('uom_id')->on('uom_master')->onDelete('restrict');
            $table->foreign('purchase_uom_id')->references('uom_id')->on('uom_master')->onDelete('set null');
            $table->foreign('hsn_code_id')->references('hsn_id')->on('hsn_codes')->onDelete('restrict');
            $table->foreign('default_warehouse_id')->references('warehouse_id')->on('warehouse_master')->onDelete('set null');
            $table->foreign('created_by')->references('user_id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('user_id')->on('users')->onDelete('set null');
            
            // Indexes
            $table->index('material_code');
            $table->index('material_type');
            $table->index('is_active');
            $table->index('qc_required');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_master');
    }
};
