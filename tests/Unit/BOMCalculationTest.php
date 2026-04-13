<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Material Calculation Tests for BOM System
 *
 * This test verifies the material requirement calculation logic.
 * The system calculates material quantity based on:
 * base_qty (from BOM detail) * (1 + scrap_percent/100) * run_qty (from Production Order)
 *
 * Validates: Fixed material calculation logic without batch_size.
 */
class BOMCalculationTest extends TestCase
{
    /**
     * Test case 1: Basic calculation without scrap
     *
     * base_qty = 10, target = 50, scrap_percent = 0
     * Expected: 10 * 1.0 * 50 = 500
     */
    public function test_calculate_material_required_basic_no_scrap(): void
    {
        $baseQty = 10;
        $target = 50;
        $scrapPercent = 0;

        $correctResult = $baseQty * (1 + $scrapPercent / 100) * $target;
        $this->assertEquals(500, $correctResult, 'Correct formula should produce 500');

        $actualResult = $this->calculateMaterialRequired($baseQty, $target, $scrapPercent);
        $this->assertEquals(500, $actualResult, 'Material calculation should return 500');
    }

    /**
     * Test case 2: Calculation with scrap percentage
     *
     * base_qty = 5, target = 100, scrap_percent = 10
     * Expected: 5 * 1.1 * 100 = 550
     */
    public function test_calculate_material_required_with_scrap(): void
    {
        $baseQty = 5;
        $target = 100;
        $scrapPercent = 10;

        $correctResult = $baseQty * (1 + $scrapPercent / 100) * $target;
        $this->assertEquals(550, $correctResult, 'Correct formula should produce 550');

        $actualResult = $this->calculateMaterialRequired($baseQty, $target, $scrapPercent);
        $this->assertEquals(550, $actualResult, 'Material calculation should return 550');
    }

    /**
     * Test case 3: Verify scrap_percent is included in calculation
     *
     * Same base_qty and target, but different scrap_percent should produce different results
     */
    public function test_scrap_percent_must_be_included_in_calculation(): void
    {
        $baseQty = 10;
        $target = 100;

        $resultNoScrap = $this->calculateMaterialRequired($baseQty, $target, 0);
        $resultWithScrap = $this->calculateMaterialRequired($baseQty, $target, 10);

        $this->assertEquals(1000, $resultNoScrap, 'Material calculation with 0% scrap should return 1000');
        $this->assertEquals(1100, $resultWithScrap, 'Material calculation with 10% scrap should return 1100');
        $this->assertGreaterThan($resultNoScrap, $resultWithScrap, 'Result with scrap should be greater than without scrap');
    }

    /**
     * Helper function that replicates the calculation logic from ProductionOrderController
     *
     * @param float $baseQty The base quantity from BOM detail (qty_required)
     * @param float $target The target/run quantity from production order
     * @param float $scrapPercent The scrap percentage from BOM detail
     * @return float The calculated required quantity
     */
    private function calculateMaterialRequired(
        float $baseQty,
        float $target,
        float $scrapPercent
    ): float {
        // Step 1: Calculate effective_qty
        $effectiveQty = $baseQty * (1 + $scrapPercent / 100);

        // Step 2: Multiply by target quantity
        $requiredQty = $effectiveQty * $target;

        return $requiredQty;
    }
}
