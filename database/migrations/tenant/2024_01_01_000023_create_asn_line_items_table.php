<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Table: asn_line_items
     * Module: Inward > Advance Shipping Notice
     * Depends on: asn_headers, po_line_items, material_master, uom_master
     */
    public function up(): void
    {
        Schema::connection('tenant')->create('asn_line_items', function (Blueprint $table) {
            $table->id();

            // --- Parent ---
            $table->unsignedBigInteger('asn_id')->comment('FK → asn_headers');
            $table->unsignedBigInteger('po_line_id')->comment('FK → po_line_items');
            $table->unsignedBigInteger('material_id')->comment('FK → material_master');

            // --- Shipment Qty ---
            $table->decimal('shipped_qty', 14, 3)->comment('Quantity vendor has dispatched');
            $table->unsignedBigInteger('uom_id')->comment('FK → uom_master');

            // --- Traceability ---
            $table->string('batch_number', 50)->nullable()->comment('Vendor batch / lot number');
            $table->date('manufacturing_date')->nullable()->comment('Date of manufacture (for FEFO)');
            $table->date('expiry_date')->nullable()->comment('Expiry date of this batch');

            // --- Packaging ---
            $table->string('pallet_id', 50)->nullable()->comment('SSCC / pallet label ID');
            $table->decimal('gross_weight', 10, 3)->nullable()->comment('Total weight including packaging (KG)');
            $table->decimal('net_weight', 10, 3)->nullable()->comment('Net material weight (KG)');

            // --- Audit ---
            $table->timestampsTz();

            // Foreign Keys
            $table->foreign('asn_id')->references('id')->on('asn_headers')->onDelete('cascade');
            $table->foreign('po_line_id')->references('id')->on('po_line_items')->onDelete('restrict');
            $table->foreign('material_id')->references('id')->on('material_master')->onDelete('restrict');
            $table->foreign('uom_id')->references('id')->on('uom_master')->onDelete('restrict');

            // Indexes
            $table->index('asn_id');
            $table->index('material_id');
            $table->index('batch_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('asn_line_items');
    }
};
