<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * New Inward Flow:
     * Gate Entry → GRN auto-created → QC → Putaway → Stock
     *
     * Changes:
     * 1. grn_headers: add ge_id FK, make mr_id nullable
     * 2. grn_line_items: make mr_line_id nullable (GRN now created from PO lines directly)
     */
    public function up(): void
    {
        // Update grn_headers
        Schema::connection('tenant')->table('grn_headers', function (Blueprint $table) {
            // Add gate entry FK — the new source for auto-created GRNs
            $table->unsignedBigInteger('ge_id')->nullable()->after('grn_number')
                ->comment('FK → gate_entries (source gate entry for auto-created GRN)');

            // Make mr_id nullable — backward compat, old MR-based GRNs preserved
            $table->unsignedBigInteger('mr_id')->nullable()->change();

            $table->foreign('ge_id')->references('id')->on('gate_entries')->onDelete('restrict');
            $table->index('ge_id');
        });

        // Update grn_line_items — mr_line_id no longer required in new flow
        Schema::connection('tenant')->table('grn_line_items', function (Blueprint $table) {
            $table->unsignedBigInteger('mr_line_id')->nullable()->change();
            // po_line_id now directly tracked for new flow
            $table->unsignedBigInteger('po_line_id')->nullable()->after('mr_line_id')
                ->comment('FK → po_line_items (direct link in new flow)');
            $table->foreign('po_line_id')->references('id')->on('po_line_items')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->table('grn_line_items', function (Blueprint $table) {
            $table->dropForeign(['po_line_id']);
            $table->dropColumn('po_line_id');
            $table->unsignedBigInteger('mr_line_id')->nullable(false)->change();
        });

        Schema::connection('tenant')->table('grn_headers', function (Blueprint $table) {
            $table->dropForeign(['ge_id']);
            $table->dropIndex(['ge_id']);
            $table->dropColumn('ge_id');
            $table->unsignedBigInteger('mr_id')->nullable(false)->change();
        });
    }
};
