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
        Schema::table('push_devices', function (Blueprint $table) {
            $table->unique(['app_id', 'fcm_token']);
        });

        Schema::table('notification_campaigns', function (Blueprint $table) {
            $table->index(['status', 'scheduled_at']);
            $table->index(['tenant_id', 'app_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notification_campaigns', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'app_id', 'created_at']);
            $table->dropIndex(['status', 'scheduled_at']);
        });

        Schema::table('push_devices', function (Blueprint $table) {
            $table->dropUnique(['app_id', 'fcm_token']);
        });
    }
};
