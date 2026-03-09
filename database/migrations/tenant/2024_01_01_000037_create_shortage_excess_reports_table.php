<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Table: shortage_excess_reports
     * Module: Inward > Warehouse (Short & Excess Tracking)
     * Depends on: mr_line_items, po_line_items, material_master, vendor_master, users
     *
     * One row per short/excess event captured at Material Receipt.
     * Used by Procurement for vendor performance tracking and Finance to block/adjust invoices.
     */
    public function up(): void
    {
        Schema::connection('tenant')->create('shortage_excess_reports', function (Blueprint $table) {
            $table->id();

            // --- Reference ---
            $table->string('report_number', 30)->unique()
                ->comment('SER-2425-0001 (system-generated on MR submission)');

            // --- Links ---
            $table->unsignedBigInteger('mr_line_id')->comment('FK → mr_line_items (the receipt line with variance)');
            $table->unsignedBigInteger('po_line_id')->comment('FK → po_line_items (original ordered line)');
            $table->unsignedBigInteger('material_id')->comment('FK → material_master');
            $table->unsignedBigInteger('vendor_id')->comment('FK → vendor_master');

            // --- Variance Details ---
            $table->enum('variance_type', ['SHORTAGE', 'EXCESS'])
                ->comment('Whether this is an under-delivery or over-delivery');
            $table->decimal('po_qty', 14, 3)->comment('Ordered quantity from PO');
            $table->decimal('received_qty', 14, 3)->comment('Actual quantity counted at unloading');
            $table->decimal('variance_qty', 14, 3)
                ->comment('Absolute difference: |received_qty - po_qty|');
            $table->decimal('variance_pct', 6, 2)
                ->comment('Variance as % of PO qty: (variance_qty / po_qty) × 100');
            $table->unsignedBigInteger('uom_id')->comment('FK → uom_master');

            // --- Tolerance Check ---
            $table->decimal('tolerance_pct', 5, 2)
                ->comment('Applicable tolerance % (under or over) from material master at time of receipt');
            $table->boolean('within_tolerance')->default(false)
                ->comment('TRUE if variance_pct <= tolerance_pct');

            // --- Resolution ---
            $table->enum('resolution_status', [
                'OPEN',                 // Reported, no action yet
                'NOTIFIED_VENDOR',      // Procurement sent short delivery notice
                'BALANCE_DELIVERED',    // Vendor delivered balance quantity (for SHORT)
                'CREDIT_NOTE_RECEIVED', // Vendor issued credit note for undelivered qty
                'PO_AMENDED',           // PO quantity increased to cover excess (for EXCESS)
                'EXCESS_RETURNED',      // Excess material returned to vendor (RTV raised)
                'CLOSED_ACCEPTABLE',    // Within tolerance, accepted and closed
            ])->default('OPEN')->comment('Resolution tracking status');

            // --- Financial Impact ---
            $table->boolean('invoice_blocked')->default(false)
                ->comment('TRUE if variance caused the related vendor invoice to be payment-blocked');
            $table->decimal('financial_impact', 12, 2)->nullable()
                ->comment('Monetary value of the variance (variance_qty × unit_price)');

            // --- Notices ---
            $table->string('notice_reference', 80)->nullable()
                ->comment('Short Delivery Notice or Excess Delivery Notice document number');
            $table->date('notice_sent_date')->nullable()
                ->comment('Date Procurement sent the notice to vendor');
            $table->date('resolution_due_date')->nullable()
                ->comment('Expected resolution by date from vendor');

            // --- Audit ---
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('reported_by')->nullable()
                ->comment('FK → users (Storekeeper who recorded the discrepancy)');
            $table->unsignedBigInteger('resolved_by')->nullable()
                ->comment('FK → users (Procurement/Finance who closed the report)');
            $table->timestamp('resolved_at')->nullable();
            $table->softDeletes();
            $table->timestampsTz();

            // Foreign Keys
            $table->foreign('mr_line_id')->references('id')->on('mr_line_items')->onDelete('restrict');
            $table->foreign('po_line_id')->references('id')->on('po_line_items')->onDelete('restrict');
            $table->foreign('material_id')->references('id')->on('material_master')->onDelete('restrict');
            $table->foreign('vendor_id')->references('id')->on('vendor_master')->onDelete('restrict');
            $table->foreign('uom_id')->references('id')->on('uom_master')->onDelete('restrict');
            $table->foreign('reported_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('resolved_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index('report_number');
            $table->index('vendor_id');
            $table->index('material_id');
            $table->index('variance_type');
            $table->index('resolution_status');
            $table->index('invoice_blocked');
            $table->index('within_tolerance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('shortage_excess_reports');
    }
};
