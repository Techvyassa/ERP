<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Table: pr_line_items
     * Module: Procurement > Purchase Requisition
     * Depends on: purchase_requisitions, material_master, uom_master, warehouse_master
     */
    public function up(): void
    {
        Schema::connection('tenant')->create('pr_line_items', function (Blueprint $table) {
            $table->id();

            // --- Parent Reference ---
            $table->unsignedBigInteger('pr_id')->comment('FK → purchase_requisitions');
            $table->unsignedSmallInteger('line_number')->comment('Sequence within PR (1, 2, 3...)');

            // --- Material ---
            $table->unsignedBigInteger('material_id')->nullable()->comment('FK → material_master (null for free-text items)');
            $table->string('item_name', 200)->comment('Item name / short description');
            $table->text('description')->comment('Detailed specs: model, SKU, material grade, service scope');

            // --- Quantity & UOM ---
            $table->decimal('quantity', 14, 3)->comment('Required quantity');
            $table->unsignedBigInteger('uom_id')->comment('FK → uom_master');

            // --- Pricing (optional at PR stage) ---
            $table->decimal('estimated_unit_price', 14, 4)->nullable()->comment('Based on previous purchases or market research');
            $table->decimal('estimated_total', 15, 2)->storedAs('quantity * estimated_unit_price')
                ->comment('Auto-calculated: quantity × estimated_unit_price');

            // --- Delivery Location ---
            $table->unsignedBigInteger('warehouse_id')->nullable()->comment('FK → warehouse_master; target storage location');

            // --- Purpose ---
            $table->string('purpose', 500)->nullable()->comment('Reason for requesting this specific item');

            // --- Display Order ---
            $table->unsignedInteger('sort_order')->default(0)->comment('Display ordering within the PR');

            // --- Audit ---
            $table->timestampsTz();

            // Foreign Keys
            $table->foreign('pr_id')->references('id')->on('purchase_requisitions')->onDelete('cascade');
            $table->foreign('material_id')->references('id')->on('material_master')->onDelete('restrict');
            $table->foreign('uom_id')->references('id')->on('uom_master')->onDelete('restrict');
            $table->foreign('warehouse_id')->references('id')->on('warehouse_master')->onDelete('set null');

            // Indexes
            $table->index('pr_id');
            $table->index('material_id');
            $table->unique(['pr_id', 'line_number'], 'uq_pr_line');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('pr_line_items');
    }
};
