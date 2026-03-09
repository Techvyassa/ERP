<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Table: putaway_tasks
     * Module: Inward > Warehouse Management
     * Depends on: grn_line_items, material_master, bin_locations, users
     *
     * Auto-generated per GRN line when QC Usage Decision = ACCEPTED.
     */
    public function up(): void
    {
        Schema::connection('tenant')->create('putaway_tasks', function (Blueprint $table) {
            $table->id();

            // --- Reference ---
            $table->string('task_number', 30)->unique()->comment('PT-2425-0001 (system-generated)');

            // --- Links ---
            $table->unsignedBigInteger('grn_line_id')->comment('FK → grn_line_items (source line)');
            $table->unsignedBigInteger('material_id')->comment('FK → material_master');
            $table->string('batch_number', 50)->nullable()->comment('Batch carried from GRN for traceability');

            // --- Quantity ---
            $table->decimal('quantity', 14, 3)->comment('Units to be moved into permanent storage');
            $table->unsignedBigInteger('uom_id')->comment('FK → uom_master');

            // --- Location Movement ---
            $table->unsignedBigInteger('source_bin_id')->nullable()
                ->comment('FK → bin_locations (staging / receiving area bin)');
            $table->unsignedBigInteger('destination_bin_id')->nullable()
                ->comment('FK → bin_locations (final permanent storage bin)');

            // --- Putaway Strategy ---
            $table->enum('strategy', ['MANUAL', 'FIXED_BIN', 'EMPTY_BIN', 'FIFO', 'FEFO'])
                ->default('MANUAL')->comment('Strategy used to determine destination bin');

            // --- Status ---
            $table->enum('status', ['PENDING', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED'])
                ->default('PENDING')->comment('Putaway task status');

            // --- Confirmation ---
            $table->string('bin_scan_confirmed', 30)->nullable()
                ->comment('Scanned bin code to confirm physical placement');
            $table->string('item_scan_confirmed', 100)->nullable()
                ->comment('Scanned item barcode/QR to confirm correct item');
            $table->dateTime('completed_at')->nullable()->comment('Timestamp when putaway was physically confirmed');

            // --- Audit ---
            $table->unsignedBigInteger('assigned_to')->nullable()->comment('FK → users (Warehouse Operator)');
            $table->unsignedBigInteger('completed_by')->nullable()->comment('FK → users (who confirmed the putaway)');
            $table->text('remarks')->nullable();
            $table->timestampsTz();

            // Foreign Keys
            $table->foreign('grn_line_id')->references('id')->on('grn_line_items')->onDelete('cascade');
            $table->foreign('material_id')->references('id')->on('material_master')->onDelete('restrict');
            $table->foreign('uom_id')->references('id')->on('uom_master')->onDelete('restrict');
            $table->foreign('source_bin_id')->references('id')->on('bin_locations')->onDelete('set null');
            $table->foreign('destination_bin_id')->references('id')->on('bin_locations')->onDelete('set null');
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
            $table->foreign('completed_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index('task_number');
            $table->index('grn_line_id');
            $table->index('material_id');
            $table->index('status');
            $table->index('destination_bin_id');
            $table->index('assigned_to');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('putaway_tasks');
    }
};
