<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Enforce one inspection lot per GRN line item (single batch/lot per line).
     */
    public function up(): void
    {
        Schema::connection('tenant')->table('inspection_lots', function (Blueprint $table) {
            $table->unique('grn_line_id', 'uq_inspection_lot_grn_line');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->table('inspection_lots', function (Blueprint $table) {
            $table->dropUnique('uq_inspection_lot_grn_line');
        });
    }
};
