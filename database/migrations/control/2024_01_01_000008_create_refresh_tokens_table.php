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
        Schema::connection('control')->create('refresh_tokens', function (Blueprint $table) {
            $table->id('token_id');
            $table->unsignedBigInteger('org_id');
            $table->unsignedBigInteger('user_id');
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('last_used_at')->nullable();
            $table->boolean('is_revoked')->default(false);
            $table->string('user_agent', 500)->nullable();
            $table->string('ip_address', 45)->nullable();
            
            // Indexes
            $table->index(['org_id', 'user_id']);
            $table->index('token');
            $table->index('expires_at');
            $table->index('is_revoked');
            
            // Foreign key
            $table->foreign('org_id')
                ->references('org_id')
                ->on('organizations')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('control')->dropIfExists('refresh_tokens');
    }
};
