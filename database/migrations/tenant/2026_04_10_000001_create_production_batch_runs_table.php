<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection('tenant')->create('production_batch_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('production_order_id');
            $table->integer('run_number');
            $table->decimal('run_qty', 12, 3); // Free-form: user can set any numeric value
            $table->date('planned_date')->nullable();
            $table->enum('status', ['PENDING', 'MIR_RAISED', 'IN_PROGRESS', 'COMPLETED'])->default('PENDING');
            $table->timestamps();

            $table->foreign('production_order_id')->references('id')->on('production_orders')->onDelete('cascade');
            $table->unique(['production_order_id', 'run_number']);
        });

        Schema::connection('tenant')->create('batch_run_materials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('batch_run_id');
            $table->unsignedBigInteger('material_id');
            $table->decimal('required_qty', 12, 4); // base_qty × (1 + scrap%) × run_qty
            $table->decimal('issued_qty', 12, 4)->nullable();
            $table->decimal('actual_consumed_qty', 12, 4)->nullable();
            $table->timestamps();

            $table->foreign('batch_run_id')->references('id')->on('production_batch_runs')->onDelete('cascade');
            $table->foreign('material_id')->references('id')->on('material_master');
        });

        Schema::connection('tenant')->create('fg_receipts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('batch_run_id');
            $table->unsignedBigInteger('product_id');
            $table->decimal('planned_qty', 12, 3); // = run_qty
            $table->decimal('received_qty', 12, 3)->nullable();
            $table->decimal('rejected_qty', 12, 3)->default(0);
            $table->timestamps();

            $table->foreign('batch_run_id')->references('id')->on('production_batch_runs')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('product_master');
            $table->unique('batch_run_id');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('fg_receipts');
        Schema::connection('tenant')->dropIfExists('batch_run_materials');
        Schema::connection('tenant')->dropIfExists('production_batch_runs');
    }
};
