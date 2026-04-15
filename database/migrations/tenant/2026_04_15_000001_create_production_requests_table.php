<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_no', 50)->unique();
            $table->foreignId('product_id')->constrained('product_master')->onDelete('restrict');
            $table->foreignId('bom_id')->constrained('bom_header')->onDelete('restrict');
            $table->decimal('target_qty', 15, 3);
            $table->foreignId('uom_id')->constrained('uom_master')->onDelete('restrict');
            $table->date('planned_date');
            $table->enum('status', ['DRAFT', 'PENDING', 'APPROVED', 'REJECTED', 'CONVERTED_TO_MIR', 'CANCELLED'])->default('DRAFT');
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('mir_id')->nullable()->constrained('material_issue_requests')->onDelete('set null');
            $table->foreignId('production_order_id')->nullable()->constrained('production_orders')->onDelete('set null');
            $table->timestamps();
            
            $table->index(['status', 'created_at']);
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_requests');
    }
};