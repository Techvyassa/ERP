<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Table: inspection_results
     * Table: usage_decisions
     * Module: Inward > Quality Control
     * Depends on: inspection_lots, users
     */
    public function up(): void
    {
        // --- Test Parameter Results ---
        Schema::connection('tenant')->create('inspection_results', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('lot_id')->comment('FK → inspection_lots');
            $table->string('parameter_name', 100)->comment('e.g. Purity %, Hardness, Moisture Content');
            $table->string('standard_min', 50)->nullable()->comment('Lower acceptable limit');
            $table->string('standard_max', 50)->nullable()->comment('Upper acceptable limit');
            $table->string('standard_value', 100)->nullable()->comment('Target / exact standard where applicable');
            $table->string('observed_value', 100)->nullable()->comment('Actual measured value from lab');
            $table->string('unit_of_measurement', 20)->nullable()->comment('e.g. %, mm, kN/m²');
            $table->boolean('is_pass')->nullable()->comment('TRUE = within spec, FALSE = out of spec, NULL = not yet tested');
            $table->text('remarks')->nullable()->comment('Inspector notes for this parameter');
            $table->timestampsTz();

            $table->foreign('lot_id')->references('id')->on('inspection_lots')->onDelete('cascade');

            $table->index('lot_id');
            $table->index('is_pass');
        });

        // --- Usage Decision (QC Manager Final Call) ---
        Schema::connection('tenant')->create('usage_decisions', function (Blueprint $table) {
            $table->id();

            // --- Parent ---
            $table->unsignedBigInteger('lot_id')->unique()->comment('FK → inspection_lots (one decision per lot)');

            // --- Decision ---
            $table->enum('decision', [
                'ACCEPTED',             // Full quantity approved → stock released to UNRESTRICTED
                'REJECTED',             // Full quantity blocked → RTV initiated
                'CONDITIONALLY_ACCEPTED', // Usable but non-conforming; requires override approval
                'REWORK_REQUIRED',      // Material to be reworked before use
            ])->comment('Final QC Usage Decision');

            // --- Quantities ---
            $table->decimal('accepted_qty', 14, 3)->default(0)
                ->comment('Quantity approved for inventory use');
            $table->decimal('rejected_qty', 14, 3)->default(0)
                ->comment('Quantity to be returned to vendor');

            // --- Override (Conditional Acceptance) ---
            $table->unsignedBigInteger('override_approved_by')->nullable()
                ->comment('FK → users (Production/Technical Head who approved conditional use)');
            $table->text('override_reason')->nullable()->comment('Justification for conditional acceptance');

            // --- Certificate of Analysis ---
            $table->string('coa_file_path', 500)->nullable()
                ->comment('Path to vendor or lab Certificate of Analysis PDF');

            // --- Audit ---
            $table->text('remarks')->nullable()->comment('QC Manager final notes');
            $table->unsignedBigInteger('decided_by')->nullable()->comment('FK → users (QC Manager)');
            $table->dateTime('decided_at')->nullable()->comment('Timestamp of usage decision submission');
            $table->timestampsTz();

            $table->foreign('lot_id')->references('id')->on('inspection_lots')->onDelete('cascade');
            $table->foreign('decided_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('override_approved_by')->references('id')->on('users')->onDelete('set null');

            $table->index('lot_id');
            $table->index('decision');
            $table->index('decided_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('usage_decisions');
        Schema::connection('tenant')->dropIfExists('inspection_results');
    }
};
