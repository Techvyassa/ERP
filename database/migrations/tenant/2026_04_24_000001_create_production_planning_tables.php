<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Production Planning Module
     * - production_forecasts: Demand forecasting based on sales orders and historical data
     * - production_gap_analysis: Gap between demand and capacity/stock
     */
    public function up(): void
    {
        // Production Forecasts Table
        Schema::connection('tenant')->create('production_forecasts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->date('forecast_date');
            $table->decimal('forecasted_qty', 12, 3)->default(0);
            $table->decimal('actual_demand_qty', 12, 3)->default(0)->comment('From confirmed sales orders');
            $table->decimal('current_stock', 12, 3)->default(0);
            $table->decimal('planned_production', 12, 3)->default(0);
            $table->enum('source', ['MANUAL', 'SALES_ORDER', 'HISTORICAL', 'SYSTEM'])->default('SYSTEM');
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestampsTz();

            $table->foreign('product_id')->references('id')->on('product_master')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['product_id', 'forecast_date']);
            $table->index('forecast_date');
        });

        // Production Gap Analysis Table
        Schema::connection('tenant')->create('production_gap_analysis', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->date('analysis_date');
            $table->decimal('demand_qty', 12, 3)->default(0)->comment('Total demand from SO + forecast');
            $table->decimal('available_stock', 12, 3)->default(0);
            $table->decimal('planned_production_qty', 12, 3)->default(0)->comment('From production orders');
            $table->decimal('gap_qty', 12, 3)->default(0)->comment('Negative = shortage, Positive = surplus');
            $table->enum('gap_status', ['SURPLUS', 'BALANCED', 'SHORTAGE', 'CRITICAL'])->default('BALANCED');
            $table->decimal('capacity_utilization', 5, 2)->default(0)->comment('Percentage of capacity used');
            $table->text('recommendations')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestampsTz();

            $table->foreign('product_id')->references('id')->on('product_master')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['product_id', 'analysis_date']);
            $table->index('gap_status');
            $table->index('analysis_date');
        });

        // Production Capacity Planning (Optional - for future enhancement)
        Schema::connection('tenant')->create('production_capacity', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->nullable()->comment('Null = overall capacity');
            $table->date('capacity_date');
            $table->decimal('daily_capacity', 12, 3)->default(0);
            $table->decimal('utilized_capacity', 12, 3)->default(0);
            $table->decimal('available_capacity', 12, 3)->default(0);
            $table->string('shift', 20)->default('SINGLE')->comment('SINGLE, DOUBLE, TRIPLE');
            $table->text('remarks')->nullable();
            $table->timestampsTz();

            $table->foreign('product_id')->references('id')->on('product_master')->onDelete('cascade');
            $table->index(['product_id', 'capacity_date']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('production_capacity');
        Schema::connection('tenant')->dropIfExists('production_gap_analysis');
        Schema::connection('tenant')->dropIfExists('production_forecasts');
    }
};
