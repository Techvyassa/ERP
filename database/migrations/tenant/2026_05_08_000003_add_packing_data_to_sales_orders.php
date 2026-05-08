<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add packing_data JSON column to sales_orders.
     * Stores box lines directly on the SO row — no separate table needed.
     * Format: [{ box_no, item_id, item_code, item_name, qty }, ...]
     */
    public function up(): void
    {
        Schema::connection('tenant')->table('sales_orders', function (Blueprint $table) {
            $table->json('packing_data')->nullable()->after('dispatched_by')
                ->comment('Box lines recorded during packing workflow');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->table('sales_orders', function (Blueprint $table) {
            $table->dropColumn('packing_data');
        });
    }
};
