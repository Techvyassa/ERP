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
            $table->string('tax_code', 20)->unique()->comment('GST5, GST12, GST18, GST28');
            $table->string('tax_name', 60)->comment('GST @ 12%');
            $table->decimal('cgst_rate', 5, 2)->default(0)->comment('Central GST rate (e.g. 6.00)');
            $table->decimal('sgst_rate', 5, 2)->default(0)->comment('State GST rate (e.g. 6.00)');
            $table->decimal('igst_rate', 5, 2)->default(0)->comment('Interstate GST rate (e.g. 12.00)');
            $table->decimal('ugst_rate', 5, 2)->default(0)->comment('Union Territory GST (0 for most)');
            $table->date('effective_from')->comment('Rate effective from date');
            $table->date('effective_to')->nullable()->comment('NULL = currently active rate');
            $table->boolean('is_active')->default(true)->comment('Active flag');
            
            // Indexes
            $table->index('tax_code');
            $table->index(['effective_from', 'effective_to']);
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
