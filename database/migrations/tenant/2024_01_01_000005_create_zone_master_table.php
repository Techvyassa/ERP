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
        Schema::create('zone_master', function (Blueprint $table) {
            $table->id('zone_id');
            $table->string('zone_code', 20)->unique()->comment('e.g. NORTH, SOUTH, EAST, WEST');
            $table->string('zone_name', 100)->comment('Zone display name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampTz('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();
            
            // Indexes
            $table->index('zone_code');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zone_master');
    }
};
