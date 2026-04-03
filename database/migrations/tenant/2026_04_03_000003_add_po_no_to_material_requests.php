<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add po_no column to track linked procurement order
        Schema::connection('tenant')->table('maint_material_requests', function (Blueprint $table) {
            $table->string('po_no', 20)->nullable()->after('issued_on');
        });

        // Widen status enum to include 'PO Raised'
        DB::connection('tenant')->statement(
            "ALTER TABLE maint_material_requests MODIFY COLUMN status ENUM('Pending Issue','Procurement Required','PO Raised','Issued') NOT NULL DEFAULT 'Pending Issue'"
        );
    }

    public function down(): void
    {
        Schema::connection('tenant')->table('maint_material_requests', function (Blueprint $table) {
            $table->dropColumn('po_no');
        });

        DB::connection('tenant')->statement(
            "ALTER TABLE maint_material_requests MODIFY COLUMN status ENUM('Pending Issue','Procurement Required','Issued') NOT NULL DEFAULT 'Pending Issue'"
        );
    }
};
