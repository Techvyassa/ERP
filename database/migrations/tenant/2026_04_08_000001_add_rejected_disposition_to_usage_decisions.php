<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->table('usage_decisions', function (Blueprint $table) {
            $table->decimal('return_qty', 14, 3)->default(0)
                ->after('rejected_qty')
                ->comment('Portion of rejected qty to return to vendor');
            $table->decimal('scrap_qty', 14, 3)->default(0)
                ->after('return_qty')
                ->comment('Portion of rejected qty to scrap/write off');
            $table->text('return_remarks')->nullable()
                ->after('scrap_qty')
                ->comment('Remarks for vendor return');
            $table->text('scrap_remarks')->nullable()
                ->after('return_remarks')
                ->comment('Remarks for scrap write-off');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->table('usage_decisions', function (Blueprint $table) {
            $table->dropColumn(['return_qty', 'scrap_qty', 'return_remarks', 'scrap_remarks']);
        });
    }
};
