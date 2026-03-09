<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Table: inspection_lots
     * Module: Inward > Quality Control
     * Depends on: grn_headers, material_master, users
     *
     * Auto-created when a GRN is saved (via event listener).
     */
    public function up(): void
    {
        Schema::connection('tenant')->create('inspection_lots', function (Blueprint $table) {
            $table->id();

            // --- Reference ---
            $table->string('lot_number', 30)->unique()->comment('IL-2425-0001 (system-generated on GRN save)');

            // --- Links ---
            $table->unsignedBigInteger('grn_id')->comment('FK → grn_headers (parent GRN)');
            $table->unsignedBigInteger('grn_line_id')->comment('FK → grn_line_items (specific material line)');
            $table->unsignedBigInteger('material_id')->comment('FK → material_master');

            // --- Sampling ---
            $table->decimal('lot_qty', 14, 3)->comment('Total quantity in the lot (from GRN line)');
            $table->decimal('sample_size', 10, 3)->comment('Quantity physically sampled for testing');
            $table->string('sampling_method', 20)->default('AQL')
                ->comment('AQL / 100PCT / SKIP — from material master inspection_type');

            // --- Assignment ---
            $table->unsignedBigInteger('assigned_to')->nullable()
                ->comment('FK → users (QC Technician assigned to this lot)');
            $table->dateTime('due_by')->nullable()->comment('Deadline for completing inspection');

            // --- Status ---
            $table->enum('status', [
                'PENDING',      // Lot created, not yet sampled
                'IN_PROGRESS',  // Sampling started
                'COMPLETED',    // All tests recorded
                'DECISION_MADE', // Usage decision posted
            ])->default('PENDING')->comment('QC lot lifecycle status');

            // --- Audit ---
            $table->text('remarks')->nullable();
            $table->timestampsTz();

            // Foreign Keys
            $table->foreign('grn_id')->references('id')->on('grn_headers')->onDelete('cascade');
            $table->foreign('grn_line_id')->references('id')->on('grn_line_items')->onDelete('cascade');
            $table->foreign('material_id')->references('id')->on('material_master')->onDelete('restrict');
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index('lot_number');
            $table->index('grn_id');
            $table->index('material_id');
            $table->index('status');
            $table->index('assigned_to');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('inspection_lots');
    }
};
