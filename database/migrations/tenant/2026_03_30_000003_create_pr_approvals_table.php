<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Table: pr_approvals
     * Module: Procurement > Purchase Requisition
     * Depends on: purchase_requisitions, users, approval_matrix_master
     */
    public function up(): void
    {
        Schema::connection('tenant')->create('pr_approvals', function (Blueprint $table) {
            $table->id();

            // --- Parent Reference ---
            $table->unsignedBigInteger('pr_id')->comment('FK → purchase_requisitions');

            // --- Approval Level (ties back to approval_matrix_master) ---
            $table->tinyInteger('approval_level')->default(1)->comment('Approval level: 1 = Dept Head, 2 = Finance, 3 = Procurement');

            // --- Approver ---
            $table->unsignedBigInteger('approved_by')->comment('FK → users (assigned approver for this level)');

            // --- Decision ---
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED'])
                ->default('PENDING')
                ->comment('Decision status for this approval level');

            $table->text('comments')->nullable()->comment('Approver remarks / rejection reason');
            $table->timestampTz('approval_date')->nullable()->comment('When the decision was made');

            // --- SLA Tracking ---
            $table->timestampTz('due_by')->nullable()->comment('SLA deadline derived from approval_matrix_master.sla_hours');

            // --- Audit ---
            $table->timestampsTz();

            // Foreign Keys
            $table->foreign('pr_id')->references('id')->on('purchase_requisitions')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('restrict');

            // Indexes
            $table->index('pr_id');
            $table->index('approved_by');
            $table->index('status');
            $table->unique(['pr_id', 'approval_level'], 'uq_pr_approval_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('pr_approvals');
    }
};
