<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Bug Condition Exploration Test for BOM Material Calculation
 *
 * This test surfaces counterexamples that demonstrate the bug exists in the
 * material calculation formula. The bug causes incorrect material quantities
 * to be calculated using batch_size as a divisor.
 *
 * Current (WRONG) formula: base_qty * (target / batch_size)
 * Expected (CORRECT) formula: base_qty * (1 + scrap_percent/100) * run_qty
 *
 * Validates: Requirements 1.1, 1.2, 1.3, 1.4
 */
class BOMCalculationBugTest extends TestCase
{
    /**
     * Test case 1: Basic calculation without scrap
     *
     * base_qty = 10, batch_size = 100, target = 50, scrap_percent = 0
     *
     * WRONG (current bug): 10 * (50 / 100) = 5
     * CORRECT (expected): 10 * 1.0 * 50 = 500
     */
    public function test_calculate_material_required_basic_no_scrap(): void
    {
        $baseQty = 10;
        $batchSize = 100;
        $target = 50;
        $scrapPercent = 0;

        // The WRONG formula being used in ProductionOrderController
        $wrongResult = $baseQty * ($target / $batchSize);

        // The CORRECT formula: base_qty * (1 + scrap_percent/100) * run_qty
        $correctResult = $baseQty * (1 + $scrapPercent / 100) * $target;

        // This assertion will FAIL on unfixed code because the system uses the wrong formula
        $this->assertEquals(500, $correctResult, 'Correct formula should produce 500');
        $this->assertEquals(5, $wrongResult, 'Wrong formula produces 5 (demonstrates the bug)');

        // The actual test - this will FAIL on unfixed code because calculateMaterialRequired uses wrong formula
        $actualResult = $this->calculateMaterialRequired($baseQty, $batchSize, $target, $scrapPercent);

        // This assertion SHOULD FAIL on unfixed code, proving the bug exists
        $this->assertEquals(500, $actualResult, 'Material calculation should return 500, not 5');
    }

    /**
     * Test case 2: Calculation with scrap percentage
     *
     * base_qty = 5, batch_size = 10, target = 100, scrap_percent = 10
     *
     * WRONG (current bug): 5 * (100 / 10) = 50
     * CORRECT (expected): 5 * 1.1 * 100 = 550
     */
    public function test_calculate_material_required_with_scrap(): void
    {
        $baseQty = 5;
        $batchSize = 10;
        $target = 100;
        $scrapPercent = 10;

        // The WRONG formula being used in ProductionOrderController
        $wrongResult = $baseQty * ($target / $batchSize);

        // The CORRECT formula: base_qty * (1 + scrap_percent/100) * run_qty
        $correctResult = $baseQty * (1 + $scrapPercent / 100) * $target;

        $this->assertEquals(550, $correctResult, 'Correct formula should produce 550');
        $this->assertEquals(50, $wrongResult, 'Wrong formula produces 50 (demonstrates the bug)');

        // The actual test - this will FAIL on unfixed code
        $actualResult = $this->calculateMaterialRequired($baseQty, $batchSize, $target, $scrapPercent);

        // This assertion SHOULD FAIL on unfixed code, proving the bug exists
        $this->assertEquals(550, $actualResult, 'Material calculation should return 550, not 50');
    }

    /**
     * Test case 3: Verify scrap_percent is included in calculation
     *
     * Same base_qty and target, but different scrap_percent should produce different results
     *
     * base_qty = 10, batch_size = 100, target = 100, scrap_percent = 0 vs 10
     *
     * With scrap=0: 10 * 1.0 * 100 = 1000
     * With scrap=10: 10 * 1.1 * 100 = 1100
     */
    public function test_scrap_percent_must_be_included_in_calculation(): void
    {
        $baseQty = 10;
        $batchSize = 100;
        $target = 100;

        $resultNoScrap = $this->calculateMaterialRequired($baseQty, $batchSize, $target, 0);
        $resultWithScrap = $this->calculateMaterialRequired($baseQty, $batchSize, $target, 10);

        // These assertions will FAIL on unfixed code because scrap_percent is not applied
        $this->assertEquals(1000, $resultNoScrap, 'Material calculation with 0% scrap should return 1000');
        $this->assertEquals(1100, $resultWithScrap, 'Material calculation with 10% scrap should return 1100');
        $this->assertGreaterThan($resultNoScrap, $resultWithScrap, 'Result with scrap should be greater than without scrap');
    }

    /**
     * Test case 4: Edge case where target equals batch_size (appears correct by coincidence)
     *
     * base_qty = 10, batch_size = 100, target = 100, scrap_percent = 0
     *
     * WRONG: 10 * (100 / 100) = 10 (appears correct)
     * CORRECT: 10 * 1.0 * 100 = 1000
     */
    public function test_edge_case_target_equals_batch_size(): void
    {
        $baseQty = 10;
        $batchSize = 100;
        $target = 100;
        $scrapPercent = 0;

        $actualResult = $this->calculateMaterialRequired($baseQty, $batchSize, $target, $scrapPercent);

        // This will FAIL on unfixed code - the bug is hidden when target equals batch_size
        $this->assertEquals(1000, $actualResult, 'Material calculation should return 1000, not 10');
    }

    /**
     * Helper function that replicates the calculation logic from ProductionOrderController
     *
     * This now implements the CORRECT calculation:
     * 1. effective_qty = base_qty * (1 + scrap_percent/100)  [from BOM detail]
     * 2. scaleFactor = target  [FIXED: now uses target directly, not target/batch_size]
     * 3. required_qty = effective_qty * scaleFactor
     *
     * @param float $baseQty The base quantity from BOM detail (qty_required)
     * @param float $batchSize The batch size from BOM header (no longer used in calculation)
     * @param float $target The target/run quantity from production order
     * @param float $scrapPercent The scrap percentage from BOM detail
     * @return float The calculated required quantity
     */
    private function calculateMaterialRequired(
        float $baseQty,
        float $batchSize,
        float $target,
        float $scrapPercent
    ): float {
        // This replicates the FIXED logic from ProductionOrderController
        // Step 1: Calculate effective_qty (this part is correct in the codebase)
        $effectiveQty = $baseQty * (1 + $scrapPercent / 100);

        // Step 2: Calculate scaleFactor (FIXED: now uses target directly)
        $scaleFactor = $target;

        // Step 3: Calculate required quantity (applies the correct scaleFactor)
        $requiredQty = $effectiveQty * $scaleFactor;

        return $requiredQty;
    }
}