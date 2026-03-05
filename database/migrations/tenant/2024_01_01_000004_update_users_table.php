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
        Schema::table('users', function (Blueprint $table) {
            // Add new columns if they don't exist
            if (!Schema::hasColumn('users', 'full_name')) {
                $table->string('full_name', 150)->after('last_name')->storedAs('CONCAT(first_name, \' \', last_name)');
            }
            
            // Add foreign keys if they don't exist
            if (!Schema::hasColumn('users', 'dept_id')) {
                $table->unsignedBigInteger('dept_id')->after('phone')->nullable();
                $table->foreign('dept_id')->references('dept_id')->on('department_master')->onDelete('set null');
            }
            
            // Update role_id to be foreign key
            $table->foreign('role_id')->references('role_id')->on('role_master')->onDelete('restrict');
            
            // Add indexes
            $table->index('dept_id');
            $table->index('is_active');
            $table->index('last_login_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['dept_id']);
            $table->dropForeign(['role_id']);
            $table->dropColumn(['full_name', 'dept_id']);
        });
    }
};
