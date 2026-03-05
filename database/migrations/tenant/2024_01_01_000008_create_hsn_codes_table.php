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
            $table->string('hsn_code', 20)->unique()->comment('0904, 0906, 2103');
            $table->string('hsn_description', 255)->comment('Pepper; Cinnamon; Sauces');
            $table->decimal('gst_rate', 5, 2)->nullable()->comment('Default GST rate percentage');
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
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
