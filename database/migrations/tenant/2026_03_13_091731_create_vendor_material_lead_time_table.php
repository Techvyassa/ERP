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
        Schema::connection('tenant')->create('vendor_material_lead_time', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_id');
            $table->unsignedBigInteger('material_id');
            $table->smallInteger('lead_time_days')->comment('Lead time in days for this material');
            $table->decimal('min_order_qty', 12, 3)->nullable()->comment('Minimum order quantity for this lead time');
            $table->date('valid_from')->nullable()->comment('Validity start date (for seasonal rates)');
            $table->date('valid_to')->nullable()->comment('Validity end date (for seasonal rates)');
            $table->boolean('is_active')->default(true);
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->nullable();
            
            // Foreign keys
            $table->foreign('vendor_id')->references('id')->on('vendor_master')->onDelete('cascade');
            $table->foreign('material_id')->references('id')->on('material_master')->onDelete('cascade');
            
            // Unique constraint for vendor-material combination
            $table->unique(['vendor_id', 'material_id'], 'unique_vendor_material_lead_time');
            
            // Indexes
            $table->index('vendor_id');
            $table->index('material_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('vendor_material_lead_time');
    }
};
