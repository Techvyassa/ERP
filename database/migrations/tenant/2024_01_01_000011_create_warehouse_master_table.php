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
        Schema::connection('tenant')->create('warehouse_master', function (Blueprint $table) {
            $table->id('warehouse_id');
            $table->string('warehouse_code', 20)->unique()->comment('WH-001, WH-002');
            $table->string('warehouse_name', 100)->comment('Masala RM Store');
            $table->string('warehouse_type', 20)->comment('RM / FG / PKG / REJECTION / WIP');
            $table->text('address')->nullable()->comment('Physical address');
            $table->unsignedBigInteger('incharge_user_id')->nullable();
            $table->boolean('is_active')->default(true);
            
            // Foreign keys
            $table->foreign('incharge_user_id')->references('user_id')->on('users')->onDelete('set null');
            
            // Indexes
            $table->index('warehouse_code');
            $table->index('warehouse_type');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('warehouse_master');
    }
};
