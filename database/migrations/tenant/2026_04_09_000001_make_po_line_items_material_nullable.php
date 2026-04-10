<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->table('po_line_items', function (Blueprint $table) {
            $table->unsignedBigInteger('material_id')->nullable()->change();
            $table->unsignedBigInteger('uom_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->table('po_line_items', function (Blueprint $table) {
            $table->unsignedBigInteger('material_id')->nullable(false)->change();
            $table->unsignedBigInteger('uom_id')->nullable(false)->change();
        });
    }
};
