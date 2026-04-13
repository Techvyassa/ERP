<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QCParametersSeeder extends Seeder
{
    /**
     * Run the database seeders.
     */
    public function run(): void
    {
        $now = now();
        
        // QC Parameters for Material ID 1 (example material)
        $parameters = [
            [
                'material_id' => 1,
                'parameter_code' => 'PURITY',
                'parameter_name' => 'Purity %',
                'parameter_category' => 'Chemical',
                'data_type' => 'NUMERIC',
                'tolerance_type' => 'RANGE',
                'standard_min' => '99.0',
                'standard_max' => '100.0',
                'standard_value' => '99.5',
                'unit_of_measurement' => '%',
                'test_method' => 'Lab Analysis',
                'is_critical' => true,
                'display_order' => 1,
                'is_active' => true,
                'created_by' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'material_id' => 1,
                'parameter_code' => 'MOISTURE',
                'parameter_name' => 'Moisture %',
                'parameter_category' => 'Chemical',
                'data_type' => 'NUMERIC',
                'tolerance_type' => 'MAX_ONLY',
                'standard_min' => null,
                'standard_max' => '2.0',
                'standard_value' => '1.5',
                'unit_of_measurement' => '%',
                'test_method' => 'Drying Method',
                'is_critical' => true,
                'display_order' => 2,
                'is_active' => true,
                'created_by' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'material_id' => 1,
                'parameter_code' => 'COLOR',
                'parameter_name' => 'Color',
                'parameter_category' => 'Physical',
                'data_type' => 'TEXT',
                'tolerance_type' => 'EXACT',
                'standard_min' => null,
                'standard_max' => null,
                'standard_value' => 'White',
                'unit_of_measurement' => null,
                'test_method' => 'Visual Inspection',
                'is_critical' => false,
                'display_order' => 3,
                'is_active' => true,
                'created_by' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'material_id' => 1,
                'parameter_code' => 'DENSITY',
                'parameter_name' => 'Density',
                'parameter_category' => 'Physical',
                'data_type' => 'NUMERIC',
                'tolerance_type' => 'RANGE',
                'standard_min' => '0.95',
                'standard_max' => '1.05',
                'standard_value' => '1.00',
                'unit_of_measurement' => 'g/cm3',
                'test_method' => 'Hydrometer',
                'is_critical' => false,
                'display_order' => 4,
                'is_active' => true,
                'created_by' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'material_id' => 1,
                'parameter_code' => 'PH',
                'parameter_name' => 'pH Value',
                'parameter_category' => 'Chemical',
                'data_type' => 'NUMERIC',
                'tolerance_type' => 'RANGE',
                'standard_min' => '6.5',
                'standard_max' => '7.5',
                'standard_value' => '7.0',
                'unit_of_measurement' => null,
                'test_method' => 'pH Meter',
                'is_critical' => true,
                'display_order' => 5,
                'is_active' => true,
                'created_by' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($parameters as $parameter) {
            DB::connection('tenant')->table('qc_parameters_master')->updateOrInsert(
                [
                    'material_id' => $parameter['material_id'], 
                    'parameter_code' => $parameter['parameter_code']
                ],
                $parameter
            );
        }
    }
}