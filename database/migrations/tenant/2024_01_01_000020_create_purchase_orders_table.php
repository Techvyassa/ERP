<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Table: purchase_orders
     * Module: Inward > Procurement
     * Depends on: vendor_master, currency_master, users
     */
    public function up(): void
    {
        Schema::connection('tenant')->create('purchase_orders', function (Blueprint $table) {
            $table->id();

            // --- Reference ---
            $table->string('po_number', 30)->unique()->comment('PO-2425-00001 (system-generated)');

            // --- Parties ---
            $table->unsignedBigInteger('vendor_id')->comment('FK → vendor_master');
            $table->unsignedBigInteger('currency_id')->comment('FK → currency_master');

            // --- Addresses ---
            $table->string('billing_address', 500)->nullable()->comment('Invoice-to address');
            $table->string('ship_to_address', 500)->nullable()->comment('Delivery address / plant');

            // --- Financials ---
            $table->string('payment_terms', 30)->default('NET30')->comment('NET30, NET60, COD, ADVANCE');
            $table->unsignedSmallInteger('credit_days')->default(30)->comment('Credit period in days');
            $table->string('delivery_terms', 20)->nullable()->comment('EXW, DDP, FOB, CIF');
            $table->decimal('subtotal', 15, 2)->default(0)->comment('Sum of line totals before tax');
            $table->decimal('discount_amount', 12, 2)->default(0)->comment('Header-level discount');
            $table->decimal('freight_charges', 12, 2)->default(0)->comment('Transportation charges');
            $table->decimal('tax_amount', 12, 2)->default(0)->comment('Total GST / tax amount');
            $table->decimal('grand_total', 15, 2)->default(0)->comment('Final payable amount');

            // --- Dates ---
            $table->date('po_date')->comment('Date of PO creation');
            $table->date('expected_delivery')->nullable()->comment('Expected delivery date');
            $table->date('valid_until')->nullable()->comment('PO expiry date');

            // --- Status ---
            $table->enum('status', [
                'DRAFT',
                'PENDING_APPROVAL',
                'OPEN',
                'PARTIALLY_RECEIVED',
                'FULLY_RECEIVED',
                'CLOSED',
                'CANCELLED',
            ])->default('DRAFT')->comment('Lifecycle status of PO');

            // --- Notes ---
            $table->text('terms_conditions')->nullable()->comment('Legal T&C for this PO');
            $table->text('remarks')->nullable()->comment('Internal notes');

            // --- Audit ---
            $table->unsignedBigInteger('created_by')->nullable()->comment('FK → users (Procurement Exec)');
            $table->unsignedBigInteger('approved_by')->nullable()->comment('FK → users (Procurement Manager)');
            $table->timestamp('approved_at')->nullable();
            $table->softDeletes();
            $table->timestampsTz();

            // Foreign Keys
            $table->foreign('vendor_id')->references('id')->on('vendor_master')->onDelete('restrict');
            $table->foreign('currency_id')->references('id')->on('currency_master')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index('po_number');
            $table->index('vendor_id');
            $table->index('status');
            $table->index('po_date');
            $table->index('expected_delivery');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('purchase_orders');
    }
};
