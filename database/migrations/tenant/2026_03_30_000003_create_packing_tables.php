<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->create('packing_orders', function (Blueprint $table) {
            $table->id();
            $table->string('packing_order_no', 30)->unique();
            $table->unsignedBigInteger('production_order_id');
            $table->enum('status', ['PENDING', 'IN_PROGRESS', 'COMPLETED'])->default('PENDING');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestampsTz();

            $table->foreign('production_order_id')->references('id')->on('production_orders')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            $table->index('production_order_id');
            $table->index('status');
        });

        Schema::connection('tenant')->create('cartons', function (Blueprint $table) {
            $table->id();
            $table->string('carton_barcode', 100)->unique();
            $table->unsignedBigInteger('packing_order_id');
            $table->enum('carton_type', ['INNER', 'OUTER', 'PALLET'])->default('OUTER');
            $table->unsignedBigInteger('parent_carton_id')->nullable();
            $table->enum('status', ['OPEN', 'SEALED', 'LABELLED', 'DISPATCHED'])->default('OPEN');
            $table->decimal('calculated_weight', 8, 3)->nullable();
            $table->decimal('actual_weight', 8, 3)->nullable();
            $table->timestampTz('sealed_at')->nullable();
            $table->timestampTz('labelled_at')->nullable();
            $table->timestampsTz();

            $table->foreign('packing_order_id')->references('id')->on('packing_orders')->onDelete('cascade');
            $table->foreign('parent_carton_id')->references('id')->on('cartons')->onDelete('set null');

            $table->index('packing_order_id');
            $table->index('status');
        });

        Schema::connection('tenant')->create('carton_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('carton_id');
            $table->unsignedBigInteger('product_id');
            $table->string('product_barcode', 100);
            $table->decimal('qty', 12, 3);
            $table->unsignedBigInteger('uom_id');
            $table->string('batch_number', 50)->nullable();
            $table->timestampTz('scanned_at');
            $table->unsignedBigInteger('scanned_by')->nullable();
            $table->timestampsTz();

            $table->foreign('carton_id')->references('id')->on('cartons')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('product_master')->onDelete('restrict');
            $table->foreign('uom_id')->references('id')->on('uom_master')->onDelete('restrict');
            $table->foreign('scanned_by')->references('id')->on('users')->onDelete('set null');

            $table->index('carton_id');
            $table->index('product_id');
            $table->index('batch_number');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('carton_items');
        Schema::connection('tenant')->dropIfExists('cartons');
        Schema::connection('tenant')->dropIfExists('packing_orders');
    }
};
