<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->table('product_master', function (Blueprint $table) {
            $table->boolean('requires_fg_qc')->default(false)->after('mrp');
        });

        Schema::connection('tenant')->table('inspection_lots', function (Blueprint $table) {
            $table->dropForeign(['grn_id']);
            $table->dropForeign(['grn_line_id']);
            $table->dropForeign(['material_id']);
        });

        DB::connection('tenant')->statement('ALTER TABLE inspection_lots MODIFY grn_id BIGINT UNSIGNED NULL');
        DB::connection('tenant')->statement('ALTER TABLE inspection_lots MODIFY grn_line_id BIGINT UNSIGNED NULL');
        DB::connection('tenant')->statement('ALTER TABLE inspection_lots MODIFY material_id BIGINT UNSIGNED NULL');

        Schema::connection('tenant')->table('inspection_lots', function (Blueprint $table) {
            $table->enum('source_type', ['GRN', 'PRODUCTION'])->default('GRN')->after('lot_number');
            $table->unsignedBigInteger('production_order_id')->nullable()->after('grn_line_id');
            $table->unsignedBigInteger('product_id')->nullable()->after('material_id');
            $table->unsignedBigInteger('warehouse_id')->nullable()->after('product_id');
            $table->unsignedBigInteger('bin_id')->nullable()->after('warehouse_id');
            $table->string('batch_number', 50)->nullable()->after('bin_id');

            $table->foreign('grn_id')->references('id')->on('grn_headers')->onDelete('cascade');
            $table->foreign('grn_line_id')->references('id')->on('grn_line_items')->onDelete('cascade');
            $table->foreign('material_id')->references('id')->on('material_master')->onDelete('restrict');
            $table->foreign('production_order_id')->references('id')->on('production_orders')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('product_master')->onDelete('restrict');
            $table->foreign('warehouse_id')->references('id')->on('warehouse_master')->onDelete('set null');
            $table->foreign('bin_id')->references('id')->on('bin_locations')->onDelete('set null');

            $table->index('source_type');
            $table->index('production_order_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->table('inspection_lots', function (Blueprint $table) {
            $table->dropForeign(['production_order_id']);
            $table->dropForeign(['product_id']);
            $table->dropForeign(['warehouse_id']);
            $table->dropForeign(['bin_id']);

            $table->dropIndex(['source_type']);
            $table->dropIndex(['production_order_id']);
            $table->dropIndex(['product_id']);

            $table->dropColumn([
                'source_type',
                'production_order_id',
                'product_id',
                'warehouse_id',
                'bin_id',
                'batch_number',
            ]);
        });

        Schema::connection('tenant')->table('product_master', function (Blueprint $table) {
            $table->dropColumn('requires_fg_qc');
        });
    }
};
