<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection('tenant')->create('production_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_no', 30)->unique();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('bom_id');
            $table->decimal('target_qty', 12, 3);
            $table->date('planned_date');
            $table->enum('status', ['DRAFT', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED'])->default('DRAFT');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('product_master');
            $table->foreign('bom_id')->references('id')->on('bom_header');
        });

        Schema::connection('tenant')->create('material_issue_requests', function (Blueprint $table) {
            $table->id();
            $table->string('mir_no', 30)->unique();
            $table->unsignedBigInteger('production_order_id');
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED'])->default('PENDING');
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->foreign('production_order_id')->references('id')->on('production_orders')->onDelete('cascade');
        });

        Schema::connection('tenant')->create('mir_line_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('mir_id');
            $table->unsignedBigInteger('material_id');
            $table->decimal('required_qty', 12, 3);
            $table->unsignedBigInteger('uom_id')->nullable();

            $table->foreign('mir_id')->references('id')->on('material_issue_requests')->onDelete('cascade');
            $table->foreign('material_id')->references('id')->on('material_master');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('mir_line_items');
        Schema::connection('tenant')->dropIfExists('material_issue_requests');
        Schema::connection('tenant')->dropIfExists('production_orders');
    }
};
