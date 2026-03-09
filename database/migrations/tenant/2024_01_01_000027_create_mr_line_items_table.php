<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Table: mr_line_items
     * Module: Inward > Warehouse
     * Depends on: material_receipts, po_line_items, material_master, uom_master, bin_locations
     */
    public function up(): void
    {
        Schema::connection('tenant')->create('mr_line_items', function (Blueprint $table) {
            $table->id();

            // --- Parent ---
            $table->unsignedBigInteger('mr_id')->comment('FK → material_receipts');
            $table->unsignedBigInteger('po_line_id')->comment('FK → po_line_items');
            $table->unsignedBigInteger('material_id')->comment('FK → material_master');

            // --- Quantities ---
            $table->decimal('received_qty', 14, 3)->comment('Actual quantity physically counted during unloading');
            $table->decimal('shortage_qty', 14, 3)->default(0)
                ->comment('PO qty - received qty when received < ordered (positive value)');
            $table->decimal('excess_qty', 14, 3)->default(0)
                ->comment('Received qty - PO qty when received > ordered (positive value)');
            $table->decimal('rejected_on_arrival', 14, 3)->default(0)
                ->comment('Qty rejected immediately at dock due to visible damage');

            // --- UOM ---
            $table->unsignedBigInteger('uom_id')->comment('FK → uom_master');

            // --- Variance Flags ---
            $table->boolean('shortage_flag')->default(false)
                ->comment('TRUE when shortage exceeds under-delivery tolerance');
            $table->boolean('excess_flag')->default(false)
                ->comment('TRUE when excess exceeds over-delivery tolerance');

            // --- Traceability ---
            $table->string('batch_number', 50)->nullable()->comment('Vendor batch / lot number from packing');
            $table->date('manufacturing_date')->nullable()->comment('Mfg date (from vendor label)');
            $table->date('expiry_date')->nullable()->comment('Expiry date for FEFO tracking');

            // --- Provisional Location ---
            $table->unsignedBigInteger('provisional_bin_id')->nullable()
                ->comment('FK → bin_locations (temporary staging area)');

            // --- Damage ---
            $table->boolean('damage_found')->default(false)->comment('Whether damage was detected on arrival');
            $table->text('damage_remarks')->nullable()->comment('Description of damage observed');
            $table->string('damage_photo_path', 500)->nullable()->comment('File path to damage photo if uploaded');

            // --- Internal Barcode ---
            $table->string('internal_barcode', 100)->nullable()
                ->comment('System-generated barcode / QR for internal tracking');

            // --- Audit ---
            $table->timestampsTz();

            // Foreign Keys
            $table->foreign('mr_id')->references('id')->on('material_receipts')->onDelete('cascade');
            $table->foreign('po_line_id')->references('id')->on('po_line_items')->onDelete('restrict');
            $table->foreign('material_id')->references('id')->on('material_master')->onDelete('restrict');
            $table->foreign('uom_id')->references('id')->on('uom_master')->onDelete('restrict');
            $table->foreign('provisional_bin_id')->references('id')->on('bin_locations')->onDelete('set null');

            // Indexes
            $table->index('mr_id');
            $table->index('material_id');
            $table->index('batch_number');
            $table->index('shortage_flag');
            $table->index('excess_flag');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('mr_line_items');
    }
};
