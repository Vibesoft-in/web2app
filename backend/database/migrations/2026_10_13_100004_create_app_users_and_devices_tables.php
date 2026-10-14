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
        Schema::create('app_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('app_id')->constrained('apps')->cascadeOnDelete();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('store_customer_id')->nullable();
            $table->string('name')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'app_id', 'phone']);
            $table->index(['tenant_id', 'app_id', 'store_customer_id']);
        });

        Schema::create('push_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('app_id')->constrained('apps')->cascadeOnDelete();
            $table->foreignId('app_user_id')->nullable()->constrained('app_users')->nullOnDelete();
            $table->string('fcm_token');
            $table->enum('platform', ['android', 'ios'])->default('android');
            $table->timestamps();

            $table->index(['tenant_id', 'app_id', 'fcm_token']);
        });

        Schema::create('notification_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('app_id')->constrained('apps')->cascadeOnDelete();
            $table->string('title');
            $table->text('body');
            $table->json('audience')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->enum('status', ['draft', 'scheduled', 'sent', 'failed'])->default('draft');
            $table->timestamps();

            $table->index(['tenant_id', 'app_id', 'status']);
        });

        Schema::create('whatsapp_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained('tenants')->cascadeOnDelete();
            $table->decimal('balance', 10, 2)->default(0.00);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_wallets');
        Schema::dropIfExists('notification_campaigns');
        Schema::dropIfExists('push_devices');
        Schema::dropIfExists('app_users');
    }
};
