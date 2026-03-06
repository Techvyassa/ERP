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
        Schema::connection('tenant')->create('product_master', function (Blueprint $table) {
            $table->id();
            $table->string('product_code', 30)->unique()->comment('FG-0001');
            $table->string('product_name', 200)->comment('Masala Powder 100g');
            $table->string('product_category', 60)->nullable()->comment('Spice / Blend / Condiment');
            $table->decimal('pack_size', 10, 3)->comment('100, 250, 1000 (per pack_uom)');
            $table->unsignedBigInteger('pack_uom_id');
            $table->unsignedBigInteger('hsn_code_id');
            $table->decimal('standard_cost', 12, 4)->default(0)->comment('Cost per unit');
            $table->decimal('mrp', 12, 2)->nullable()->comment('Maximum retail price');
            $table->boolean('is_active')->default(true);
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->nullable();
            
            // Foreign keys
            $table->foreign('pack_uom_id')->references('id')->on('uom_master')->onDelete('restrict');
            $table->foreign('hsn_code_id')->references('id')->on('hsn_codes')->onDelete('restrict');
            
            // Indexes
            $table->index('product_code');
            $table->index('product_category');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('product_master');
    }
};
