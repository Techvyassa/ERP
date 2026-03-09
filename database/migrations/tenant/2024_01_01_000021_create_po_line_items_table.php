<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Table: po_line_items
     * Module: Inward > Procurement
     * Depends on: purchase_orders, material_master, uom_master, gst_taxes
     */
    public function up(): void
    {
        Schema::connection('tenant')->create('po_line_items', function (Blueprint $table) {
            $table->id();

            // --- Parent Reference ---
            $table->unsignedBigInteger('po_id')->comment('FK → purchase_orders');
            $table->unsignedSmallInteger('line_number')->comment('Sequence number within PO (1, 2, 3...)');

            // --- Material ---
            $table->unsignedBigInteger('material_id')->comment('FK → material_master');
            $table->string('material_description', 300)->nullable()->comment('Override description for this line');

            // --- Quantity & UOM ---
            $table->decimal('ordered_qty', 14, 3)->comment('Quantity ordered in PO');
            $table->unsignedBigInteger('uom_id')->comment('FK → uom_master (purchase UOM)');

            // --- Pricing ---
            $table->decimal('unit_price', 14, 4)->comment('Negotiated price per unit');
            $table->decimal('discount_pct', 5, 2)->default(0)->comment('Line-level discount percentage');
            $table->decimal('line_total', 15, 2)->comment('ordered_qty × unit_price - discount');

            // --- Tax ---
            $table->unsignedBigInteger('gst_tax_id')->nullable()->comment('FK → gst_taxes (HSN-based rate)');
            $table->decimal('tax_amount', 12, 2)->default(0)->comment('Computed tax for this line');

            // --- Delivery ---
            $table->date('scheduled_delivery')->nullable()->comment('Line-level delivery date');

            // --- Tolerance (receipt control) ---
            $table->decimal('under_delivery_tolerance', 5, 2)->default(3.00)
                ->comment('% allowed under-delivery before flagging shortage');
            $table->decimal('over_delivery_tolerance', 5, 2)->default(5.00)
                ->comment('% allowed over-delivery before blocking GRN');

            // --- Receipt Tracking ---
            $table->decimal('received_qty', 14, 3)->default(0)->comment('Cumulative qty GRN-posted');
            $table->decimal('pending_qty', 14, 3)->storedAs('ordered_qty - received_qty')
                ->comment('Auto-calculated: ordered - received');
            $table->enum('receipt_status', ['OPEN', 'PARTIAL', 'COMPLETE', 'CLOSED'])
                ->default('OPEN')->comment('Auto-updated on each GRN');

            // --- Audit ---
            $table->timestampsTz();

            // Foreign Keys
            $table->foreign('po_id')->references('id')->on('purchase_orders')->onDelete('cascade');
            $table->foreign('material_id')->references('id')->on('material_master')->onDelete('restrict');
            $table->foreign('uom_id')->references('id')->on('uom_master')->onDelete('restrict');
            $table->foreign('gst_tax_id')->references('id')->on('gst_taxes')->onDelete('set null');

            // Indexes
            $table->index('po_id');
            $table->index('material_id');
            $table->index('receipt_status');
            $table->unique(['po_id', 'line_number'], 'uq_po_line');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('po_line_items');
    }
};
