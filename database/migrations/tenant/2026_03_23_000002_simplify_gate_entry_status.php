<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * New Inward Flow — Gate Entry Status Simplification.
     *
     * Old statuses: PENDING_VERIFICATION, VERIFIED, REJECTED, MOVED_TO_DOCK
     * New statuses: PENDING (arrived, GRN not yet created), COMPLETED (GRN auto-created)
     *
     * For MySQL: modify enum by raw ALTER TABLE.
     */
    public function up(): void
    {
        // Step 1: Expand enum to allow BOTH old and new values simultaneously,
        // so MySQL strict mode does not reject the subsequent UPDATE.
        DB::connection('tenant')->statement("
            ALTER TABLE gate_entries
            MODIFY COLUMN status ENUM(
                'PENDING_VERIFICATION','VERIFIED','REJECTED','MOVED_TO_DOCK',
                'PENDING','COMPLETED'
            ) NOT NULL DEFAULT 'PENDING_VERIFICATION'
        ");

        // Step 2: Migrate existing rows to the new status values.
        DB::connection('tenant')->statement("
            UPDATE gate_entries
            SET status = CASE
                WHEN status IN ('PENDING_VERIFICATION','REJECTED') THEN 'PENDING'
                WHEN status IN ('VERIFIED','MOVED_TO_DOCK')         THEN 'COMPLETED'
                ELSE 'PENDING'
            END
        ");

        // Step 3: Narrow the enum to only the new values.
        DB::connection('tenant')->statement("
            ALTER TABLE gate_entries
            MODIFY COLUMN status ENUM('PENDING','COMPLETED') NOT NULL DEFAULT 'PENDING'
                COMMENT 'PENDING = arrived, GRN not yet created; COMPLETED = GRN auto-created'
        ");
    }

    public function down(): void
    {
        // Restore old enum
        DB::connection('tenant')->statement("
            ALTER TABLE gate_entries
            MODIFY COLUMN status ENUM('PENDING_VERIFICATION','VERIFIED','REJECTED','MOVED_TO_DOCK')
                NOT NULL DEFAULT 'PENDING_VERIFICATION'
        ");

        // Best-effort data migration back
        DB::connection('tenant')->statement("
            UPDATE gate_entries
            SET status = CASE
                WHEN status = 'COMPLETED' THEN 'MOVED_TO_DOCK'
                ELSE 'PENDING_VERIFICATION'
            END
        ");
    }
};
