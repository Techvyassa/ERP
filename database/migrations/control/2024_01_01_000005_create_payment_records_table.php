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
        Schema::connection('control')->create('payment_records', function (Blueprint $table) {
            $table->id('payment_id');
            $table->unsignedBigInteger('org_id');
            $table->unsignedBigInteger('subscription_id')->nullable();
            
            // Payment Details
            $table->string('payment_reference', 100)->unique();
            $table->enum('payment_type', ['INVOICE', 'ADVANCE', 'REFUND', 'CREDIT_NOTE', 'ADJUSTMENT']);
            $table->enum('payment_status', ['PENDING', 'SUCCESS', 'FAILED', 'REFUNDED', 'PARTIALLY_REFUNDED']);
            
            // Amounts
            $table->decimal('taxable_amount', 10, 2);
            $table->decimal('cgst_amount', 10, 2)->default(0.00);
            $table->decimal('sgst_amount', 10, 2)->default(0.00);
            $table->decimal('igst_amount', 10, 2)->default(0.00);
            $table->decimal('total_amount', 10, 2);
            
            // Gateway Integration
            $table->string('gateway_name', 50)->nullable();
            $table->string('gateway_payment_id', 255)->nullable();
            $table->json('gateway_response')->nullable();
            
            // Dates
            $table->timestamp('payment_date')->nullable();
            $table->timestamp('created_at')->useCurrent();
            // Note: No updated_at - immutable ledger
            
            // Foreign Keys
            $table->foreign('org_id')->references('org_id')->on('organizations')->onDelete('restrict');
            
            // Indexes
            $table->index('org_id', 'idx_org_id');
            $table->index('payment_reference', 'idx_payment_reference');
            $table->index('payment_status', 'idx_payment_status');
            $table->index('gateway_payment_id', 'idx_gateway_payment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('control')->dropIfExists('payment_records');
    }
};
