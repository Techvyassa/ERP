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
        Schema::connection('tenant')->create('uom_master', function (Blueprint $table) {
            $table->id();
            $table->string('uom_code', 10)->unique()->comment('KG, GM, LTR, PCS, BAG');
            $table->string('uom_name', 50)->comment('Kilogram, Gram, Litre...');
            $table->string('uom_type', 20)->comment('weight / volume / qty / length');
            $table->unsignedBigInteger('base_uom_id')->nullable()->comment('Self-ref for conversion');
            $table->decimal('conversion_factor', 12, 6)->default(1)->comment('1 GM = 0.001 KG');
            $table->boolean('is_active')->default(true);
            
            // Indexes
            $table->index('uom_code');
            $table->index('uom_type');
            $table->index('is_active');
        });
        
        // Add self-referencing foreign key after table creation
        Schema::connection('tenant')->table('uom_master', function (Blueprint $table) {
            $table->foreign('base_uom_id')->references('id')->on('uom_master')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('uom_master');
    }
};
