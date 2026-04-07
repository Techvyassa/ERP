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
        Schema::connection('tenant')->create('maint_assets', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 150);
            $table->string('category', 60);
            $table->string('location', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->date('installed_on')->nullable();
            $table->date('last_maintained')->nullable();
            $table->enum('status', ['Active', 'Inactive', 'Under Maintenance'])->default('Active');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::connection('tenant')->create('maint_spare_parts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 150);
            $table->string('compatible_asset', 150)->nullable();
            $table->integer('stock')->default(0);
            $table->integer('reorder_level')->nullable();
            $table->string('unit', 30)->default('Nos');
            $table->timestamps();
        });

        Schema::connection('tenant')->create('maint_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_no', 20)->unique();
            $table->unsignedBigInteger('asset_id')->nullable();
            $table->string('asset_name', 150);
            $table->enum('priority', ['High', 'Medium', 'Low'])->default('Medium');
            $table->text('issue');
            $table->enum('status', ['Pending Approval', 'Approved', 'Rejected', 'Assigned', 'Closed'])->default('Pending Approval');
            $table->string('raised_by', 100)->nullable();
            $table->unsignedBigInteger('raised_by_id')->nullable();
            $table->date('approved_on')->nullable();
            $table->date('rejected_on')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index('asset_id');
            $table->foreign('asset_id')->references('id')->on('maint_assets')->onDelete('set null');
        });

        Schema::connection('tenant')->create('maint_work_orders', function (Blueprint $table) {
            $table->id();
            $table->string('wo_no', 20)->unique();
            $table->unsignedBigInteger('request_id')->nullable();
            $table->unsignedBigInteger('asset_id')->nullable();
            $table->string('asset_name', 150);
            $table->string('technician', 100);
            $table->string('team', 60)->nullable();
            $table->date('due_date')->nullable();
            $table->enum('priority', ['High', 'Medium', 'Low'])->default('Medium');
            $table->text('notes')->nullable();
            $table->text('engineer_notes')->nullable();
            $table->enum('status', ['Assigned', 'In Progress', 'Completed', 'Closed'])->default('Assigned');
            $table->date('assigned_on')->nullable();
            $table->date('closed_on')->nullable();
            $table->string('verified_by', 100)->nullable();
            $table->text('closure_notes')->nullable();
            $table->timestamps();

            $table->index('request_id');
            $table->index('asset_id');
            $table->foreign('asset_id')->references('id')->on('maint_assets')->onDelete('set null');
            $table->foreign('request_id')->references('id')->on('maint_requests')->onDelete('set null');
        });

        Schema::connection('tenant')->create('maint_pm_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('pm_no', 20)->unique();
            $table->unsignedBigInteger('asset_id')->nullable();
            $table->string('asset_name', 150);
            $table->string('task', 200);
            $table->enum('frequency', ['Daily', 'Weekly', 'Monthly', 'Quarterly', 'Half-Yearly', 'Yearly']);
            $table->string('assigned_to', 100)->nullable();
            $table->date('next_due');
            $table->string('duration', 50)->nullable();
            $table->enum('status', ['Scheduled', 'Overdue', 'Done'])->default('Scheduled');
            $table->date('last_done')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('asset_id');
            $table->foreign('asset_id')->references('id')->on('maint_assets')->onDelete('set null');
        });

        Schema::connection('tenant')->create('maint_pm_materials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pm_id');
            $table->string('part_name', 150);
            $table->integer('qty')->default(1);
            $table->string('unit', 30)->default('Nos');

            $table->index('pm_id');
            $table->foreign('pm_id')->references('id')->on('maint_pm_schedules')->onDelete('cascade');
        });

        Schema::connection('tenant')->create('maint_material_requests', function (Blueprint $table) {
            $table->id();
            $table->string('mmr_no', 20)->unique();
            $table->unsignedBigInteger('wo_id')->nullable();
            $table->string('wo_no', 20)->nullable();
            $table->unsignedBigInteger('part_id')->nullable();
            $table->string('part_code', 30);
            $table->string('part_name', 150);
            $table->integer('qty')->default(1);
            $table->string('unit', 30)->default('Nos');
            $table->boolean('in_stock')->default(false);
            $table->enum('status', ['Pending Issue', 'Procurement Required', 'Issued'])->default('Pending Issue');
            $table->date('raised_on')->nullable();
            $table->date('issued_on')->nullable();
            $table->timestamps();

            $table->index('wo_id');
            $table->index('part_id');
            $table->foreign('part_id')->references('id')->on('maint_spare_parts')->onDelete('set null');
            $table->foreign('wo_id')->references('id')->on('maint_work_orders')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('maint_material_requests');
        Schema::connection('tenant')->dropIfExists('maint_pm_materials');
        Schema::connection('tenant')->dropIfExists('maint_pm_schedules');
        Schema::connection('tenant')->dropIfExists('maint_work_orders');
        Schema::connection('tenant')->dropIfExists('maint_requests');
        Schema::connection('tenant')->dropIfExists('maint_spare_parts');
        Schema::connection('tenant')->dropIfExists('maint_assets');
    }
};
