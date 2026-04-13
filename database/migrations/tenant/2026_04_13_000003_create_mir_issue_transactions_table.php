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
        Schema::connection('tenant')->create('mir_issue_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('mir_line_id');
            $table->decimal('issued_qty', 12, 4);
            $table->unsignedBigInteger('issued_by');
            $table->timestamp('issued_at');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('mir_line_id')->references('id')->on('mir_line_items');
            $table->foreign('issued_by')->references('id')->on('users');

            $table->index('mir_line_id');
            $table->index('issued_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('mir_issue_transactions');
    }
};