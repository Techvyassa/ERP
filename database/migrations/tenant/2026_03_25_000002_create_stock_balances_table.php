<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * TABLE: stock_balances
     * PURPOSE: Read-Model / Materialized Cache of current stock per item per bucket per bin.
     * Querying this table is O(1) or O(bins) rather than O(all transactions ever).
     *
     * RULE: Never manually update this table. It is ONLY modified by StockService::post()
     * and StockService::transfer(), which run inside the same DB transaction as the
     * inventory_transactions insert.
     *
     * One row = one unique combination of (material|product, batch, bucket, warehouse, bin).
     */
    public function up(): void
    {
        Schema::connection('tenant')->create('stock_balances', function (Blueprint $table) {
            $table->id();

            // --- Item Identity (one of material or product, not both) ---
            $table->unsignedBigInteger('material_id')->nullable()->comment('FK → material_master');
            $table->unsignedBigInteger('product_id')->nullable()->comment('FK → product_master');
            $table->string('batch_number', 50)->nullable()->comment('Batch/lot grouping');

            // --- Stock Bucket (split by purpose) ---
            $table->enum('bucket', [
                'QC_HOLD',
                'PUTAWAY_PENDING',
                'AVAILABLE',
                'RESERVED',
                'BLOCKED',
                'CONSUMED',
                'SHIPPED',
                'RETURNED',
                'ADJUSTMENT',
            ])->comment('Mirrors inventory_transactions.bucket enum');

            // --- Location ---
            $table->unsignedBigInteger('warehouse_id')->comment('FK → warehouse_master');
            $table->unsignedBigInteger('bin_id')->nullable()->comment('FK → bin_locations (null = warehouse-level balance)');

            // --- Running Balance ---
            $table->decimal('qty_on_hand', 14, 3)->default(0)
                ->comment('Current physical qty in this bucket/bin combination');
            $table->decimal('qty_reserved', 14, 3)->default(0)
                ->comment('Qty committed (sales orders, production orders) — subset of AVAILABLE');

            // --- UOM ---
            $table->unsignedBigInteger('uom_id')->comment('FK → uom_master');

            // --- Valuation Cache ---
            $table->decimal('avg_cost', 14, 4)->nullable()
                ->comment('Moving average cost — updated on each inflow transaction');

            // --- Sync ---
            $table->timestamp('last_transaction_at')->nullable()
                ->comment('Timestamp of the last inventory_transaction that updated this row');

            // --- Audit ---
            $table->timestampsTz();

            // Foreign Keys
            $table->foreign('material_id')->references('id')->on('material_master')->onDelete('cascade');
            $table->foreign('warehouse_id')->references('id')->on('warehouse_master')->onDelete('restrict');
            $table->foreign('bin_id')->references('id')->on('bin_locations')->onDelete('set null');
            $table->foreign('uom_id')->references('id')->on('uom_master')->onDelete('restrict');

            // UNIQUE constraint: one balance row per (item, batch, bucket, warehouse, bin) combination
            $table->unique(
                ['material_id', 'product_id', 'batch_number', 'bucket', 'warehouse_id', 'bin_id'],
                'stock_balances_unique_key'
            );

            // Indexes — optimized for stock query patterns
            $table->index('material_id');
            $table->index('product_id');
            $table->index(['warehouse_id', 'bucket']);
            $table->index('batch_number');
            $table->index('bin_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('stock_balances');
    }
};
