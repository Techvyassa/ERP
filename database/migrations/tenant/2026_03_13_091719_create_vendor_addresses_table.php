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
        Schema::connection('tenant')->create('vendor_addresses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_id');
            $table->enum('address_type', ['BILLING', 'SHIPPING', 'FACTORY', 'REGISTERED'])->default('BILLING')->comment('Address classification');
            $table->string('address_line1', 255)->comment('Street address');
            $table->string('address_line2', 255)->nullable()->comment('Apartment, suite, etc.');
            $table->string('city', 100)->comment('City / Town');
            $table->string('state', 100)->comment('State / Province');
            $table->string('postal_code', 20)->comment('ZIP / Postal code');
            $table->string('country', 100)->default('India')->comment('Country name');
            $table->string('phone', 20)->nullable()->comment('Contact phone for this address');
            $table->string('email', 150)->nullable()->comment('Contact email for this address');
            $table->boolean('is_primary')->default(false)->comment('Primary address flag');
            $table->boolean('is_active')->default(true);
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->nullable();
            
            // Foreign keys
            $table->foreign('vendor_id')->references('id')->on('vendor_master')->onDelete('cascade');
            
            // Indexes
            $table->index('vendor_id');
            $table->index('address_type');
            $table->index('is_primary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('vendor_addresses');
    }
};
