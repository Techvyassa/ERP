<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add execution tracking fields to production_orders:
     * - actual_start_at / actual_end_at for labor tracking
     * - actual_qty / rejected_qty / rework_qty for yield tracking
     * - yield_percent for efficiency reporting
     * - fg_bin_id / fg_warehouse_id / fg_batch_number for FG stock placement
     * - confirmed_by / confirmed_at for audit trail
     */
    public function up(): void
    {
        Schema::connection('tenant')->table('production_orders', function (Blueprint $table) {
            // Execution timestamps
            $table->timestampTz('actual_start_at')->nullable()->after('planned_date');
            $table->timestampTz('actual_end_at')->nullable()->after('actual_start_at');

            // Quantities
            $table->decimal('actual_qty', 12, 3)->nullable()->after('target_qty');
            $table->decimal('rejected_qty', 12, 3)->default(0)->after('actual_qty');
            $table->decimal('rework_qty', 12, 3)->default(0)->after('rejected_qty');

            // Yield calculation
            $table->decimal('yield_percent', 5, 2)->nullable()->after('rework_qty');

            // FG stock placement
            $table->unsignedBigInteger('fg_bin_id')->nullable()->after('bom_id');
            $table->unsignedBigInteger('fg_warehouse_id')->nullable()->after('fg_bin_id');
            $table->string('fg_batch_number', 50)->nullable()->after('fg_warehouse_id');

            // Audit
            $table->unsignedBigInteger('confirmed_by')->nullable()->after('created_by');
            $table->timestampTz('confirmed_at')->nullable()->after('confirmed_by');

            // Foreign keys
            $table->foreign('fg_bin_id')->references('id')->on('bin_locations')->onDelete('set null');
            $table->foreign('fg_warehouse_id')->references('id')->on('warehouse_master')->onDelete('set null');
            $table->foreign('confirmed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->table('production_orders', function (Blueprint $table) {
            $table->dropForeign(['fg_bin_id']);
            $table->dropForeign(['fg_warehouse_id']);
            $table->dropForeign(['confirmed_by']);

            $table->dropColumn([
                'actual_start_at',
                'actual_end_at',
                'actual_qty',
                'rejected_qty',
                'rework_qty',
                'yield_percent',
                'fg_bin_id',
                'fg_warehouse_id',
                'fg_batch_number',
                'confirmed_by',
                'confirmed_at',
            ]);
        });
    }
};