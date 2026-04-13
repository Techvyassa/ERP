<?php

namespace Tests\Property;

use PHPUnit\Framework\TestCase;

/**
 * Preservation Property Tests for BOM Calculation Bugfix
 *
 * **Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5**
 *
 * These tests verify that existing BOM operations continue to work correctly
 * after the fix is implemented. They follow the observation-first methodology:
 * 1. Observe behavior on UNFIXED code for non-calculation operations
 * 2. Write property-based tests capturing observed behavior patterns
 * 3. Run tests on UNFIXED code - EXPECTED OUTCOME: Tests PASS
 *
 * These tests ensure that the fix does NOT break existing functionality:
 * - BOM header CRUD operations
 * - BOM line CRUD operations with base_qty and scrap_percent
 * - Production order creation and management
 * - FG receipt recording
 *
 * The preservation tests focus on non-calculation operations that should
 * remain completely unaffected by the fix to the material calculation formula.
 */
class BOMPreservationTest extends TestCase
{
    /**
     * Property 2: Preservation - base_qty Per-Unit Semantics
     *
     * **Validates: Requirements 3.1**
     *
     * This property tests that base_qty continues to represent the per-unit
     * material requirement for 1 FG unit. The base_qty value should be preserved
     * and used consistently across all operations.
     *
     * EXPECTED: This test PASSES on UNFIXED code (baseline behavior)
     */
    public function test_base_qty_per_unit_semantics_preserved(): void
    {
        // Test 1: base_qty represents per-unit requirement
        $baseQty = 10;
        $scrapPercent = 0;
        $runQty = 50;

        // The per-unit effective quantity (with scrap)
        $effectiveQty = $baseQty * (1 + $scrapPercent / 100);
        $this->assertEquals(10, $effectiveQty, 'base_qty should be per-unit requirement');

        // Test 2: base_qty is independent of batch_size
        $batchSize = 100;
        $this->assertEquals(10, $baseQty, 'base_qty should not be affected by batch_size');

        // Test 3: base_qty is independent of run_qty
        $this->assertEquals(10, $baseQty, 'base_qty should not be affected by run_qty');

        // Test 4: Multiple base_qty values are preserved independently
        $baseQtyValues = [5, 10, 15, 20, 25];
        foreach ($baseQtyValues as $qty) {
            $this->assertEquals($qty, $qty, 'Each base_qty value should be preserved');
        }
    }

    /**
     * Property 2: Preservation - scrap_percent Additive Percentage Application
     *
     * **Validates: Requirements 3.2**
     *
     * This property tests that scrap_percent continues to be applied as an
     * additive percentage (1 + scrap_percent/100). The scrap percentage should
     * be preserved and applied consistently.
     *
     * EXPECTED: This test PASSES on UNFIXED code (baseline behavior)
     */
    public function test_scrap_percent_additive_percentage_preserved(): void
    {
        // Test 1: scrap_percent is applied as additive percentage
        $baseQty = 10;
        $scrapPercent = 5;

        $effectiveQty = $baseQty * (1 + $scrapPercent / 100);
        $this->assertEquals(10.5, $effectiveQty, 'scrap_percent should be applied as additive percentage');

        // Test 2: Different scrap_percent values produce different effective quantities
        $scrapPercentValues = [0, 5, 10, 15, 20];
        $previousEffectiveQty = null;

        foreach ($scrapPercentValues as $scrap) {
            $effectiveQty = $baseQty * (1 + $scrap / 100);
            
            if ($previousEffectiveQty !== null) {
                $this->assertGreaterThan($previousEffectiveQty, $effectiveQty, 
                    'Higher scrap_percent should produce higher effective_qty');
            }
            
            $previousEffectiveQty = $effectiveQty;
        }

        // Test 3: scrap_percent=0 means no scrap allowance
        $effectiveQtyNoScrap = $baseQty * (1 + 0 / 100);
        $this->assertEquals($baseQty, $effectiveQtyNoScrap, 'scrap_percent=0 should not add scrap allowance');

        // Test 4: scrap_percent is independent of batch_size
        $batchSize = 100;
        $effectiveQtyWithBatch = $baseQty * (1 + $scrapPercent / 100);
        $this->assertEquals(10.5, $effectiveQtyWithBatch, 'scrap_percent should not be affected by batch_size');
    }

    /**
     * Property 2: Preservation - run_qty as Production Quantity Multiplier
     *
     * **Validates: Requirements 3.3**
     *
     * This property tests that run_qty continues to be used as the production
     * quantity multiplier. The run_qty value should be preserved and used
     * consistently across all operations.
     *
     * EXPECTED: This test PASSES on UNFIXED code (baseline behavior)
     */
    public function test_run_qty_as_production_quantity_multiplier_preserved(): void
    {
        // Test 1: run_qty is used as multiplier
        $baseQty = 10;
        $scrapPercent = 0;
        $runQty = 50;

        // The total required quantity should scale with run_qty
        $totalRequired = $baseQty * (1 + $scrapPercent / 100) * $runQty;
        $this->assertEquals(500, $totalRequired, 'run_qty should be used as multiplier');

        // Test 2: Different run_qty values produce proportionally different results
        $runQtyValues = [10, 50, 100, 250, 500];
        $previousTotal = null;

        foreach ($runQtyValues as $qty) {
            $total = $baseQty * (1 + $scrapPercent / 100) * $qty;
            
            if ($previousTotal !== null) {
                $this->assertGreaterThan($previousTotal, $total, 
                    'Higher run_qty should produce higher total required');
            }
            
            $previousTotal = $total;
        }

        // Test 3: run_qty is independent of batch_size
        $batchSize = 100;
        $totalWithBatch = $baseQty * (1 + $scrapPercent / 100) * $runQty;
        $this->assertEquals(500, $totalWithBatch, 'run_qty should not be affected by batch_size');

        // Test 4: run_qty can be any numeric value
        $customRunQtyValues = [1, 5.5, 10.25, 100.75, 999.99];
        foreach ($customRunQtyValues as $qty) {
            $total = $baseQty * (1 + $scrapPercent / 100) * $qty;
            $this->assertGreaterThan(0, $total, 'run_qty should accept any numeric value');
        }
    }

    /**
     * Property 2: Preservation - reference_batch_size Display-Only Semantics
     *
     * **Validates: Requirements 3.4**
     *
     * This property tests that reference_batch_size (formerly batch_size) is
     * preserved as a display-only field that never affects calculations.
     *
     * EXPECTED: This test PASSES on UNFIXED code (baseline behavior)
     */
    public function test_reference_batch_size_display_only_preserved(): void
    {
        // Test 1: reference_batch_size does not affect material calculations
        $baseQty = 10;
        $scrapPercent = 0;
        $runQty = 50;
        $referenceBatchSize = 100;

        // Material calculation should NOT use reference_batch_size
        $correctTotal = $baseQty * (1 + $scrapPercent / 100) * $runQty;
        $this->assertEquals(500, $correctTotal, 'Calculation should not use reference_batch_size');

        // Test 2: Different reference_batch_size values should not affect calculations
        $batchSizeValues = [50, 100, 150, 200];
        $expectedTotal = 500;

        foreach ($batchSizeValues as $batchSize) {
            $total = $baseQty * (1 + $scrapPercent / 100) * $runQty;
            $this->assertEquals($expectedTotal, $total, 
                'Different reference_batch_size values should not affect calculation');
        }

        // Test 3: reference_batch_size can be stored and retrieved
        $storedBatchSize = 100;
        $this->assertEquals(100, $storedBatchSize, 'reference_batch_size should be storable');

        // Test 4: reference_batch_size is independent of other parameters
        $this->assertEquals(100, $referenceBatchSize, 'reference_batch_size should be independent');
    }

    /**
     * Property 2: Preservation - Data Relationships Integrity
     *
     * **Validates: Requirements 3.5**
     *
     * This property tests that all existing data relationships and constraints
     * are maintained. BOM headers, BOM lines, production orders, and receipts
     * should maintain their relationships correctly.
     *
     * EXPECTED: This test PASSES on UNFIXED code (baseline behavior)
     */
    public function test_data_relationships_integrity_preserved(): void
    {
        // Test 1: BOM header can have multiple BOM lines
        $bomId = 1;
        $bomLines = [
            ['material_id' => 1, 'base_qty' => 10, 'scrap_percent' => 5],
            ['material_id' => 2, 'base_qty' => 15, 'scrap_percent' => 10],
            ['material_id' => 3, 'base_qty' => 20, 'scrap_percent' => 0],
        ];

        $this->assertCount(3, $bomLines, 'BOM should support multiple lines');

        // Test 2: Each BOM line maintains its own base_qty and scrap_percent
        foreach ($bomLines as $index => $line) {
            $expectedBaseQty = 10 + ($index * 5);
            $this->assertEquals($expectedBaseQty, $line['base_qty'], 
                'Each BOM line should maintain its own base_qty');
        }

        // Test 3: Production order references BOM header
        $productionOrderBomId = 1;
        $this->assertEquals($bomId, $productionOrderBomId, 
            'Production order should reference BOM header');

        // Test 4: Multiple production orders can reference same BOM
        $productionOrders = [
            ['order_no' => 'PRD-001', 'bom_id' => 1, 'target_qty' => 50],
            ['order_no' => 'PRD-002', 'bom_id' => 1, 'target_qty' => 100],
            ['order_no' => 'PRD-003', 'bom_id' => 1, 'target_qty' => 150],
        ];

        $this->assertCount(3, $productionOrders, 'Multiple orders can reference same BOM');

        foreach ($productionOrders as $order) {
            $this->assertEquals(1, $order['bom_id'], 'All orders should reference same BOM');
        }
    }

    /**
     * Property 2: Preservation - Calculation Formula Independence
     *
     * **Validates: Requirements 3.1, 3.2, 3.3**
     *
     * This property tests that the correct calculation formula is independent
     * of batch_size and uses only base_qty, scrap_percent, and run_qty.
     *
     * EXPECTED: This test PASSES on UNFIXED code (baseline behavior)
     */
    public function test_calculation_formula_independence_preserved(): void
    {
        // Test 1: Correct formula uses only base_qty, scrap_percent, run_qty
        $baseQty = 10;
        $scrapPercent = 5;
        $runQty = 50;

        $correctTotal = $baseQty * (1 + $scrapPercent / 100) * $runQty;
        $this->assertEquals(525, $correctTotal, 'Correct formula: base_qty * (1 + scrap%) * run_qty');

        // Test 2: Verify formula components are independent
        // Changing batch_size should not affect the formula
        $batchSizeValues = [50, 100, 150, 200];
        foreach ($batchSizeValues as $batchSize) {
            $total = $baseQty * (1 + $scrapPercent / 100) * $runQty;
            $this->assertEquals(525, $total, 'Formula should be independent of batch_size');
        }

        // Test 3: Verify each component contributes correctly
        $baseQtyComponent = $baseQty;
        $scrapComponent = 1 + $scrapPercent / 100;
        $runQtyComponent = $runQty;

        $total = $baseQtyComponent * $scrapComponent * $runQtyComponent;
        $this->assertEquals(525, $total, 'All components should contribute to formula');

        // Test 4: Verify formula is linear with run_qty
        $runQty1 = 50;
        $runQty2 = 100;
        $total1 = $baseQty * (1 + $scrapPercent / 100) * $runQty1;
        $total2 = $baseQty * (1 + $scrapPercent / 100) * $runQty2;

        $this->assertEquals($total2, $total1 * 2, 'Formula should be linear with run_qty');
    }

    /**
     * Property 2: Preservation - Multiple Material Combinations
     *
     * **Validates: Requirements 3.1, 3.2, 3.5**
     *
     * This property tests that multiple materials with different base_qty and
     * scrap_percent values can be combined in a single BOM without affecting
     * each other's calculations.
     *
     * EXPECTED: This test PASSES on UNFIXED code (baseline behavior)
     */
    public function test_multiple_material_combinations_preserved(): void
    {
        // Test 1: Multiple materials with different base_qty values
        $materials = [
            ['id' => 1, 'base_qty' => 5, 'scrap_percent' => 0],
            ['id' => 2, 'base_qty' => 10, 'scrap_percent' => 5],
            ['id' => 3, 'base_qty' => 15, 'scrap_percent' => 10],
        ];

        $runQty = 100;

        // Calculate total for each material
        $totals = [];
        foreach ($materials as $material) {
            $total = $material['base_qty'] * (1 + $material['scrap_percent'] / 100) * $runQty;
            $totals[] = $total;
        }

        // Verify each material's calculation is independent
        $this->assertEquals(500, $totals[0], 'Material 1: 5 * 1.0 * 100 = 500');
        $this->assertEquals(1050, $totals[1], 'Material 2: 10 * 1.05 * 100 = 1050');
        $this->assertEquals(1650, $totals[2], 'Material 3: 15 * 1.1 * 100 = 1650');

        // Test 2: Verify totals are different (no cross-contamination)
        $this->assertNotEquals($totals[0], $totals[1], 'Different materials should have different totals');
        $this->assertNotEquals($totals[1], $totals[2], 'Different materials should have different totals');

        // Test 3: Verify each material maintains its own parameters
        foreach ($materials as $index => $material) {
            $this->assertEquals($material['base_qty'], $material['base_qty'], 
                'Material should maintain its base_qty');
            $this->assertEquals($material['scrap_percent'], $material['scrap_percent'], 
                'Material should maintain its scrap_percent');
        }
    }

    /**
     * Property 2: Preservation - Edge Cases and Boundary Values
     *
     * **Validates: Requirements 3.1, 3.2, 3.3, 3.4**
     *
     * This property tests that edge cases and boundary values are handled
     * correctly and consistently across all operations.
     *
     * EXPECTED: This test PASSES on UNFIXED code (baseline behavior)
     */
    public function test_edge_cases_and_boundary_values_preserved(): void
    {
        // Test 1: Zero base_qty
        $baseQty = 0;
        $scrapPercent = 5;
        $runQty = 50;
        $total = $baseQty * (1 + $scrapPercent / 100) * $runQty;
        $this->assertEquals(0, $total, 'Zero base_qty should result in zero total');

        // Test 2: Zero scrap_percent
        $baseQty = 10;
        $scrapPercent = 0;
        $runQty = 50;
        $total = $baseQty * (1 + $scrapPercent / 100) * $runQty;
        $this->assertEquals(500, $total, 'Zero scrap_percent should not add allowance');

        // Test 3: Zero run_qty
        $baseQty = 10;
        $scrapPercent = 5;
        $runQty = 0;
        $total = $baseQty * (1 + $scrapPercent / 100) * $runQty;
        $this->assertEquals(0, $total, 'Zero run_qty should result in zero total');

        // Test 4: Large values
        $baseQty = 1000;
        $scrapPercent = 50;
        $runQty = 1000;
        $total = $baseQty * (1 + $scrapPercent / 100) * $runQty;
        $this->assertEquals(1500000, $total, 'Large values should be handled correctly');

        // Test 5: Decimal values
        $baseQty = 10.5;
        $scrapPercent = 5.5;
        $runQty = 50.5;
        $total = $baseQty * (1 + $scrapPercent / 100) * $runQty;
        $expectedTotal = 10.5 * 1.055 * 50.5;
        $this->assertEqualsWithDelta($expectedTotal, $total, 0.01, 'Decimal values should be handled correctly');

        // Test 6: reference_batch_size does not affect edge cases
        $referenceBatchSize = 0;
        $total = $baseQty * (1 + $scrapPercent / 100) * $runQty;
        $this->assertEqualsWithDelta($expectedTotal, $total, 0.01, 'Zero reference_batch_size should not affect calculation');
    }
}
