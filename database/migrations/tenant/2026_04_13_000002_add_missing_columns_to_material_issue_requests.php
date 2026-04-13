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
        Schema::connection('tenant')->table('material_issue_requests', function (Blueprint $table) {
            // Add batch_run_id column if it doesn't exist
            if (!Schema::connection('tenant')->hasColumn('material_issue_requests', 'batch_run_id')) {
                $table->unsignedBigInteger('batch_run_id')->nullable()->after('mir_no');
                $table->foreign('batch_run_id')->references('id')->on('production_batch_runs');
            }

            // Add fully_issued_at column if it doesn't exist
            if (!Schema::connection('tenant')->hasColumn('material_issue_requests', 'fully_issued_at')) {
                $table->timestamp('fully_issued_at')->nullable()->after('approved_at');
            }

            // Add closed_at column if it doesn't exist
            if (!Schema::connection('tenant')->hasColumn('material_issue_requests', 'closed_at')) {
                $table->timestamp('closed_at')->nullable()->after('fully_issued_at');
            }

            // Update status enum to include new values if needed
            // Note: This is a simplified approach - in production, you may need to modify the enum
        });
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
    }
};