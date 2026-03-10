<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates the dept_role_map pivot table which enforces
     * that only approved roles can be assigned within a department.
     */
    public function up(): void
    {
        Schema::connection('tenant')->create('dept_role_map', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dept_id')->comment('Department from department_master');
            $table->unsignedBigInteger('role_id')->comment('Role from role_master');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            // Foreign keys
            $table->foreign('dept_id')
                ->references('id')
                ->on('department_master')
                ->onDelete('cascade');

            $table->foreign('role_id')
                ->references('id')
                ->on('role_master')
                ->onDelete('cascade');

            // A role can only appear once per department
            $table->unique(['dept_id', 'role_id'], 'uq_dept_role');

            // Indexes
            $table->index('dept_id');
            $table->index('role_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('dept_role_map');
    }
};
