<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Table: grn_headers
     * Module: Inward > Warehouse / Finance
     * Depends on: material_receipts, purchase_orders, vendor_master, users
     *
     * The GRN is the formal book entry that legally acknowledges ownership transfer.
     * On save: Dr GR/IR Clearing Account | Cr Accounts Payable
     */
    public function up(): void
    {
        Schema::connection('tenant')->create('grn_headers', function (Blueprint $table) {
            $table->id();

            // --- Reference ---
            $table->string('grn_number', 30)->unique()->comment('GRN/24-25/089 (system-generated, non-editable)');

            // --- Links ---
            $table->unsignedBigInteger('mr_id')->comment('FK → material_receipts (source receipt)');
            $table->unsignedBigInteger('po_id')->comment('FK → purchase_orders');
            $table->unsignedBigInteger('vendor_id')->comment('FK → vendor_master');

            // --- Dates ---
            $table->date('grn_date')->comment('Date goods are officially accepted into books');
            $table->date('posting_date')->comment('Financial ledger posting date (may differ from grn_date)');

            // --- Financial Summary ---
            $table->decimal('total_received_value', 15, 2)->default(0)
                ->comment('Sum of (accepted_qty × unit_price) across all lines');
            $table->decimal('total_tax_amount', 12, 2)->default(0)->comment('Total GST / tax for this GRN');
            $table->decimal('grand_total', 15, 2)->default(0)->comment('Total value including taxes');

            // --- Accounting Reference ---
            $table->string('journal_ref', 50)->nullable()
                ->comment('Accounting entry ID (Dr GR/IR Cr AP) posted on GRN save');

            // --- Status ---
            $table->enum('status', [
                'PROVISIONAL',       // Created after unloading, QC pending
                'QC_PENDING',        // Inspection lot raised, awaiting decision
                'PARTIALLY_ACCEPTED', // Some lines accepted, some rejected
                'ACCEPTED',          // All lines QC approved, stock released
                'REJECTED',          // All material rejected, RTV raised
                'CANCELLED',         // GRN voided before posting
            ])->default('PROVISIONAL')->comment('GRN lifecycle status');

            // --- Audit ---
            $table->text('remarks')->nullable()->comment('Storekeeper or system notes');
            $table->unsignedBigInteger('created_by')->nullable()->comment('FK → users (Storekeeper)');
            $table->unsignedBigInteger('approved_by')->nullable()->comment('FK → users (Store Manager)');
            $table->timestamp('approved_at')->nullable();
            $table->softDeletes();
            $table->timestampsTz();

            // Foreign Keys
            $table->foreign('mr_id')->references('id')->on('material_receipts')->onDelete('restrict');
            $table->foreign('po_id')->references('id')->on('purchase_orders')->onDelete('restrict');
            $table->foreign('vendor_id')->references('id')->on('vendor_master')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index('grn_number');
            $table->index('po_id');
            $table->index('vendor_id');
            $table->index('status');
            $table->index('grn_date');
            $table->index('posting_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('grn_headers');
    }
};
