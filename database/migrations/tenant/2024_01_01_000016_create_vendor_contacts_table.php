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
        Schema::connection('tenant')->create('vendor_contacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_id');
            $table->string('contact_name', 100)->comment('Full name of contact person');
            $table->string('contact_type', 20)->default('SALES')->comment('SALES / FINANCE / LOGISTICS / GM');
            $table->string('phone', 20)->nullable()->comment('Mobile / landline');
            $table->string('email', 150)->nullable()->comment('Email for RFQ/PO dispatch');
            $table->boolean('is_primary')->default(false)->comment('Primary contact flag');
            $table->boolean('is_active')->default(true);
            
            // Foreign keys
            $table->foreign('vendor_id')->references('id')->on('vendor_master')->onDelete('cascade');
            
            // Indexes
            $table->index('vendor_id');
            $table->index('contact_type');
            $table->index('is_primary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('vendor_contacts');
    }
};
