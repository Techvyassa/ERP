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
        Schema::create('uom_master', function (Blueprint $table) {
            $table->id('uom_id');
            $table->string('uom_code', 10)->unique()->comment('KG, GM, LTR, PCS, BAG');
            $table->string('uom_name', 50)->comment('Kilogram, Gram, Litre...');
            $table->string('uom_type', 20)->comment('weight / volume / qty / length');
            $table->unsignedBigInteger('base_uom_id')->nullable()->comment('Self-ref for conversion');
            $table->decimal('conversion_factor', 12, 6)->default(1)->comment('1 GM = 0.001 KG');
            $table->boolean('is_active')->default(true);
            
            // Foreign keys
            $table->foreign('base_uom_id')->references('uom_id')->on('uom_master')->onDelete('set null');
            
            // Indexes
            $table->index('uom_code');
            $table->index('uom_type');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uom_master');
    }
};
