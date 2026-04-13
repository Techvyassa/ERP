<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //

        $this->call([
            DefaultRoleSeeder::class,
            DefaultRolePermissionSeeder::class,
            QCParametersSeeder::class,
            RbacSeeder::class,
            FGStockAndSalesOrderSeeder::class,
        ]);
    }
}
