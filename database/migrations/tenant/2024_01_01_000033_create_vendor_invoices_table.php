<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Table: vendor_invoices
     * Module: Inward > Finance / Accounts Payable
     * Depends on: vendor_master, grn_headers, purchase_orders, users
     *
     * 3-Way Match: PO + GRN + Vendor Invoice must all align before payment.
     */
    public function up(): void
    {
        Schema::connection('tenant')->create('vendor_invoices', function (Blueprint $table) {
            $table->id();

            // --- Vendor Invoice Reference ---
            $table->string('invoice_number', 80)->comment('Original invoice number from vendor document');
            $table->unsignedBigInteger('vendor_id')->comment('FK → vendor_master');
            $table->date('invoice_date')->comment('Date printed on vendor invoice');
            $table->date('received_date')->comment('Date invoice was received in Finance');

            // --- Links to Operational Docs ---
            $table->unsignedBigInteger('grn_id')->nullable()->comment('FK → grn_headers (primary GRN being matched)');
            $table->unsignedBigInteger('po_id')->comment('FK → purchase_orders (linked PO)');

            // --- Billed Amounts ---
            $table->decimal('subtotal', 15, 2)->comment('Total before taxes and discounts');
            $table->decimal('discount_amount', 12, 2)->default(0)->comment('Trade or negotiated discount');
            $table->decimal('tax_amount', 12, 2)->default(0)->comment('Total GST / VAT billed by vendor');
            $table->decimal('freight_charges', 12, 2)->default(0)->comment('Freight / transport charges on invoice');
            $table->decimal('total_payable', 15, 2)->comment('Final invoice bottom-line amount');

            // --- 3-Way Match Result ---
            $table->enum('match_status', [
                'PENDING',        // Invoice registered, match not yet run
                'MATCHED',        // PO + GRN + Invoice all agree
                'PRICE_VARIANCE', // Invoice price ≠ PO price
                'QTY_VARIANCE',   // Invoice quantity > GRN quantity
                'TAX_VARIANCE',   // GST mismatch
                'BLOCKED',        // Blocked due to unresolved variance
            ])->default('PENDING')->comment('Result of 3-way match verification');
            $table->decimal('variance_amount', 12, 2)->default(0)
                ->comment('Amount of discrepancy found during match');
            $table->text('variance_notes')->nullable()->comment('AP clerk notes on why it was blocked');

            // --- Due Date ---
            $table->date('due_date')->nullable()->comment('Payment due date based on credit terms');

            // --- Payment Status ---
            $table->enum('payment_status', [
                'UNPAID',
                'PARTIALLY_PAID',
                'PAID',
                'ON_HOLD',
            ])->default('UNPAID')->comment('Payment settlement status');

            // --- Accounting ---
            $table->string('journal_ref', 50)->nullable()
                ->comment('Journal reference when GR/IR is cleared and final AP posted');

            // --- Audit ---
            $table->unsignedBigInteger('entered_by')->nullable()->comment('FK → users (AP Clerk)');
            $table->unsignedBigInteger('verified_by')->nullable()->comment('FK → users (Finance Manager)');
            $table->timestamp('verified_at')->nullable();
            $table->softDeletes();
            $table->timestampsTz();

            // Unique constraint: same vendor cannot submit same invoice number twice
            $table->unique(['vendor_id', 'invoice_number'], 'uq_vendor_invoice');

            // Foreign Keys
            $table->foreign('vendor_id')->references('id')->on('vendor_master')->onDelete('restrict');
            $table->foreign('grn_id')->references('id')->on('grn_headers')->onDelete('set null');
            $table->foreign('po_id')->references('id')->on('purchase_orders')->onDelete('restrict');
            $table->foreign('entered_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('verified_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index('vendor_id');
            $table->index('grn_id');
            $table->index('po_id');
            $table->index('match_status');
            $table->index('payment_status');
            $table->index('due_date');
            $table->index('invoice_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('vendor_invoices');
    }
};
