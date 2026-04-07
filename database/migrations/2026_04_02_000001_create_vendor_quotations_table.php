<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->create('vendor_quotations', function (Blueprint $table) {
            $table->id();
            $table->string('pr_number', 50)->index();
            $table->unsignedBigInteger('vendor_id');
            $table->string('item_name', 200);
            $table->decimal('quantity', 15, 3);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('total_price', 15, 2);
            $table->date('delivery_date')->nullable();
            $table->string('file_path', 500)->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->foreign('vendor_id')->references('id')->on('vendor_master')->onDelete('cascade');
            $table->index(['pr_number', 'vendor_id']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('vendor_quotations');
    }
};
