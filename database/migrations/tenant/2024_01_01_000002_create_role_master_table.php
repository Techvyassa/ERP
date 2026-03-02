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
        Schema::connection('tenant')->create('role_master', function (Blueprint $table) {
            $table->id('role_id');
            $table->string('role_code', 50)->unique();
            $table->string('role_name', 100);
            $table->text('description')->nullable();
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system_role')->default(false)->comment('System roles cannot be deleted');
            
            // Audit
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('role_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('role_master');
    }
};
