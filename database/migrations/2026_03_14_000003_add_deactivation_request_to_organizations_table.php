<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->text('deactivation_reason')->nullable()->after('status');
            $table->timestamp('deactivation_requested_at')->nullable()->after('deactivation_reason');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['deactivation_reason', 'deactivation_requested_at']);
        });
    }
};
