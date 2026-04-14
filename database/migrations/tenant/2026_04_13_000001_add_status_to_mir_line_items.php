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
        Schema::connection('tenant')->table('mir_line_items', function (Blueprint $table) {
            // Add status column if it doesn't exist
            if (!Schema::connection('tenant')->hasColumn('mir_line_items', 'status')) {
                $table->enum('status', ['PENDING', 'APPROVED', 'PARTIALLY_PICKED', 'FULLY_PICKED', 'REJECTED'])
                      ->default('PENDING')
                      ->after('material_id');
            }

            // Add last_issued_at column if it doesn't exist
            if (!Schema::connection('tenant')->hasColumn('mir_line_items', 'last_issued_at')) {
                $table->timestamp('last_issued_at')->nullable()->after('issued_qty');
            }

            // Add rejected_reason column if it doesn't exist
            if (!Schema::connection('tenant')->hasColumn('mir_line_items', 'rejected_reason')) {
                $table->text('rejected_reason')->nullable()->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->table('mir_line_items', function (Blueprint $table) {
            $table->dropColumn(['status', 'last_issued_at', 'rejected_reason']);
        });
    }
};