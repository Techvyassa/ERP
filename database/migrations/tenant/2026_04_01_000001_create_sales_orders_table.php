<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table: sales_orders + sales_order_line_items
     * Module: Outward > Sales
     * Depends on: product_master, uom_master, users
     */
    public function up(): void
    {
        Schema::connection('tenant')->create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_code', 30)->unique();
            $table->string('customer_name', 200);
            $table->string('contact_person', 100)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('billing_address', 500)->nullable();
            $table->string('shipping_address', 500)->nullable();
            $table->string('gstin', 20)->nullable();
            $table->string('payment_terms', 30)->default('NET30');
            $table->unsignedSmallInteger('credit_days')->default(30);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->softDeletes();
            $table->timestampsTz();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->index('customer_code');
            $table->index('is_active');
        });

        Schema::connection('tenant')->create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->string('so_number', 30)->unique()->comment('SO-YYMM-XXXX (system-generated)');
            $table->unsignedBigInteger('customer_id');
            $table->string('billing_address', 500)->nullable();
            $table->string('shipping_address', 500)->nullable();
            $table->date('so_date');
            $table->date('required_delivery_date');
            $table->string('payment_terms', 30)->default('NET30');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->enum('status', [
                'DRAFT',
                'CONFIRMED',
                'STOCK_CHECKED',
                'PICKING',
                'PACKED',
                'DISPATCHED',
                'DELIVERED',
                'CANCELLED',
            ])->default('DRAFT');
            $table->enum('stock_status', [
                'PENDING',
                'AVAILABLE',
                'PARTIAL',
                'UNAVAILABLE',
            ])->default('PENDING')->comment('Result of stock availability check');
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->timestampTz('confirmed_at')->nullable();
            $table->softDeletes();
            $table->timestampsTz();

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('confirmed_by')->references('id')->on('users')->onDelete('set null');
            $table->index('so_number');
            $table->index('customer_id');
            $table->index('status');
            $table->index('required_delivery_date');
        });

        Schema::connection('tenant')->create('sales_order_line_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('so_id');
            $table->unsignedBigInteger('product_id');
            $table->decimal('qty', 12, 3);
            $table->unsignedBigInteger('uom_id');
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('line_total', 15, 2)->default(0);
            $table->decimal('available_qty', 12, 3)->default(0)->comment('Filled during stock check');
            $table->enum('availability', ['PENDING', 'AVAILABLE', 'PARTIAL', 'UNAVAILABLE'])->default('PENDING');
            $table->timestampsTz();

            $table->foreign('so_id')->references('id')->on('sales_orders')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('product_master')->onDelete('restrict');
            $table->foreign('uom_id')->references('id')->on('uom_master')->onDelete('restrict');
            $table->index('so_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('sales_order_line_items');
        Schema::connection('tenant')->dropIfExists('sales_orders');
        Schema::connection('tenant')->dropIfExists('customers');
    }
};
