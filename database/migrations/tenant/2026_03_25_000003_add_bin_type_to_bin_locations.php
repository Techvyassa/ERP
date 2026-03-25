<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add bin_type column to bin_locations so virtual bins (RECEIVING_DOCK, STAGING, etc.)
     * can be differentiated from normal storage bins.
     * Also marks system-generated virtual bins that are created automatically per warehouse.
     */
    public function up(): void
    {
        Schema::connection('tenant')->table('bin_locations', function (Blueprint $table) {
            $table->enum('bin_type', [
                'STORAGE',       // Regular racking / shelving — normal put-away destination
                'RECEIVING_DOCK',// Virtual bin: goods arrive here at the gate / unloading area
                'STAGING',       // Physical staging area (between dock and final shelf)
                'QUARANTINE',    // QC hold area (physical or virtual)
                'DISPATCH',      // Outbound dispatch staging
                'RETURN',        // Return / RTV staging area
            ])->default('STORAGE')->after('shelf')->comment('Functional type of the bin');

            $table->boolean('is_virtual')->default(false)->after('bin_type')
                ->comment('Virtual bins track state (QC_HOLD, PUTAWAY_PENDING) without a physical shelf');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->table('bin_locations', function (Blueprint $table) {
            $table->dropColumn(['bin_type', 'is_virtual']);
        });
    }
};
