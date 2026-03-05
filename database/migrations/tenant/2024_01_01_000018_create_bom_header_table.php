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
        Schema::create('bom_header', function (Blueprint $table) {
            $table->id('bom_id');
            $table->string('bom_code', 30)->unique()->comment('BOM-FG001-V2 – unique identifier');
            $table->unsignedBigInteger('product_id');
            $table->smallInteger('version')->default(1)->comment('Version number 1, 2, 3...');
            $table->date('effective_from')->comment('BOM valid from this date');
            $table->date('effective_to')->nullable()->comment('NULL = currently active BOM');
            $table->string('bom_status', 15)->default('DRAFT')->comment('DRAFT / ACTIVE / OBSOLETE');
            $table->decimal('batch_size', 12, 3)->comment('Output quantity per batch run');
            $table->unsignedBigInteger('output_uom_id');
            $table->text('remarks')->nullable()->comment('Change notes, reason for version');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            
            // Foreign keys
            $table->foreign('product_id')->references('product_id')->on('product_master')->onDelete('restrict');
            $table->foreign('output_uom_id')->references('uom_id')->on('uom_master')->onDelete('restrict');
            $table->foreign('created_by')->references('user_id')->on('users')->onDelete('set null');
            $table->foreign('approved_by')->references('user_id')->on('users')->onDelete('set null');
            
            // Unique constraint
            $table->unique(['product_id', 'version'], 'unique_product_version');
            
            // Indexes
            $table->index('bom_code');
            $table->index('product_id');
            $table->index('bom_status');
            $table->index(['effective_from', 'effective_to']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bom_header');
    }
};
