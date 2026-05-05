<?php

namespace App\Http\Controllers;

use App\Models\Tenant\ProductionForecast;
use App\Models\Tenant\ProductionGapAnalysis;
use App\Models\Tenant\ProductionCapacity;
use App\Models\Tenant\ProductionOrder;
use App\Models\Tenant\SalesOrder;
use App\Models\Tenant\SalesOrderLineItem;
use App\Models\Tenant\Product;
use App\Services\StockQueryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProductionPlanningController extends Controller
{
    public function __construct(protected StockQueryService $stockQueryService)
    {
    }
    /**
     * Get forecast data for dashboard
     */
    public function getForecastData(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->addMonths(3)->endOfMonth()->toDateString());
        $productId = $request->input('product_id');

        $query = ProductionForecast::with('product')
            ->whereBetween('forecast_date', [$startDate, $endDate]);
        
        // Filter by product if specified
        if ($productId) {
            $query->where('product_id', $productId);
        }
        
        $forecasts = $query->orderBy('forecast_date')
            ->get()
            ->map(function ($item) {
                return [
                    'id'               => $item->id,
                    'product_id'       => $item->product_id,
                    'product_code'     => $item->product->product_code ?? '',
                    'product_name'     => $item->product->product_name ?? '',
                    'date'             => $item->forecast_date->format('Y-m-d'),
                    'forecasted_qty'   => (float) $item->forecasted_qty,
                    'actual_demand_qty'=> (float) $item->actual_demand_qty,
                    'current_stock'    => (float) $item->current_stock,
                    'planned_production'=> (float) $item->planned_production,
                    'source'           => $item->source,
                    'remarks'          => $item->remarks,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $forecasts->values(),
        ]);
    }

    /**
     * Get gap analysis data
     */
    public function getGapAnalysis(Request $request)
    {
        $analysisDate = $request->input('analysis_date', now()->toDateString());

        $gaps = ProductionGapAnalysis::with('product')
            ->where('analysis_date', $analysisDate)
            ->orderBy('gap_status', 'desc')
            ->orderBy('gap_qty')
            ->get()
            ->map(function ($gap) {
                return [
                    'id' => $gap->id,
                    'product_id' => $gap->product_id,
                    'product_code' => $gap->product->product_code ?? '',
                    'product_name' => $gap->product->product_name ?? '',
                    'demand_qty' => (float) $gap->demand_qty,
                    'available_stock' => (float) $gap->available_stock,
                    'planned_production_qty' => (float) $gap->planned_production_qty,
                    'gap_qty' => (float) $gap->gap_qty,
                    'gap_status' => $gap->gap_status,
                    'capacity_utilization' => (float) $gap->capacity_utilization,
                    'recommendations' => $gap->recommendations,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $gaps,
        ]);
    }

    /**
     * Generate forecast based on sales orders and historical data
     */
    public function generateForecast(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'product_ids' => 'nullable|array',
        ]);

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $productIds = $request->product_ids ?? Product::pluck('id')->toArray();

        DB::beginTransaction();
        try {
            foreach ($productIds as $productId) {
                // Get sales order demand
                $salesDemand = SalesOrderLineItem::where('product_id', $productId)
                    ->whereHas('salesOrder', function ($q) use ($startDate, $endDate) {
                        $q->whereBetween('required_delivery_date', [$startDate, $endDate])
                          ->whereIn('status', ['CONFIRMED', 'STOCK_CHECKED', 'PICKING', 'PACKED']);
                    })
                    ->sum('qty');

                // Get current stock using StockQueryService
                $currentStock = $this->stockQueryService->getAvailableProductStock($productId);

                // Get planned production
                $plannedProduction = ProductionOrder::where('product_id', $productId)
                    ->whereBetween('planned_date', [$startDate, $endDate])
                    ->whereIn('status', ['DRAFT', 'IN_PROGRESS'])
                    ->sum('target_qty');

                // Calculate forecast (simple average for now)
                $forecastedQty = $salesDemand > 0 ? $salesDemand : 0;

                // Create or update forecast
                ProductionForecast::updateOrCreate(
                    [
                        'product_id' => $productId,
                        'forecast_date' => $startDate->toDateString(),
                    ],
                    [
                        'forecasted_qty' => $forecastedQty,
                        'actual_demand_qty' => $salesDemand,
                        'current_stock' => $currentStock,
                        'planned_production' => $plannedProduction,
                        'source' => 'SYSTEM',
                        'created_by' => auth()->id(),
                    ]
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Forecast generated successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate forecast: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store individual forecast calculation
     */
    public function storeForecast(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|integer|exists:tenant.product_master,id',
            'forecast_month' => 'required|string|size:7', // YYYY-MM format
            'forecast_quantity' => 'required|numeric|min:0',
            'previous_month_sales' => 'required|numeric|min:0',
            'growth_percentage' => 'required|numeric|min:0|max:100',
            'remarks' => 'nullable|string|max:1000',
        ]);

        DB::connection('tenant')->beginTransaction();
        try {
            // Parse forecast month to get the first day of that month
            $forecastDate = Carbon::createFromFormat('Y-m', $validated['forecast_month'])->startOfMonth();
            
            // Get current stock using StockQueryService
            $currentStock = $this->stockQueryService->getAvailableProductStock($validated['product_id']);
            
            // Get planned production for the forecast month
            $plannedProduction = ProductionOrder::where('product_id', $validated['product_id'])
                ->whereYear('planned_date', $forecastDate->year)
                ->whereMonth('planned_date', $forecastDate->month)
                ->whereIn('status', ['DRAFT', 'IN_PROGRESS'])
                ->sum('target_qty');
            
            // Create calculation formula
            $formula = sprintf(
                '%s × (1 + %s%% / 100) = %s',
                number_format($validated['previous_month_sales'], 0),
                number_format($validated['growth_percentage'], 1),
                number_format($validated['forecast_quantity'], 0)
            );
            
            // Create forecast record
            $forecast = ProductionForecast::create([
                'product_id' => $validated['product_id'],
                'forecast_date' => $forecastDate->toDateString(),
                'forecast_month' => $validated['forecast_month'],
                'forecasted_qty' => $validated['forecast_quantity'],
                'actual_demand_qty' => 0, // Will be updated as actual sales occur
                'previous_month_sales' => $validated['previous_month_sales'],
                'growth_percentage' => $validated['growth_percentage'],
                'calculation_formula' => $formula,
                'current_stock' => $currentStock,
                'planned_production' => $plannedProduction,
                'source' => 'MANUAL',
                'remarks' => $validated['remarks'],
                'created_by' => $request->input('auth_user_id'),
            ]);

            DB::connection('tenant')->commit();

            return response()->json([
                'success' => true,
                'message' => 'Forecast saved successfully',
                'data' => $forecast->load('product'),
            ], 201);
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to save forecast: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Run gap analysis
     */
    public function runGapAnalysis(Request $request)
    {
        $request->validate([
            'analysis_date' => 'required|date',
            'product_ids' => 'nullable|array',
        ]);

        $analysisDate = Carbon::parse($request->analysis_date);
        $productIds = $request->product_ids ?? Product::pluck('id')->toArray();

        DB::beginTransaction();
        try {
            foreach ($productIds as $productId) {
                // Get demand from sales orders
                $salesDemand = SalesOrderLineItem::where('product_id', $productId)
                    ->whereHas('salesOrder', function ($q) use ($analysisDate) {
                        $q->where('required_delivery_date', '<=', $analysisDate)
                          ->whereIn('status', ['CONFIRMED', 'STOCK_CHECKED', 'PICKING', 'PACKED']);
                    })
                    ->sum('qty');

                // Get forecasted demand
                $forecastDemand = ProductionForecast::where('product_id', $productId)
                    ->where('forecast_date', $analysisDate)
                    ->sum('forecasted_qty');

                $totalDemand = max($salesDemand, $forecastDemand);

                // Get available stock using StockQueryService
                $availableStock = $this->stockQueryService->getAvailableProductStock($productId);

                // Get planned production
                $plannedProduction = ProductionOrder::where('product_id', $productId)
                    ->where('planned_date', '<=', $analysisDate)
                    ->whereIn('status', ['DRAFT', 'IN_PROGRESS'])
                    ->sum('target_qty');

                // Calculate gap
                $gapQty = ($availableStock + $plannedProduction) - $totalDemand;
                $gapStatus = ProductionGapAnalysis::determineGapStatus($gapQty, $totalDemand);

                // Calculate capacity utilization (if capacity data exists)
                $capacity = ProductionCapacity::where('product_id', $productId)
                    ->where('capacity_date', $analysisDate)
                    ->first();

                $capacityUtilization = 0;
                if ($capacity && $capacity->daily_capacity > 0) {
                    $capacityUtilization = ($plannedProduction / $capacity->daily_capacity) * 100;
                }

                // Generate recommendations
                $recommendations = $this->generateRecommendations($gapStatus, $gapQty, $totalDemand);

                // Create or update gap analysis
                ProductionGapAnalysis::updateOrCreate(
                    [
                        'product_id' => $productId,
                        'analysis_date' => $analysisDate->toDateString(),
                    ],
                    [
                        'demand_qty' => $totalDemand,
                        'available_stock' => $availableStock,
                        'planned_production_qty' => $plannedProduction,
                        'gap_qty' => $gapQty,
                        'gap_status' => $gapStatus,
                        'capacity_utilization' => $capacityUtilization,
                        'recommendations' => $recommendations,
                        'created_by' => auth()->id(),
                    ]
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Gap analysis completed successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to run gap analysis: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate recommendations based on gap analysis
     */
    private function generateRecommendations(string $gapStatus, float $gapQty, float $demand): string
    {
        switch ($gapStatus) {
            case 'CRITICAL':
                return "URGENT: Shortage of " . abs($gapQty) . " units. Immediate production planning required. Consider expediting production orders or sourcing alternatives.";
            case 'SHORTAGE':
                return "Shortage of " . abs($gapQty) . " units detected. Plan additional production orders to meet demand.";
            case 'SURPLUS':
                return "Surplus of " . $gapQty . " units. Consider reducing production or reallocating capacity.";
            case 'BALANCED':
                return "Production and demand are balanced. Continue monitoring.";
            default:
                return "No specific recommendations.";
        }
    }

    /**
     * Get capacity planning data
     */
    public function getCapacity(Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));
        [$year, $mon] = explode('-', $month);

        $capacities = ProductionCapacity::with('product')
            ->whereYear('capacity_date', $year)
            ->whereMonth('capacity_date', $mon)
            ->orderBy('capacity_date')
            ->get()
            ->map(function ($c) {
                return [
                    'id'                 => $c->id,
                    'product_id'         => $c->product_id,
                    'product_code'       => $c->product->product_code ?? 'N/A',
                    'product_name'       => $c->product->product_name ?? 'Overall',
                    'capacity_date'      => $c->capacity_date->format('Y-m-d'),
                    'daily_capacity'     => (float) $c->daily_capacity,
                    'utilized_capacity'  => (float) $c->utilized_capacity,
                    'available_capacity' => (float) $c->available_capacity,
                    'utilization_pct'    => $c->daily_capacity > 0
                        ? round(($c->utilized_capacity / $c->daily_capacity) * 100, 1)
                        : 0,
                    'shift'              => $c->shift,
                    'remarks'            => $c->remarks,
                ];
            });

        // Summary
        $totalCapacity  = $capacities->sum('daily_capacity');
        $totalUtilized  = $capacities->sum('utilized_capacity');

        return response()->json([
            'success' => true,
            'data'    => $capacities->values(),
            'summary' => [
                'total_capacity'   => $totalCapacity,
                'total_utilized'   => $totalUtilized,
                'overall_pct'      => $totalCapacity > 0 ? round(($totalUtilized / $totalCapacity) * 100, 1) : 0,
                'records'          => $capacities->count(),
            ],
        ]);
    }

    /**
     * Store or update a capacity record
     */
    public function storeCapacity(Request $request)
    {
        $validated = $request->validate([
            'product_id'        => 'nullable|integer',
            'capacity_date'     => 'required|date',
            'daily_capacity'    => 'required|numeric|min:0',
            'utilized_capacity' => 'nullable|numeric|min:0',
            'shift'             => 'required|in:SINGLE,DOUBLE,TRIPLE',
            'remarks'           => 'nullable|string|max:500',
        ]);

        // Validate product exists in tenant DB if provided
        if (!empty($validated['product_id'])) {
            $exists = Product::where('id', $validated['product_id'])->exists();
            if (!$exists) {
                return response()->json(['success' => false, 'message' => 'Product not found.'], 422);
            }
        }

        $utilized  = (float) ($validated['utilized_capacity'] ?? 0);
        $daily     = (float) $validated['daily_capacity'];
        $available = max(0, $daily - $utilized);

        $record = ProductionCapacity::updateOrCreate(
            [
                'product_id'    => $validated['product_id'] ?? null,
                'capacity_date' => $validated['capacity_date'],
            ],
            [
                'daily_capacity'    => $daily,
                'utilized_capacity' => $utilized,
                'available_capacity'=> $available,
                'shift'             => $validated['shift'],
                'remarks'           => $validated['remarks'] ?? null,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Capacity record saved.',
            'data'    => $record,
        ]);
    }

    /**
     * Delete a capacity record
     */
    public function deleteCapacity(int $id)
    {
        $record = ProductionCapacity::findOrFail($id);
        $record->delete();

        return response()->json(['success' => true, 'message' => 'Capacity record deleted.']);
    }

    /**
     * Get planning summary for dashboard
     */
    public function getPlanningSummary(Request $request)
    {
        $today = now()->toDateString();

        // Get gap analysis summary
        $gapSummary = ProductionGapAnalysis::where('analysis_date', $today)
            ->selectRaw('gap_status, COUNT(*) as count')
            ->groupBy('gap_status')
            ->get()
            ->pluck('count', 'gap_status');

        // Get forecast accuracy (last 30 days)
        $forecastAccuracy = ProductionForecast::where('forecast_date', '>=', now()->subDays(30))
            ->where('actual_demand_qty', '>', 0)
            ->selectRaw('AVG(ABS(forecasted_qty - actual_demand_qty) / actual_demand_qty * 100) as avg_error')
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'gap_summary' => [
                    'critical' => $gapSummary['CRITICAL'] ?? 0,
                    'shortage' => $gapSummary['SHORTAGE'] ?? 0,
                    'balanced' => $gapSummary['BALANCED'] ?? 0,
                    'surplus' => $gapSummary['SURPLUS'] ?? 0,
                ],
                'forecast_accuracy' => 100 - ($forecastAccuracy->avg_error ?? 0),
            ],
        ]);
    }
}
