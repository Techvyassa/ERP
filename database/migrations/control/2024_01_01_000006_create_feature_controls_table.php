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
        Schema::connection('control')->create('feature_controls', function (Blueprint $table) {
            $table->id('control_id');
            $table->unsignedBigInteger('org_id');
            
            // Feature Identification
            $table->string('feature_key', 100);
            $table->enum('feature_type', ['BOOLEAN', 'NUMERIC', 'TEXT', 'JSON']);
            
            // Override Value
            $table->text('feature_value');
            
            // Effective Period
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            
            // Audit
            $table->unsignedInteger('granted_by')->nullable();
            $table->text('notes')->nullable();
            
            // Timestamps
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            
            // Foreign Keys
            $table->foreign('org_id')->references('org_id')->on('organizations')->onDelete('cascade');
            
            // Unique Constraint
            $table->unique(['org_id', 'feature_key'], 'unique_org_feature');
            
            // Indexes
            $table->index('feature_key', 'idx_feature_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('control')->dropIfExists('feature_controls');
    }
};
