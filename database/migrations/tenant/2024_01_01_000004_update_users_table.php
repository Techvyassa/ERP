<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add full_name computed column if it doesn't exist
            if (!Schema::hasColumn('users', 'full_name')) {
                $table->string('full_name', 150)->after('last_name')->storedAs('CONCAT(first_name, \' \', last_name)');
            }
            
            // Add dept_id column if it doesn't exist
            if (!Schema::hasColumn('users', 'dept_id')) {
                $table->unsignedBigInteger('dept_id')->after('phone')->nullable();
            }
        });
        
        // Add foreign keys separately to avoid duplicate constraint errors
        $foreignKeys = $this->getForeignKeys('users');
        
        Schema::table('users', function (Blueprint $table) use ($foreignKeys) {
            // Add dept_id foreign key if it doesn't exist
            if (!in_array('users_dept_id_foreign', $foreignKeys)) {
                $table->foreign('dept_id', 'users_dept_id_foreign')
                    ->references('id')
                    ->on('department_master')
                    ->onDelete('set null');
            }
            
            // Add role_id foreign key if it doesn't exist
            if (!in_array('users_role_id_foreign', $foreignKeys)) {
                $table->foreign('role_id', 'users_role_id_foreign')
                    ->references('id')
                    ->on('role_master')
                    ->onDelete('restrict');
            }
        });
        
        // Add indexes if they don't exist
        $indexes = $this->getIndexes('users');
        
        Schema::table('users', function (Blueprint $table) use ($indexes) {
            if (!in_array('users_dept_id_index', $indexes)) {
                $table->index('dept_id');
            }
            if (!in_array('users_is_active_index', $indexes)) {
                $table->index('is_active');
            }
            if (!in_array('users_last_login_at_index', $indexes)) {
                $table->index('last_login_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop foreign keys if they exist
            $foreignKeys = $this->getForeignKeys('users');
            
            if (in_array('users_dept_id_foreign', $foreignKeys)) {
                $table->dropForeign('users_dept_id_foreign');
            }
            if (in_array('users_role_id_foreign', $foreignKeys)) {
                $table->dropForeign('users_role_id_foreign');
            }
            
            // Drop columns if they exist
            if (Schema::hasColumn('users', 'full_name')) {
                $table->dropColumn('full_name');
            }
            if (Schema::hasColumn('users', 'dept_id')) {
                $table->dropColumn('dept_id');
            }
        });
    }
    
    /**
     * Get list of foreign keys for a table
     */
    private function getForeignKeys(string $table): array
    {
        $database = DB::connection('tenant')->getDatabaseName();
        
        $foreignKeys = DB::connection('tenant')
            ->select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE TABLE_SCHEMA = ? 
                AND TABLE_NAME = ? 
                AND CONSTRAINT_TYPE = 'FOREIGN KEY'
            ", [$database, $table]);
        
        return array_map(fn($fk) => $fk->CONSTRAINT_NAME, $foreignKeys);
    }
    
    /**
     * Get list of indexes for a table
     */
    private function getIndexes(string $table): array
    {
        $database = DB::connection('tenant')->getDatabaseName();
        
        $indexes = DB::connection('tenant')
            ->select("
                SELECT DISTINCT INDEX_NAME 
                FROM information_schema.STATISTICS 
                WHERE TABLE_SCHEMA = ? 
                AND TABLE_NAME = ?
            ", [$database, $table]);
        
        return array_map(fn($idx) => $idx->INDEX_NAME, $indexes);
    }
};
