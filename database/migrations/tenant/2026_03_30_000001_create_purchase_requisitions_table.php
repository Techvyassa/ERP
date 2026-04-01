<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Table: purchase_requisitions
     * Module: Procurement > Purchase Requisition
     * Depends on: department_master, users, vendor_master
     */
    public function up(): void
    {
        Schema::connection('tenant')->create('purchase_requisitions', function (Blueprint $table) {
            $table->id();

            // --- Reference ---
            $table->string('pr_number', 50)->unique()->comment('PR-2425-00001 (system-generated)');

            // --- Requestor Info ---
            $table->unsignedBigInteger('requested_by')->comment('FK → users (employee initiating the PR)');
            $table->unsignedBigInteger('department_id')->comment('FK → department_master');
            $table->string('cost_center_code', 20)->nullable()->comment('Copied from department_master.cost_center_code at time of PR');

            // --- Dates ---
            $table->date('pr_date')->comment('Date the PR was officially raised');
            $table->date('required_date')->comment('Date by which goods/services are needed');

            // --- Priority ---
            $table->enum('priority', ['LOW', 'MEDIUM', 'HIGH', 'EMERGENCY'])
                ->default('LOW')
                ->comment('LOW=Standard, MEDIUM=Urgent, HIGH=Critical, EMERGENCY=Emergency');

            // --- Budget / GL ---
            $table->string('budget_code', 50)->nullable()->comment('GL account / budget code for financial tracking');

            // --- Suggested Vendor ---
            $table->unsignedBigInteger('suggested_vendor_id')->nullable()
                ->comment('FK → vendor_master; requestor preference, subject to procurement approval');

            // --- Status ---
            $table->enum('status', [
                'DRAFT',
                'PENDING_APPROVAL',
                'APPROVED',
                'REJECTED',
                'CONVERTED_TO_PO',
                'CANCELLED',
            ])->default('DRAFT')->comment('Lifecycle status of the PR');

            // --- Justification / Notes ---
            $table->text('justification')->nullable()->comment('Business reason / purpose for the request');
            $table->text('remarks')->nullable()->comment('Internal notes');

            // --- Audit ---
            $table->unsignedBigInteger('created_by')->nullable()->comment('FK → users');
            $table->softDeletes();
            $table->timestampsTz();

            // Foreign Keys
            $table->foreign('requested_by')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('department_id')->references('id')->on('department_master')->onDelete('restrict');
            $table->foreign('suggested_vendor_id')->references('id')->on('vendor_master')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index('pr_number');
            $table->index('requested_by');
            $table->index('department_id');
            $table->index('status');
            $table->index('pr_date');
            $table->index('required_date');
            $table->index('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('purchase_requisitions');
    }
};
