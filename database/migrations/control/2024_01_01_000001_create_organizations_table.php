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
        Schema::connection('control')->create('organizations', function (Blueprint $table) {
            $table->id('org_id');
            $table->string('org_slug', 100)->unique();
            $table->string('org_name', 255);
            $table->string('tenant_db_name', 100)->unique();
            $table->enum('registration_status', ['PENDING', 'ACTIVE', 'SUSPENDED', 'TERMINATED'])
                  ->default('PENDING');
            
            // Contact Information
            $table->string('primary_email', 255)->unique();
            $table->string('primary_phone', 20)->nullable();
            $table->string('address_line1', 255)->nullable();
            $table->string('address_line2', 255)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->char('country_code', 2);
            
            // Localization
            $table->string('timezone', 50)->default('UTC');
            $table->char('currency_code', 3)->default('USD');
            
            // Capacity
            $table->unsignedInteger('max_users')->default(10);
            
            // Timestamps
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->timestamp('terminated_at')->nullable();
            
            // Indexes
            $table->index('org_slug', 'idx_org_slug');
            $table->index('registration_status', 'idx_registration_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('control')->dropIfExists('organizations');
    }
};
