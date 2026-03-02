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
            $table->id('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->string('module_code', 50);
            
            // Permission Flags
            $table->boolean('can_view')->default(false);
            $table->boolean('can_create')->default(false);
            $table->boolean('can_edit')->default(false);
            $table->boolean('can_approve')->default(false);
            $table->boolean('can_delete')->default(false);
            
            // Audit
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            
            // Foreign key
            $table->foreign('role_id')
                  ->references('role_id')
                  ->on('role_master')
                  ->onDelete('cascade');
            
            // Unique constraint: one permission record per role-module combination
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
