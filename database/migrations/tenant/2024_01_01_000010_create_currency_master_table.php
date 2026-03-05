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
        Schema::connection('tenant')->create('currency_master', function (Blueprint $table) {
            $table->id();
            $table->string('currency_code', 10)->unique()->comment('INR, USD, EUR, AED, SGD');
            $table->string('currency_name', 100)->comment('Indian Rupee, US Dollar...');
            $table->string('currency_symbol', 10)->comment('₹, $, €');
            $table->decimal('exchange_rate', 12, 4)->default(1)->comment('Rate vs base currency');
            $table->boolean('is_base_currency')->default(false)->comment('Only one record = true');
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('currency_code');
            $table->index('is_base_currency');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('currency_master');
    }
};
