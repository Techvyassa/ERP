<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->create('maint_procurement_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_no', 20)->unique();
            $table->unsignedBigInteger('part_id')->nullable();
            $table->string('part_code', 30);
            $table->string('part_name', 100)->default('');
            $table->string('unit', 20)->default('Nos');
            $table->integer('qty');
            $table->string('vendor', 150)->nullable();
            $table->date('expected_date')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('Pending'); // Pending, Ordered, Received, Cancelled
            $table->timestamps();

            $table->foreign('part_id')->references('id')->on('maint_spare_parts')->onDelete('set null');
        });

        Schema::connection('tenant')->create('maint_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('part_id')->nullable();
            $table->string('part_code', 30);
            $table->string('part_name', 100)->default('');
            $table->string('type', 20); // Issue, Receive, Adjust+, Adjust-
            $table->integer('qty');
            $table->string('reference', 50)->nullable(); // WO no, PO no, etc.
            $table->text('note')->nullable();
            $table->timestamps();

            $table->foreign('part_id')->references('id')->on('maint_spare_parts')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('maint_stock_movements');
        Schema::connection('tenant')->dropIfExists('maint_procurement_orders');
    }
};
