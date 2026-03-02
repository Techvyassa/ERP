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
        Schema::connection('control')->create('org_subscriptions', function (Blueprint $table) {
            $table->id('subscription_id');
            $table->unsignedBigInteger('org_id');
            $table->unsignedBigInteger('plan_id');
            
            // Status
            $table->enum('subscription_status', ['TRIAL', 'ACTIVE', 'PAST_DUE', 'CANCELLED', 'EXPIRED']);
            
            // Trial Period
            $table->date('trial_start_date')->nullable();
            $table->date('trial_end_date')->nullable();
            
            // Billing Period
            $table->date('current_period_start');
            $table->date('current_period_end');
            $table->date('next_billing_date')->nullable();
            
            // Grace Period
            $table->timestamp('grace_period_until')->nullable();
            
            // Cancellation
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            
            // Timestamps
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            
            // Foreign Keys
            $table->foreign('org_id')->references('org_id')->on('organizations')->onDelete('cascade');
            $table->foreign('plan_id')->references('plan_id')->on('subscription_plans')->onDelete('restrict');
            
            // Indexes
            $table->index('org_id', 'idx_org_id');
            $table->index('subscription_status', 'idx_subscription_status');
            $table->index('next_billing_date', 'idx_next_billing_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('control')->dropIfExists('org_subscriptions');
    }
};
