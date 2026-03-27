<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('tenant')->table('role_permissions', function (Blueprint $table) {
            $table->string('scope', 20)->default('department')->after('module_code')->comment('global, department, self');
            $table->boolean('view_cross_department')->default(false)->after('scope')->comment('Cross-Department View');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->table('role_permissions', function (Blueprint $table) {
            $table->dropColumn(['scope', 'view_cross_department']);
        });
    }
};
