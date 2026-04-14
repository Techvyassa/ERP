<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->table('vendor_quotations', function (Blueprint $table) {
            $table->string('item_code', 100)->nullable()->after('vendor_id');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->table('vendor_quotations', function (Blueprint $table) {
            $table->dropColumn('item_code');
        });
    }
};
