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
        Schema::connection('control')->create('active_subscriptions', function (Blueprint $table) {
            $table->unsignedBigInteger('org_id')->primary();
            $table->unsignedBigInteger('subscription_id');
            $table->unsignedBigInteger('plan_id');
            $table->string('plan_code', 50);
            
            // Status
            $table->enum('subscription_status', ['TRIAL', 'ACTIVE', 'PAST_DUE']);
            $table->date('period_end_date');
            
            // Denormalized Plan Data
            $table->json('modules_allowed');
            $table->unsignedInteger('max_users');
            
            // Tenant Info
            $table->string('tenant_db_name', 100);
            
            // Flags
            $table->boolean('is_in_trial')->default(false);
            
            // Sync Timestamp
            $table->timestamp('refreshed_at')->useCurrent();
            
            // Foreign Keys
            $table->foreign('org_id')->references('org_id')->on('organizations')->onDelete('cascade');
            
            // Indexes
            $table->index('subscription_status', 'idx_subscription_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('control')->dropIfExists('active_subscriptions');
    }
};
