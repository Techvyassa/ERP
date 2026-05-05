<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('tenant')->table('production_forecasts', function (Blueprint $table) {
            $table->string('forecast_month', 7)->nullable()->after('forecast_date')->comment('YYYY-MM format');
            $table->decimal('previous_month_sales', 12, 3)->default(0)->after('actual_demand_qty');
            $table->decimal('growth_percentage', 5, 2)->default(0)->after('previous_month_sales')->comment('Growth % used in calculation');
            $table->string('calculation_formula', 255)->nullable()->after('growth_percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->table('production_forecasts', function (Blueprint $table) {
            $table->dropColumn(['forecast_month', 'previous_month_sales', 'growth_percentage', 'calculation_formula']);
        });
    }
};
