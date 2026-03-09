<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Table: inward_payments
     * Table: return_to_vendor  (RTV)
     * Module: Inward > Finance + Warehouse
     * Depends on: vendor_invoices, vendor_master, grn_line_items, users
     */
    public function up(): void
    {
        // ===================================================================
        // PAYMENTS TABLE
        // Final settlement of vendor liability. Closing entry:
        // Dr Vendor Account | Cr Bank Account
        // ===================================================================
        Schema::connection('tenant')->create('inward_payments', function (Blueprint $table) {
            $table->id();

            // --- Reference ---
            $table->string('payment_reference', 50)->unique()
                ->comment('Internal payment ID (PAY-2425-0001)');
            $table->string('utr_number', 50)->nullable()
                ->comment('Bank UTR / transaction reference number after transfer');

            // --- Links ---
            $table->unsignedBigInteger('invoice_id')->comment('FK → vendor_invoices');
            $table->unsignedBigInteger('vendor_id')->comment('FK → vendor_master');

            // --- Payment Details ---
            $table->enum('payment_method', [
                'NEFT',
                'RTGS',
                'IMPS',
                'CHEQUE',
                'DD',
                'LETTER_OF_CREDIT',
                'ADVANCE'
            ])->comment('Mode of payment transfer');
            $table->string('bank_name', 100)->nullable()->comment('Payer bank name');

            // --- Vendor Bank (auto-fetched from vendor_master) ---
            $table->string('vendor_bank_name', 100)->nullable();
            $table->string('vendor_account_number', 30)->nullable();
            $table->char('vendor_ifsc', 11)->nullable();

            // --- Amounts ---
            $table->decimal('gross_amount', 15, 2)->comment('Full invoice amount before deductions');
            $table->decimal('tds_deduction', 12, 2)->default(0)->comment('Tax Deducted at Source');
            $table->decimal('debit_note_deduction', 12, 2)->default(0)
                ->comment('Deductions for rejections / shortages (debit notes)');
            $table->decimal('advance_adjusted', 12, 2)->default(0)
                ->comment('Advance payment adjusted against this invoice');
            $table->decimal('early_payment_discount', 12, 2)->default(0)
                ->comment('Discount earned for paying before due date');
            $table->decimal('net_paid_amount', 15, 2)->comment('Final amount actually transferred to vendor');

            // --- Dates ---
            $table->date('payment_date')->comment('Date payment was initiated');
            $table->date('value_date')->comment('Date money leaves the company\'s bank account');

            // --- Status ---
            $table->enum('status', [
                'PROPOSED',    // Added to payment run, awaiting approval
                'APPROVED',    // CFO / Finance Manager approved
                'EXECUTED',    // Bank transfer initiated
                'CLEARED',     // UTR confirmed; vendor ledger zeroed
                'FAILED',      // Bank rejected the transfer
                'REVERSED',    // Payment reversed / recalled
            ])->default('PROPOSED')->comment('Payment status');

            // --- Accounting ---
            $table->string('journal_ref', 50)->nullable()
                ->comment('Accounting entry ref: Dr Vendor Account | Cr Bank Account');

            // --- Audit ---
            $table->unsignedBigInteger('proposed_by')->nullable()->comment('FK → users (AP Clerk)');
            $table->unsignedBigInteger('approved_by')->nullable()->comment('FK → users (Finance Manager / CFO)');
            $table->timestamp('approved_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestampsTz();

            // Foreign Keys
            $table->foreign('invoice_id')->references('id')->on('vendor_invoices')->onDelete('restrict');
            $table->foreign('vendor_id')->references('id')->on('vendor_master')->onDelete('restrict');
            $table->foreign('proposed_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index('invoice_id');
            $table->index('vendor_id');
            $table->index('status');
            $table->index('payment_date');
            $table->index('value_date');
        });

        // ===================================================================
        // RETURN TO VENDOR (RTV) TABLE
        // Raised when QC rejects material or excess delivery not accepted.
        // ===================================================================
        Schema::connection('tenant')->create('return_to_vendor', function (Blueprint $table) {
            $table->id();

            // --- Reference ---
            $table->string('rtv_number', 30)->unique()->comment('RTV-2425-0001 (system-generated)');

            // --- Links ---
            $table->unsignedBigInteger('grn_line_id')->comment('FK → grn_line_items (rejected material)');
            $table->unsignedBigInteger('vendor_id')->comment('FK → vendor_master');

            // --- Reason ---
            $table->enum('return_reason', [
                'QC_REJECTED',
                'EXCESS_DELIVERY',
                'WRONG_MATERIAL',
                'DAMAGED_ON_ARRIVAL',
                'EXPIRED',
            ])->comment('Why the material is being returned');
            $table->text('rejection_details')->nullable()->comment('Detailed description of rejection');

            // --- Quantities ---
            $table->decimal('return_qty', 14, 3)->comment('Quantity being returned to vendor');
            $table->unsignedBigInteger('uom_id')->comment('FK → uom_master');
            $table->string('batch_number', 50)->nullable()->comment('Batch of the returned material');

            // --- Resolution ---
            $table->enum('resolution_type', [
                'REPLACE',       // Vendor will send replacement material
                'CREDIT_NOTE',   // Vendor will issue a credit note
                'DEBIT_NOTE',    // Company raises debit note on vendor
            ])->nullable()->comment('Agreed resolution with vendor');
            $table->string('credit_note_number', 50)->nullable()
                ->comment('Vendor credit note reference if applicable');

            // --- Status ---
            $table->enum('status', [
                'PENDING_DISPATCH',  // Material awaiting pickup
                'DISPATCHED',        // Returned goods sent back
                'ACKNOWLEDGED',      // Vendor confirmed receipt
                'RESOLVED',          // Credit / replacement received
            ])->default('PENDING_DISPATCH')->comment('RTV status');

            // --- Dates ---
            $table->date('return_date')->nullable()->comment('Date goods were physically dispatched back');

            // --- Invoice Impact ---
            $table->boolean('invoice_hold')->default(true)
                ->comment('Whether vendor invoice payment is on hold until RTV resolved');

            // --- Audit ---
            $table->unsignedBigInteger('raised_by')->nullable()->comment('FK → users (QC Manager / Storekeeper)');
            $table->text('remarks')->nullable();
            $table->softDeletes();
            $table->timestampsTz();

            // Foreign Keys
            $table->foreign('grn_line_id')->references('id')->on('grn_line_items')->onDelete('restrict');
            $table->foreign('vendor_id')->references('id')->on('vendor_master')->onDelete('restrict');
            $table->foreign('uom_id')->references('id')->on('uom_master')->onDelete('restrict');
            $table->foreign('raised_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index('rtv_number');
            $table->index('vendor_id');
            $table->index('status');
            $table->index('return_reason');
            $table->index('invoice_hold');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('return_to_vendor');
        Schema::connection('tenant')->dropIfExists('inward_payments');
    }
};
