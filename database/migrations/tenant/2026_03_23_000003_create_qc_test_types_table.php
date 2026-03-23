<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create qc_test_types master table and link it to qc_parameters_master.
     *
     * QC Test Type examples: Visual, Chemical, Physical, Microbiological, Dimensional
     */
    public function up(): void
    {
        Schema::connection('tenant')->create('qc_test_types', function (Blueprint $table) {
            $table->id();

            $table->string('type_code', 50)->unique()->comment('API-friendly code e.g. VISUAL, CHEMICAL');
            $table->string('type_name', 100)->comment('Display name e.g. Visual Inspection');
            $table->text('description')->nullable()->comment('Optional description of the test type');
            $table->boolean('is_active')->default(true);

            $table->unsignedBigInteger('created_by')->nullable()->comment('FK → users');
            $table->timestampsTz();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->index('is_active');
        });

        // Add test_type_id to qc_parameters_master
        Schema::connection('tenant')->table('qc_parameters_master', function (Blueprint $table) {
            $table->unsignedBigInteger('test_type_id')->nullable()->after('parameter_category')
                ->comment('FK → qc_test_types');
            $table->foreign('test_type_id')->references('id')->on('qc_test_types')->onDelete('set null');
            $table->index('test_type_id');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->table('qc_parameters_master', function (Blueprint $table) {
            $table->dropForeign(['test_type_id']);
            $table->dropIndex(['test_type_id']);
            $table->dropColumn('test_type_id');
        });

        Schema::connection('tenant')->dropIfExists('qc_test_types');
    }
};
