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
        Schema::connection('tenant')->create('vendor_master', function (Blueprint $table) {
            $table->id();
            $table->string('vendor_code', 20)->unique()->comment('VND-001');
            $table->string('vendor_name', 200)->comment('Legal company name');
            $table->string('vendor_type', 20)->default('SUPPLIER')->comment('SUPPLIER / SERVICE / TRADER');
            $table->string('gstin', 20)->unique()->nullable()->comment('15-digit GSTIN (unique)');
            $table->char('pan_number', 10)->nullable()->comment('10-char PAN');
            $table->string('msme_category', 10)->nullable()->comment('MICRO / SMALL / MEDIUM');
            $table->string('payment_terms', 30)->default('NET30')->comment('NET30, NET60, ADVANCE, COD');
            $table->smallInteger('credit_days')->default(30)->comment('Credit period in days');
            $table->unsignedBigInteger('currency_id');
            $table->string('delivery_terms', 20)->nullable()->comment('EXW, DDP, CIF, FOB');
            $table->string('bank_name', 100)->nullable()->comment('Bank name');
            $table->string('bank_account_no', 30)->nullable()->comment('Encrypted account number');
            $table->char('ifsc_code', 11)->nullable()->comment('11-char IFSC code');
            $table->boolean('is_approved')->default(false)->comment('Vendor approval status');
            $table->date('approved_date')->nullable()->comment('Date of approval');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->decimal('rating_score', 4, 2)->nullable()->comment('0-100 vendor performance score');
            $table->boolean('blacklisted')->default(false)->comment('Block vendor from RFQ/PO');
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->nullable();
            
            // Foreign keys
            $table->foreign('currency_id')->references('id')->on('currency_master')->onDelete('restrict');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            
            // Indexes
            $table->index('vendor_code');
            $table->index('vendor_type');
            $table->index('is_approved');
            $table->index('blacklisted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('vendor_master');
    }
};
