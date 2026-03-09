<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Table: grn_line_items
     * Module: Inward > Warehouse
     * Depends on: grn_headers, mr_line_items, material_master, uom_master, bin_locations
     */
    public function up(): void
    {
        Schema::connection('tenant')->create('grn_line_items', function (Blueprint $table) {
            $table->id();

            // --- Parent ---
            $table->unsignedBigInteger('grn_id')->comment('FK → grn_headers');
            $table->unsignedBigInteger('mr_line_id')->comment('FK → mr_line_items (source receipt line)');
            $table->unsignedBigInteger('material_id')->comment('FK → material_master');

            // --- Quantities ---
            $table->decimal('accepted_qty', 14, 3)->comment('Formally accepted quantity for this line');
            $table->unsignedBigInteger('uom_id')->comment('FK → uom_master (must match PO)');

            // --- Traceability ---
            $table->string('batch_number', 50)->nullable()->comment('Batch/lot number for expiry/FEFO tracking');
            $table->date('manufacturing_date')->nullable()->comment('Manufacturing date from vendor label');
            $table->date('expiry_date')->nullable()->comment('Expiry date for FEFO bin sequencing');

            // --- Valuation ---
            $table->decimal('unit_price', 14, 4)->comment('Unit price from PO (for stock valuation)');
            $table->decimal('tax_rate', 5, 2)->default(0)->comment('GST rate applied (%)');
            $table->decimal('line_value', 15, 2)->comment('accepted_qty × unit_price');
            $table->decimal('tax_amount', 12, 2)->default(0)->comment('Tax amount for this line');

            // --- Storage Location ---
            $table->unsignedBigInteger('warehouse_bin_id')->nullable()
                ->comment('FK → bin_locations (final storage bin after putaway)');

            // --- Stock Status ---
            $table->enum('stock_status', [
                'RESTRICTED',       // In-quality, awaiting QC
                'UNRESTRICTED',     // QC approved, available for production
                'BLOCKED',          // QC rejected
                'RETURNED',         // RTV completed
            ])->default('RESTRICTED')->comment('Stock movement status');

            // --- Audit ---
            $table->timestampsTz();

            // Foreign Keys
            $table->foreign('grn_id')->references('id')->on('grn_headers')->onDelete('cascade');
            $table->foreign('mr_line_id')->references('id')->on('mr_line_items')->onDelete('restrict');
            $table->foreign('material_id')->references('id')->on('material_master')->onDelete('restrict');
            $table->foreign('uom_id')->references('id')->on('uom_master')->onDelete('restrict');
            $table->foreign('warehouse_bin_id')->references('id')->on('bin_locations')->onDelete('set null');

            // Indexes
            $table->index('grn_id');
            $table->index('material_id');
            $table->index('batch_number');
            $table->index('stock_status');
            $table->index('expiry_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('grn_line_items');
    }
};
