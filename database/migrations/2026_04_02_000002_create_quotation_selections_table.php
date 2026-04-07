<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->create('quotation_selections', function (Blueprint $table) {
            $table->id();
            $table->string('pr_number', 50)->index();
            $table->unsignedBigInteger('vendor_id');
            $table->unsignedBigInteger('quotation_id');
            $table->decimal('selected_price', 15, 2);
            $table->date('selected_delivery_date')->nullable();
            $table->text('selection_reason')->nullable();
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED'])->default('PENDING');
            $table->unsignedBigInteger('selected_by')->nullable();
            $table->timestamp('selected_at')->nullable();
            $table->timestamps();

            $table->foreign('vendor_id')->references('id')->on('vendor_master')->onDelete('cascade');
            $table->foreign('quotation_id')->references('id')->on('vendor_quotations')->onDelete('cascade');
            $table->foreign('selected_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('quotation_selections');
    }
};
