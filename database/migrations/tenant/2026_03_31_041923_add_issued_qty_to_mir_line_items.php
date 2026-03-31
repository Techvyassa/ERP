<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection('tenant')->table('mir_line_items', function (Blueprint $table) {
            $table->decimal('issued_qty', 12, 3)->default(0)->after('required_qty');
            // Update enum if needed - SQLite doesn't support changing enum easily, 
            // but since this is usually MySQL/PostgreSQL in production, we can try.
            // For safety in this environment, we might just use string or check if we can modify it.
        });
        
        // Use a raw query to update the enum for MySQL
        if (config('database.default') === 'mysql' || config('database.connections.tenant.driver') === 'mysql') {
            DB::connection('tenant')->statement("ALTER TABLE mir_line_items MODIFY COLUMN scan_status ENUM('PENDING', 'SCANNED', 'ISSUED', 'PARTIAL') DEFAULT 'PENDING'");
        }
    }

    public function down(): void
    {
        Schema::connection('tenant')->table('mir_line_items', function (Blueprint $table) {
            $table->dropColumn('issued_qty');
        });
        
        if (config('database.default') === 'mysql' || config('database.connections.tenant.driver') === 'mysql') {
            DB::connection('tenant')->statement("ALTER TABLE mir_line_items MODIFY COLUMN scan_status ENUM('PENDING', 'SCANNED', 'ISSUED') DEFAULT 'PENDING'");
        }
    }
};
