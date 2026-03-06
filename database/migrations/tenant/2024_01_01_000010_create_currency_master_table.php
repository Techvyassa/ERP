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
            $table->char('currency_code', 3)->unique()->comment('INR, USD, EUR, AED, SGD');
            $table->string('currency_name', 60)->comment('Indian Rupee, US Dollar...');
            $table->string('symbol', 5)->comment('₹, $, €');
            $table->decimal('exchange_rate', 12, 6)->default(1)->comment('Rate vs base currency (INR)');
            $table->boolean('is_base_currency')->default(false)->comment('Only one record = true (INR)');
            $table->boolean('is_active')->default(true)->comment('Active flag');
            $table->timestampTz('updated_at')->nullable()->comment('Rate last updated timestamp');
            
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
