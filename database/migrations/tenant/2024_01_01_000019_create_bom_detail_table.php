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
        Schema::connection('tenant')->create('bom_detail', function (Blueprint $table) {
            $table->id('bom_detail_id');
            $table->unsignedBigInteger('bom_id');
            $table->unsignedBigInteger('material_id');
            $table->decimal('qty_required', 12, 4)->comment('Required qty per batch_size');
            $table->unsignedBigInteger('uom_id');
            $table->decimal('scrap_percent', 5, 2)->default(0)->comment('Process loss % (e.g. 2.50)');
            $table->decimal('effective_qty', 12, 4)->storedAs('qty_required * (1 + scrap_percent / 100)')->comment('qty × (1 + scrap%/100)');
            $table->unsignedBigInteger('substitute_material_id')->nullable()->comment('Alternate material');
            $table->boolean('is_critical')->default(false)->comment('No substitute allowed if true');
            $table->smallInteger('line_no')->comment('Display sort order');
            $table->string('remarks', 200)->nullable()->comment('Component-level note');
            
            // Foreign keys
            $table->foreign('bom_id')->references('id')->on('bom_header')->onDelete('cascade');
            $table->foreign('material_id')->references('id')->on('material_master')->onDelete('restrict');
            $table->foreign('uom_id')->references('id')->on('uom_master')->onDelete('restrict');
            $table->foreign('substitute_material_id')->references('id')->on('material_master')->onDelete('set null');
            
            // Indexes
            $table->index('bom_id');
            $table->index('material_id');
            $table->index('line_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('bom_detail');
    }
};
