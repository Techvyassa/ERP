<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * so_pick_lines — records each scanned pick action during the Start Picking workflow.
     * One SO can have many pick lines across multiple pallets.
     */
    public function up(): void
    {
        Schema::connection('tenant')->create('so_pick_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('so_id');
            $table->string('pallet_no', 60)->comment('Pallet identifier entered/scanned by picker');
            $table->unsignedBigInteger('bin_id')->nullable()->comment('Source bin location');
            $table->string('bin_code', 30)->comment('Denormalised bin code for quick display');
            $table->unsignedBigInteger('product_id');
            $table->decimal('qty', 12, 3);
            $table->unsignedBigInteger('picked_by')->nullable()->comment('User who performed the pick');
            $table->timestampsTz();

            $table->foreign('so_id')->references('id')->on('sales_orders')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('product_master')->onDelete('restrict');
            $table->foreign('bin_id')->references('id')->on('bin_locations')->onDelete('set null');
            $table->foreign('picked_by')->references('id')->on('users')->onDelete('set null');

            $table->index('so_id');
            $table->index('pallet_no');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('so_pick_lines');
    }
};
