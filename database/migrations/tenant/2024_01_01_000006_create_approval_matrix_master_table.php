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
        Schema::connection('tenant')->create('approval_matrix_master', function (Blueprint $table) {
            $table->id();
            $table->string('document_type', 20)->comment('PR / PO / PAYMENT / DN');
            $table->smallInteger('level')->comment('1 = first approver, 2 = second...');
            $table->decimal('min_amount', 15, 2)->default(0)->comment('Threshold lower bound (INR)');
            $table->decimal('max_amount', 15, 2)->nullable()->comment('NULL means no upper limit');
            $table->unsignedBigInteger('approver_role_id');
            $table->smallInteger('sla_hours')->default(24)->comment('Escalation SLA in hours');
            $table->boolean('is_active')->default(true);
            
            // Foreign keys
            $table->foreign('approver_role_id')->references('id')->on('role_master')->onDelete('restrict');
            
            // Indexes
            $table->index(['document_type', 'level']);
            $table->index('approver_role_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('approval_matrix_master');
    }
};
