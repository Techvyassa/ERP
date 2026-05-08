<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * so_box_lines — records each box packed during the packing workflow.
     * One SO can have many box lines across multiple boxes.
     * No bin required — packing happens at a staging/dispatch area.
     */
    public function up(): void
    {
        Schema::connection('tenant')->create('so_box_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('so_id');
            $table->string('box_no', 60)->comment('Box identifier entered/scanned by packer');
            $table->unsignedBigInteger('product_id');
            $table->decimal('qty', 12, 3);
            $table->unsignedBigInteger('packed_by')->nullable()->comment('User who performed the packing');
            $table->timestampsTz();

            $table->foreign('so_id')->references('id')->on('sales_orders')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('product_master')->onDelete('restrict');
            $table->foreign('packed_by')->references('id')->on('users')->onDelete('set null');

            $table->index('so_id');
            $table->index('box_no');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('so_box_lines');
    }
};
