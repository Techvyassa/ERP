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
        Schema::connection('control')->create('subscription_plans', function (Blueprint $table) {
            $table->id('plan_id');
            $table->string('plan_code', 50)->unique();
            $table->string('plan_name', 100);
            $table->text('description')->nullable();
            
            // Billing
            $table->enum('billing_cycle', ['MONTHLY', 'QUARTERLY', 'ANNUAL']);
            $table->decimal('price_amount', 10, 2);
            $table->char('currency_code', 3)->default('USD');
            
            // Capacity Limits
            $table->unsignedInteger('max_users');
            $table->unsignedInteger('max_warehouses');
            $table->unsignedInteger('max_materials');
            $table->unsignedInteger('storage_gb');
            $table->unsignedInteger('api_rate_limit_day');
            
            // Features
            $table->json('modules_included');
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_public')->default(true);
            
            // Timestamps
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            
            // Indexes
            $table->index('plan_code', 'idx_plan_code');
            $table->index('is_active', 'idx_is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('control')->dropIfExists('subscription_plans');
    }
};
