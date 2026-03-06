<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('tenant')->create('vendor_material_map', function (Blueprint $table) {
            $table->id('map_id');
            $table->unsignedBigInteger('vendor_id');
            $table->unsignedBigInteger('material_id');
            $table->string('vendor_material_code', 50)->nullable()->comment("Vendor's own SKU/part number");
            $table->decimal('last_purchase_price', 12, 4)->nullable()->comment('Last transacted price');
            $table->smallInteger('lead_time_days')->nullable()->comment('Vendor-specific lead time');
            $table->decimal('min_order_qty', 12, 3)->nullable()->comment('Minimum order quantity (MOQ)');
            $table->boolean('is_preferred')->default(false)->comment('L1 / preferred vendor flag');
            $table->boolean('is_active')->default(true);
            
            // Foreign keys
            $table->foreign('vendor_id')->references('id')->on('vendor_master')->onDelete('cascade');
            $table->foreign('material_id')->references('id')->on('material_master')->onDelete('cascade');
            
            // Unique constraint
            $table->unique(['vendor_id', 'material_id'], 'unique_vendor_material');
            
            // Indexes
            $table->index('vendor_id');
            $table->index('material_id');
            $table->index('is_preferred');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('vendor_material_map');
    }
};
