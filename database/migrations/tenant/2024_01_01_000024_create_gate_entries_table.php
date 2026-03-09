<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Table: gate_entries
     * Module: Inward > Gate Management
     * Depends on: purchase_orders, asn_headers, vendor_master, users
     */
    public function up(): void
    {
        Schema::connection('tenant')->create('gate_entries', function (Blueprint $table) {
            $table->id();

            // --- Reference Number ---
            $table->string('ge_number', 30)->unique()->comment('GE-2425-0001 (system-generated)');

            // --- Links ---
            $table->unsignedBigInteger('po_id')->comment('FK → purchase_orders');
            $table->unsignedBigInteger('asn_id')->nullable()->comment('FK → asn_headers (if ASN-based entry)');
            $table->unsignedBigInteger('vendor_id')->comment('FK → vendor_master');

            // --- Vehicle Details ---
            $table->string('vehicle_number', 20)->comment('Truck registration plate e.g. MH-04-EY-1234');
            $table->string('transporter_name', 100)->nullable()->comment('Logistics company name');
            $table->string('driver_name', 100)->nullable()->comment('Driver full name');
            $table->string('driver_phone', 15)->nullable()->comment('Driver contact number');

            // --- Documents Collected ---
            $table->string('challan_number', 50)->nullable()->comment('Vendor delivery challan number');
            $table->string('vendor_invoice_number', 50)->nullable()->comment('Vendor invoice number on paper');
            $table->string('eway_bill_number', 30)->nullable()->comment('E-Way Bill number (E-INV format)');
            $table->date('eway_bill_expiry')->nullable()->comment('E-Way Bill valid until date');

            // --- Material Classification ---
            $table->enum('material_type', ['RAW_MATERIAL', 'PACKAGING', 'CONSUMABLE', 'CAPITAL_GOODS', 'SPARE_PARTS'])
                ->default('RAW_MATERIAL')->comment('Category of incoming material');

            // --- Weighbridge (Gross) ---
            $table->decimal('gross_weight_kg', 10, 3)->nullable()->comment('Loaded truck weight from weighbridge');

            // --- Timing ---
            $table->dateTime('arrived_at')->comment('Actual arrival timestamp at gate');

            // --- Status ---
            $table->enum('status', ['PENDING_VERIFICATION', 'VERIFIED', 'REJECTED', 'MOVED_TO_DOCK'])
                ->default('PENDING_VERIFICATION')->comment('Gate processing status');

            // --- Audit ---
            $table->text('remarks')->nullable()->comment('Guard notes');
            $table->unsignedBigInteger('created_by')->nullable()->comment('FK → users (Security Guard)');
            $table->softDeletes();
            $table->timestampsTz();

            // Foreign Keys
            $table->foreign('po_id')->references('id')->on('purchase_orders')->onDelete('restrict');
            $table->foreign('asn_id')->references('id')->on('asn_headers')->onDelete('set null');
            $table->foreign('vendor_id')->references('id')->on('vendor_master')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index('ge_number');
            $table->index('po_id');
            $table->index('vendor_id');
            $table->index('status');
            $table->index('arrived_at');
            $table->index('vehicle_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('gate_entries');
    }
};
