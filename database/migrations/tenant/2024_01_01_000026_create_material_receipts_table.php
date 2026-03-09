<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Table: material_receipts
     * Module: Inward > Warehouse
     * Depends on: gate_entries, purchase_orders, users
     */
    public function up(): void
    {
        Schema::connection('tenant')->create('material_receipts', function (Blueprint $table) {
            $table->id();

            // --- Reference ---
            $table->string('mr_number', 30)->unique()->comment('MR-2425-0001 (system-generated)');

            // --- Links ---
            $table->unsignedBigInteger('ge_id')->comment('FK → gate_entries');
            $table->unsignedBigInteger('po_id')->comment('FK → purchase_orders');
            $table->unsignedBigInteger('vendor_id')->comment('FK → vendor_master');

            // --- Unloading Timing ---
            $table->dateTime('unloading_started_at')->nullable()->comment('When unloading began at dock');
            $table->dateTime('unloading_completed_at')->nullable()->comment('When unloading finished');

            // --- Overall Status ---
            $table->enum('status', [
                'IN_PROGRESS',
                'COMPLETED',
                'PENDING_GRN',
                'GRN_POSTED',
            ])->default('IN_PROGRESS')->comment('MR lifecycle status');

            // --- Audit ---
            $table->text('remarks')->nullable()->comment('Storekeeper unloading notes');
            $table->unsignedBigInteger('created_by')->nullable()->comment('FK → users (Storekeeper)');
            $table->unsignedBigInteger('updated_by')->nullable()->comment('FK → users');
            $table->softDeletes();
            $table->timestampsTz();

            // Foreign Keys
            $table->foreign('ge_id')->references('id')->on('gate_entries')->onDelete('restrict');
            $table->foreign('po_id')->references('id')->on('purchase_orders')->onDelete('restrict');
            $table->foreign('vendor_id')->references('id')->on('vendor_master')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index('mr_number');
            $table->index('ge_id');
            $table->index('po_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('material_receipts');
    }
};
