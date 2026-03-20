<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Table: putaway_lines
     * Module: Inward > Warehouse Management
     * Depends on: putaway_tasks, materials
     *
     * Stores individual line items for putaway tasks with batch tracking.
     */
    public function up(): void
    {
        Schema::connection('tenant')->create('putaway_lines', function (Blueprint $table) {
            $table->id();

            // --- Reference ---
            $table->unsignedBigInteger('putaway_task_id')->comment('FK → putaway_tasks');
            $table->unsignedSmallInteger('line_number')->comment('Line sequence number');

            // --- Batch Tracking ---
            $table->string('batch_number', 50)->nullable()->comment('Batch number for traceability');
            $table->string('serial_numbers', 500)->nullable()->comment('Comma-separated serial numbers');

            // --- Quantity ---
            $table->decimal('quantity', 14, 3)->comment('Units in this line');
            $table->unsignedBigInteger('uom_id')->comment('FK → uom_master');

            // --- Status ---
            $table->enum('status', ['PENDING', 'COMPLETED', 'CANCELLED'])->default('PENDING');

            // --- Audit ---
            $table->unsignedBigInteger('created_by')->nullable()->comment('FK → users');
            $table->unsignedBigInteger('updated_by')->nullable()->comment('FK → users');
            $table->timestampsTz();

            // Foreign Keys
            $table->foreign('putaway_task_id')->references('id')->on('putaway_tasks')->onDelete('cascade');
            $table->foreign('uom_id')->references('id')->on('uom_master')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index('putaway_task_id');
            $table->index('line_number');
            $table->index('batch_number');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('putaway_lines');
    }
};
