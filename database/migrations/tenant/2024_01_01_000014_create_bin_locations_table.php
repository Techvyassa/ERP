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
        Schema::connection('tenant')->create('bin_locations', function (Blueprint $table) {
            $table->id('bin_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->string('bin_code', 30)->unique()->comment('R01-S02-B03 (Rack-Shelf-Bin)');
            $table->string('aisle', 10)->nullable()->comment('Aisle identifier');
            $table->string('rack', 10)->nullable()->comment('Rack identifier');
            $table->string('shelf', 10)->nullable()->comment('Shelf level');
            $table->decimal('max_weight_kg', 10, 2)->nullable()->comment('Capacity limit in kg');
            $table->boolean('is_active')->default(true);
            
            // Foreign keys
            $table->foreign('warehouse_id')->references('id')->on('warehouse_master')->onDelete('cascade');
            
            // Indexes
            $table->index('warehouse_id');
            $table->index('bin_code');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('bin_locations');
    }
};
