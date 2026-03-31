<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        if (config('database.default') === 'mysql' || config('database.connections.tenant.driver') === 'mysql') {
            DB::connection('tenant')->statement("ALTER TABLE material_issue_requests MODIFY COLUMN status ENUM('PENDING', 'APPROVED', 'REJECTED', 'PARTIAL', 'ISSUED') DEFAULT 'PENDING'");
        }
    }

    public function down(): void
    {
        if (config('database.default') === 'mysql' || config('database.connections.tenant.driver') === 'mysql') {
            DB::connection('tenant')->statement("ALTER TABLE material_issue_requests MODIFY COLUMN status ENUM('PENDING', 'APPROVED', 'REJECTED') DEFAULT 'PENDING'");
        }
    }
};
