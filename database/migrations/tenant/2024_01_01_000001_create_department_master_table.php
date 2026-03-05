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
        Schema::create('department_master', function (Blueprint $table) {
            $table->id('dept_id');
            $table->string('dept_code', 20)->unique()->comment('e.g. PROD, PURCH, QC');
            $table->string('dept_name', 100)->comment('Full display name');
            $table->unsignedBigInteger('parent_dept_id')->nullable()->comment('Self-reference for hierarchy');
            $table->string('cost_center_code', 20)->nullable()->comment('Link to finance cost centre');
            $table->boolean('is_active')->default(true)->comment('Active flag');
            $table->timestampTz('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();
            
            // Foreign keys
            $table->foreign('parent_dept_id')->references('dept_id')->on('department_master')->onDelete('set null');
            
            // Indexes
            $table->index('dept_code');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('department_master');
    }
};
