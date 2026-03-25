<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * TABLE: inventory_transactions
     * PURPOSE: Immutable ledger — the single "Source of Truth" for all stock movements.
     * Every quantity change in the warehouse must produce exactly ONE row here.
     * This table is NEVER updated; only INSERTed into.
     *
     * This solves the "Missing Ledger" anti-pattern where GRN edits would silently
     * overwrite history. With this table, every stock change is permanently traceable.
     */
    public function up(): void
    {
        Schema::connection('tenant')->create('inventory_transactions', function (Blueprint $table) {
            $table->id();

            // --- Item Identity ---
            $table->unsignedBigInteger('material_id')->nullable()->comment('FK → material_master (null if product)');
            $table->unsignedBigInteger('product_id')->nullable()->comment('FK → product_master (null if material)');
            $table->string('batch_number', 50)->nullable()->comment('Batch/lot for expiry tracking');

            // --- Stock Bucket (Where it Lives) ---
            $table->enum('bucket', [
                'QC_HOLD',          // Received at dock, awaiting QC inspection
                'PUTAWAY_PENDING',  // QC passed, on forklift / staging area, not yet shelved
                'AVAILABLE',        // Physically on shelf, unrestricted — can be sold or issued
                'RESERVED',         // Committed to a Sales Order / Production Order, not yet picked
                'BLOCKED',          // QC rejected, cannot be used or sold
                'CONSUMED',         // Issued to Production (Material Requisition)
                'SHIPPED',          // Dispatched against Sales Order
                'RETURNED',         // RTV (Return to Vendor) completed
                'ADJUSTMENT',       // Ad-hoc stock take / correction
            ])->comment('The stock state bucket this transaction affects');

            // --- Quantity (Signed: positive = inflow, negative = outflow) ---
            $table->decimal('qty_change', 14, 3)->comment('Signed delta: +ve for inflow, -ve for outflow');
            $table->unsignedBigInteger('uom_id')->comment('FK → uom_master');

            // --- Location ---
            $table->unsignedBigInteger('warehouse_id')->comment('FK → warehouse_master');
            $table->unsignedBigInteger('bin_id')->nullable()->comment('FK → bin_locations (null if bin unknown at time of posting)');

            // --- Transaction Type (Why it Changed) ---
            $table->enum('transaction_type', [
                'GRN_RECEIPT',          // Stock arrives at dock from GRN; goes into QC_HOLD
                'QC_PASS',              // QC approved; moves QC_HOLD → PUTAWAY_PENDING
                'QC_REJECT',            // QC rejected; moves QC_HOLD → BLOCKED
                'PUTAWAY_COMPLETE',     // Forklift confirms shelf placement; moves PUTAWAY_PENDING → AVAILABLE
                'SALES_RESERVE',        // Sales order placed; moves AVAILABLE → RESERVED
                'SALES_SHIP',           // Goods dispatched; moves RESERVED → SHIPPED
                'PRODUCTION_ISSUE',     // Material requisition; moves AVAILABLE → CONSUMED
                'PRODUCTION_RECEIPT',   // Finished goods from production; creates AVAILABLE (product)
                'RETURN_TO_VENDOR',     // RTV completed; removes BLOCKED stock
                'STOCK_ADJUSTMENT',     // Physical count / correction (can be +/-)
                'TRANSFER',             // Bin-to-bin transfer within warehouse
                'CANCELLATION',         // Reversal of a prior transaction (GRN cancelled etc.)
            ])->comment('Business reason for this stock change');

            // --- Source Document (Traceability) ---
            $table->string('reference_type', 50)->nullable()
                ->comment('Polymorphic: "GRN", "PutawayTask", "SalesOrder", "ProductionOrder"');
            $table->unsignedBigInteger('reference_id')->nullable()
                ->comment('ID of the source document in the referenced table');
            $table->string('reference_number', 50)->nullable()
                ->comment('Human-readable reference, e.g., GRN/25-26/001 — denormalized for fast display');

            // --- Valuation ---
            $table->decimal('unit_cost', 14, 4)->nullable()->comment('Cost per unit at time of transaction');
            $table->decimal('total_cost', 15, 2)->nullable()->comment('qty_change × unit_cost (informational)');

            // --- Audit ---
            $table->unsignedBigInteger('created_by')->nullable()->comment('FK → users (who triggered the action)');
            $table->text('remarks')->nullable()->comment('Free-text notes for this transaction');
            $table->timestampsTz();

            // Foreign Keys
            $table->foreign('material_id')->references('id')->on('material_master')->onDelete('restrict');
            $table->foreign('uom_id')->references('id')->on('uom_master')->onDelete('restrict');
            $table->foreign('warehouse_id')->references('id')->on('warehouse_master')->onDelete('restrict');
            $table->foreign('bin_id')->references('id')->on('bin_locations')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            // Indexes — optimized for the most common query patterns
            $table->index('material_id');
            $table->index('product_id');
            $table->index('bucket');
            $table->index('transaction_type');
            $table->index(['reference_type', 'reference_id']);
            $table->index('warehouse_id');
            $table->index('batch_number');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('inventory_transactions');
    }
};
