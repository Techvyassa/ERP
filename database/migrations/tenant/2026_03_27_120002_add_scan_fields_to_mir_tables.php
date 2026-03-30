<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection('tenant')->table('material_issue_requests', function (Blueprint $table) {
            $table->text('rejection_reason')->nullable()->after('remarks');
        });

        Schema::connection('tenant')->table('mir_line_items', function (Blueprint $table) {
            $table->string('bin_barcode', 100)->nullable()->after('uom_id');
            $table->string('material_barcode', 100)->nullable()->after('bin_barcode');
            $table->enum('scan_status', ['PENDING', 'SCANNED', 'ISSUED'])->default('PENDING')->after('material_barcode');
            $table->unsignedBigInteger('bin_id')->nullable()->after('scan_status');
            $table->unsignedBigInteger('warehouse_id')->nullable()->after('bin_id');
            $table->timestamp('scanned_at')->nullable()->after('warehouse_id');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->table('mir_line_items', function (Blueprint $table) {
            $table->dropColumn(['bin_barcode', 'material_barcode', 'scan_status', 'bin_id', 'warehouse_id', 'scanned_at']);
        });
        Schema::connection('tenant')->table('material_issue_requests', function (Blueprint $table) {
            $table->dropColumn('rejection_reason');
        });
    }
};
