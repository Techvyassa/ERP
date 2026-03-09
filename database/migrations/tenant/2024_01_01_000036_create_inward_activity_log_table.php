<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Table: inward_activity_log
     * Module: Inward > All (Cross-Module Audit Trail)
     * Depends on: users
     *
     * Records every status change across the inward lifecycle — a single
     * queryable audit trail from GE Number all the way to Payment Cleared.
     * Polymorphic: one row per event, per document type.
     */
    public function up(): void
    {
        Schema::connection('tenant')->create('inward_activity_log', function (Blueprint $table) {
            $table->id();

            // --- Polymorphic Link ---
            $table->string('loggable_type', 80)
                ->comment('Model class name: GateEntry, GrnHeader, InspectionLot, VendorInvoice, etc.');
            $table->unsignedBigInteger('loggable_id')
                ->comment('Primary key of the record being tracked');

            // --- Thread Tracing (GE Number as thread root) ---
            $table->string('ge_number', 30)->nullable()
                ->comment('Gate Entry number — ties all events for a single delivery into one thread');

            // --- Event Details ---
            $table->string('event', 80)
                ->comment('Descriptive event name e.g. gate_entry_created, grn_posted, qc_accepted, payment_cleared');
            $table->string('department', 50)->nullable()
                ->comment('Department that triggered the event: SECURITY / WAREHOUSE / QC / FINANCE');
            $table->string('from_status', 50)->nullable()
                ->comment('Status before the event');
            $table->string('to_status', 50)->nullable()
                ->comment('Status after the event');

            // --- Context ---
            $table->json('metadata')->nullable()
                ->comment('Extra event data: quantities, variance amounts, rejection reasons, etc.');
            $table->text('remarks')->nullable()
                ->comment('Free-text note recorded at the time of the action');

            // --- Audit ---
            $table->unsignedBigInteger('performed_by')->nullable()
                ->comment('FK → users (who triggered the action)');
            $table->string('ip_address', 45)->nullable()
                ->comment('IP of the client session (IPv4 or IPv6)');
            $table->timestampTz('performed_at')->useCurrent()
                ->comment('Exact timestamp of the event');

            // Foreign Keys
            $table->foreign('performed_by')->references('id')->on('users')->onDelete('set null');

            // Indexes — optimised for common query patterns
            $table->index(['loggable_type', 'loggable_id'], 'idx_loggable');
            $table->index('ge_number');
            $table->index('event');
            $table->index('department');
            $table->index('performed_at');
            $table->index('performed_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('inward_activity_log');
    }
};
