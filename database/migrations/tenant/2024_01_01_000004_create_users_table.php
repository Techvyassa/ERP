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
        Schema::connection('tenant')->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('employee_code', 50)->unique();
            $table->string('email', 255)->unique();
            $table->string('password_hash', 255);
            
            // Personal Info
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('phone', 20)->nullable();
            
            // Organization Structure
            $table->unsignedBigInteger('dept_id');
            $table->unsignedBigInteger('role_id');
            
            // Status
            $table->boolean('is_active')->default(true);
            
            // Authentication
            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('password_changed_at')->nullable();
            
            // Audit
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('dept_id')
                  ->references('id')
                  ->on('department_master')
                  ->onDelete('restrict');
            
            $table->foreign('role_id')
                  ->references('id')
                  ->on('role_master')
                  ->onDelete('restrict');
            
            // Indexes for performance
            $table->index('employee_code');
            $table->index('email');
            $table->index('dept_id');
            $table->index('role_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('users');
    }
};
