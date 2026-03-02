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
        Schema::connection('tenant')->create('department_master', function (Blueprint $table) {
            $table->id('dept_id');
            $table->string('dept_code', 50)->unique();
            $table->string('dept_name', 100);
            $table->unsignedBigInteger('parent_dept_id')->nullable();
            
            // Status
            $table->boolean('is_active')->default(true);
            
            // Audit
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            
            // Foreign key for self-referencing hierarchy
            $table->foreign('parent_dept_id')
                  ->references('dept_id')
                  ->on('department_master')
                  ->onDelete('restrict');
            
            // Indexes
            $table->index('parent_dept_id');
            $table->index('dept_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('department_master');
    }
};
