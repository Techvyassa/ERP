<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Table: asn_headers
     * Module: Inward > Advance Shipping Notice
     * Depends on: purchase_orders, vendor_master
     */
    public function up(): void
    {
        Schema::connection('tenant')->create('asn_headers', function (Blueprint $table) {
            $table->id();

            // --- Reference ---
            $table->string('asn_number', 30)->unique()->comment('ASN-2425-0001 (vendor-provided or generated)');
            $table->unsignedBigInteger('po_id')->comment('FK → purchase_orders');
            $table->unsignedBigInteger('vendor_id')->comment('FK → vendor_master');

            // --- Dispatch Details ---
            $table->dateTime('ship_date')->comment('Date and time goods left vendor facility');
            $table->dateTime('eta')->nullable()->comment('Estimated time of arrival at plant');

            // --- Transport ---
            $table->string('carrier_name', 100)->nullable()->comment('Logistics company name e.g. BlueDart');
            $table->string('tracking_number', 100)->nullable()->comment('Courier or lorry receipt number');
            $table->string('vehicle_number', 20)->nullable()->comment('Truck / lorry registration number');
            $table->string('container_id', 50)->nullable()->comment('Container or trailer ID');

            // --- Delivery Target ---
            $table->string('ship_from_address', 500)->nullable()->comment('Vendor warehouse dispatch address');
            $table->string('ship_to_address', 500)->nullable()->comment('Plant / dock receiving address');

            // --- Status ---
            $table->enum('status', ['SENT', 'ACKNOWLEDGED', 'ARRIVED', 'CANCELLED'])
                ->default('SENT')->comment('Current shipment status');

            // --- Audit ---
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->comment('FK → users');
            $table->timestampsTz();
            $table->softDeletes();

            // Foreign Keys
            $table->foreign('po_id')->references('id')->on('purchase_orders')->onDelete('restrict');
            $table->foreign('vendor_id')->references('id')->on('vendor_master')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index('asn_number');
            $table->index('po_id');
            $table->index('vendor_id');
            $table->index('status');
            $table->index('eta');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('asn_headers');
    }
};
