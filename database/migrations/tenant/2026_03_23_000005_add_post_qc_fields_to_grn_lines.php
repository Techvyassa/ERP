<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add post-QC fields to GRN line items for rejected/return tracking.
     */
    public function up(): void
    {
        Schema::table('grn_line_items', function (Blueprint $table) {
            $table->decimal('rejected_qty', 10, 3)->default(0)->after('accepted_qty')
                  ->comment('Quantity rejected by QC');
            $table->decimal('return_qty', 10, 3)->default(0)->after('rejected_qty')
                  ->comment('Quantity to be returned to vendor (RTV)');
            $table->string('return_remarks', 500)->nullable()->after('return_qty')
                  ->comment('Remarks for return quantity');
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::table('grn_line_items', function (Blueprint $table) {
            $table->dropColumn(['rejected_qty', 'return_qty', 'return_remarks']);
        });
    }
};
