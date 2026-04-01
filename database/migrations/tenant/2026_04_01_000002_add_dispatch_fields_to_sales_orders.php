<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->table('sales_orders', function (Blueprint $table) {
            $table->string('vehicle_number', 30)->nullable()->after('remarks');
            $table->string('driver_name', 100)->nullable()->after('vehicle_number');
            $table->string('logistics_partner', 100)->nullable()->after('driver_name');
            $table->timestampTz('dispatched_at')->nullable()->after('logistics_partner');
            $table->unsignedBigInteger('dispatched_by')->nullable()->after('dispatched_at');

            $table->foreign('dispatched_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->table('sales_orders', function (Blueprint $table) {
            $table->dropForeign(['dispatched_by']);
            $table->dropColumn(['vehicle_number', 'driver_name', 'logistics_partner', 'dispatched_at', 'dispatched_by']);
        });
    }
};
