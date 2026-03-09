<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Table: gate_verifications
     * Module: Inward > Gate Management
     * Depends on: gate_entries, users
     */
    public function up(): void
    {
        Schema::connection('tenant')->create('gate_verifications', function (Blueprint $table) {
            $table->id();

            // --- Parent ---
            $table->unsignedBigInteger('ge_id')->unique()->comment('FK → gate_entries (one verification per entry)');

            // --- Document Checks ---
            $table->boolean('challan_verified')->default(false)->comment('Delivery challan matched with GE');
            $table->boolean('invoice_verified')->default(false)->comment('Vendor invoice matched with system PO');
            $table->boolean('eway_bill_valid')->default(false)->comment('E-Way Bill not expired and valid');
            $table->boolean('po_status_valid')->default(false)->comment('PO is OPEN (not cancelled / closed)');

            // --- Physical Inspection ---
            $table->string('seal_number', 50)->nullable()->comment('Container / truck seal ID recorded');
            $table->boolean('seal_intact')->nullable()->comment('Whether seal was found unbroken');
            $table->boolean('external_damage')->default(false)->comment('Any visible packaging / container damage');

            // --- Weighbridge (Tare) ---
            $table->decimal('tare_weight_kg', 10, 3)->nullable()->comment('Empty truck weight after unloading');
            $table->decimal('net_weight_kg', 10, 3)->nullable()
                ->comment('Derived: gross_weight - tare_weight. Compared to invoice weight.');
            $table->boolean('weight_variance_flag')->default(false)
                ->comment('TRUE if net weight deviates beyond tolerance vs invoice');

            // --- Dock Assignment ---
            $table->string('dock_assigned', 30)->nullable()->comment('Unloading bay / dock number assigned');

            // --- Outcome ---
            $table->enum('approval_status', ['PENDING', 'APPROVED', 'REJECTED'])
                ->default('PENDING')->comment('Supervisor decision');
            $table->text('rejection_reason')->nullable()->comment('Filled if status = REJECTED');
            $table->text('security_remarks')->nullable()->comment('Supervisor observations');

            // --- Audit ---
            $table->unsignedBigInteger('verified_by')->nullable()->comment('FK → users (Security Supervisor)');
            $table->dateTime('verified_at')->nullable()->comment('Timestamp of verification completion');
            $table->timestampsTz();

            // Foreign Keys
            $table->foreign('ge_id')->references('id')->on('gate_entries')->onDelete('cascade');
            $table->foreign('verified_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index('ge_id');
            $table->index('approval_status');
            $table->index('verified_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('gate_verifications');
    }
};
