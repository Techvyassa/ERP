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
        Schema::create('role_master', function (Blueprint $table) {
            $table->id('role_id');
            $table->string('role_code', 30)->unique()->comment('e.g. ADMIN, BUYER, QC_INSP');
            $table->string('role_name', 100)->comment('Human-readable label');
            $table->text('description')->nullable()->comment('Role description');
            $table->boolean('is_active')->default(true)->comment('Soft delete flag');
            $table->timestampTz('created_at')->useCurrent();
            
            // Indexes
            $table->index('role_code');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_master');
    }
};
