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
        Schema::create('vendor_quotations', function (Blueprint $table) {
            $table->id();
            $table->string('pr_number', 50)->index();
            $table->unsignedBigInteger('vendor_id');
            $table->string('item_name');
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->date('delivery_date')->nullable();
            $table->string('file_path')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('quotation_selections', function (Blueprint $table) {
            $table->id();
            $table->string('pr_number', 50)->index();
            $table->unsignedBigInteger('vendor_id');
            $table->unsignedBigInteger('quotation_id');
            $table->decimal('selected_price', 10, 2);
            $table->date('selected_delivery_date')->nullable();
            $table->text('selection_reason')->nullable();
            $table->enum('status', ['selected', 'rejected'])->default('selected');
            $table->unsignedBigInteger('selected_by')->nullable();
            $table->timestamp('selected_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotation_selections');
        Schema::dropIfExists('vendor_quotations');
    }
};
