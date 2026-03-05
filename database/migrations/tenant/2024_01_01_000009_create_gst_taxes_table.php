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
        Schema::connection('tenant')->create('gst_taxes', function (Blueprint $table) {
            $table->id();
            $table->string('tax_name', 100)->comment('GST @ 12%');
            $table->enum('tax_type', ['CGST_SGST', 'IGST', 'CESS'])->comment('Tax type');
            $table->decimal('cgst_rate', 5, 2)->default(0)->comment('Central GST rate (e.g. 6.00)');
            $table->decimal('sgst_rate', 5, 2)->default(0)->comment('State GST rate (e.g. 6.00)');
            $table->decimal('igst_rate', 5, 2)->default(0)->comment('Interstate GST rate (e.g. 12.00)');
            $table->decimal('cess_rate', 5, 2)->default(0)->comment('CESS rate');
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('tax_type');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('gst_taxes');
    }
};
