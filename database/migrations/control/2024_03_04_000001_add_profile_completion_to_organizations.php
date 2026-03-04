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
        Schema::connection('control')->table('organizations', function (Blueprint $table) {
            $table->json('profile_completion')->nullable()->after('max_users');
            $table->integer('profile_completion_percentage')->default(0)->after('profile_completion');
            $table->timestamp('profile_completed_at')->nullable()->after('profile_completion_percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('control')->table('organizations', function (Blueprint $table) {
            $table->dropColumn(['profile_completion', 'profile_completion_percentage', 'profile_completed_at']);
        });
    }
};
