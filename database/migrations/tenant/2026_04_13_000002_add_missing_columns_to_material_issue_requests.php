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
        // Add batch_run_id column if it doesn't exist
        if (!Schema::connection('tenant')->hasColumn('material_issue_requests', 'batch_run_id')) {
            Schema::connection('tenant')->table('material_issue_requests', function (Blueprint $table) {
                $table->unsignedBigInteger('batch_run_id')->nullable()->after('mir_no');
                $table->foreign('batch_run_id')->references('id')->on('production_batch_runs');
            });
        }

        // Add fully_issued_at column if it doesn't exist
        if (!Schema::connection('tenant')->hasColumn('material_issue_requests', 'fully_issued_at')) {
            Schema::connection('tenant')->table('material_issue_requests', function (Blueprint $table) {
                $table->timestamp('fully_issued_at')->nullable()->after('approved_at');
            });
        }

        // Add closed_at column if it doesn't exist
        if (!Schema::connection('tenant')->hasColumn('material_issue_requests', 'closed_at')) {
            Schema::connection('tenant')->table('material_issue_requests', function (Blueprint $table) {
                $table->timestamp('closed_at')->nullable()->after('fully_issued_at');
            });
        }

        // Update status enum to include new values
        // MySQL doesn't support ALTER TABLE MODIFY ENUM easily, so we use raw SQL
        try {
            DB::connection('tenant')->statement("ALTER TABLE material_issue_requests MODIFY COLUMN status ENUM('PENDING', 'APPROVED', 'PARTIALLY_ISSUED', 'FULLY_ISSUED', 'REJECTED', 'CLOSED') DEFAULT 'PENDING'");
        } catch (\Exception $e) {
            // If the enum already has these values, ignore the error
            // This is expected if the column was already updated
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->table('material_issue_requests', function (Blueprint $table) {
            $table->dropForeign(['batch_run_id']);
            $table->dropColumn(['batch_run_id', 'fully_issued_at', 'closed_at']);
        });

        // Revert status enum to original values
        try {
            DB::connection('tenant')->statement("ALTER TABLE material_issue_requests MODIFY COLUMN status ENUM('PENDING', 'APPROVED', 'ISSUED') DEFAULT 'PENDING'");
        } catch (\Exception $e) {
            // Ignore if revert fails
        }
    }
};