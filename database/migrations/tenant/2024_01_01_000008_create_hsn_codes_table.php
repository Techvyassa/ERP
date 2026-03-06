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
        Schema::connection('tenant')->create('hsn_codes', function (Blueprint $table) {
            $table->id();
            $table->string('hsn_code', 10)->unique()->comment('0904, 0906, 2103');
            $table->string('description', 300)->comment('Pepper; Cinnamon; Sauces');
            $table->unsignedBigInteger('default_gst_id');
            $table->boolean('is_active')->default(true)->comment('Active flag');
            
            // Foreign keys
            $table->foreign('default_gst_id')->references('id')->on('gst_taxes')->onDelete('restrict');
            
            // Indexes
            $table->index('hsn_code');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('hsn_codes');
    }
};
