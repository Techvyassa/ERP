<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->table('fg_confirmation_sessions', function (Blueprint $table) {
            $table->text('rejection_reason_note')->nullable()->after('rejection_reason_code');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->table('fg_confirmation_sessions', function (Blueprint $table) {
            $table->dropColumn('rejection_reason_note');
        });
    }
};
