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
        Schema::table('notification_campaigns', function (Blueprint $table) {
            $table->string('deep_link')->nullable()->after('body');
            $table->unsignedInteger('sent_count')->default(0)->after('status');
            $table->unsignedInteger('failed_count')->default(0)->after('sent_count');
        });

        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('app_id')->nullable()->constrained('apps')->nullOnDelete();
            $table->string('phone');
            $table->string('template_name');
            $table->enum('status', ['queued', 'sent', 'delivered', 'read', 'failed'])->default('queued');
            $table->decimal('cost', 10, 4)->default(0.8000);
            $table->string('whatsapp_message_id')->nullable()->index();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');

        Schema::table('notification_campaigns', function (Blueprint $table) {
            $table->dropColumn(['deep_link', 'sent_count', 'failed_count']);
        });
    }
};
