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
        Schema::connection('tenant')->create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->string('module_code', 40)->comment('PR, PO, GRN, QC, INVOICE, PAYMENT...');
            $table->boolean('can_view')->default(false)->comment('View/read access');
            $table->boolean('can_create')->default(false)->comment('Create new records');
            $table->boolean('can_edit')->default(false)->comment('Edit existing records');
            $table->boolean('can_approve')->default(false)->comment('Approval workflow actions');
            $table->boolean('can_delete')->default(false)->comment('Delete / deactivate records');
            
            // Foreign keys
            $table->foreign('role_id')->references('id')->on('role_master')->onDelete('cascade');
            
            // Unique constraint
            $table->unique(['role_id', 'module_code'], 'unique_role_module');
            
            // Indexes
            $table->index('role_id');
            $table->index('module_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('role_permissions');
    }
};
