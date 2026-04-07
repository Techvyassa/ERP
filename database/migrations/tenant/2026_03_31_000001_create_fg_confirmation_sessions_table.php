<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->create('fg_confirmation_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('production_order_id');
            $table->decimal('confirmed_qty', 12, 3);
            $table->decimal('rejected_qty', 12, 3)->default(0);
            $table->string('rejection_reason_code', 100)->nullable();
            $table->string('fg_batch_number', 50)->nullable();
            $table->unsignedBigInteger('fg_warehouse_id')->nullable();
            $table->unsignedBigInteger('fg_bin_id')->nullable();
            $table->enum('completion_status', ['PARTIALLY_COMPLETED', 'COMPLETED']);
            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->timestampsTz();

            $table->foreign('production_order_id')->references('id')->on('production_orders')->onDelete('cascade');
            $table->foreign('confirmed_by')->references('id')->on('users')->onDelete('set null');
            $table->index('production_order_id');
        });

        // Add cumulative confirmed qty tracking to production_orders
        Schema::connection('tenant')->table('production_orders', function (Blueprint $table) {
            $table->decimal('confirmed_qty_total', 12, 3)->default(0)->after('actual_qty');
            $table->decimal('rejected_qty_total', 12, 3)->default(0)->after('confirmed_qty_total');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->table('production_orders', function (Blueprint $table) {
            $table->dropColumn(['confirmed_qty_total', 'rejected_qty_total']);
        });
        Schema::connection('tenant')->dropIfExists('fg_confirmation_sessions');
    }
};
