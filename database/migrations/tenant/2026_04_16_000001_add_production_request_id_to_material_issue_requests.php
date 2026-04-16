<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_issue_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('production_request_id')->nullable()->after('production_order_id');
            $table->foreign('production_request_id')->references('id')->on('production_requests')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('material_issue_requests', function (Blueprint $table) {
            $table->dropForeign(['production_request_id']);
            $table->dropColumn('production_request_id');
        });
    }
};