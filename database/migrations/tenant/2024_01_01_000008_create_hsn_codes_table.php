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
        Schema::create('hsn_codes', function (Blueprint $table) {
            $table->id('hsn_id');
            $table->string('hsn_code', 10)->unique()->comment('0904, 0906, 2103');
            $table->string('description', 300)->comment('Pepper; Cinnamon; Sauces');
            $table->unsignedBigInteger('default_gst_id')->nullable();
            $table->boolean('is_active')->default(true);
            
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
        Schema::dropIfExists('hsn_codes');
    }
};
