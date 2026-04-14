<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->table('purchase_orders', function (Blueprint $table) {
            $table->string('pr_number', 50)->nullable()->index()->after('po_number');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn('pr_number');
        });
    }
};
