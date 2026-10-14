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
        Schema::create('integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('app_id')->constrained('apps')->cascadeOnDelete();
            $table->enum('type', ['firebase', 'whatsapp', 'otp', 'google']);
            $table->string('status')->default('not_configured');
            $table->longText('credentials')->nullable(); // Encrypted payload
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'app_id', 'type']);
        });

        Schema::create('builds', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('app_id')->constrained('apps')->cascadeOnDelete();
            $table->unsignedInteger('config_version')->default(1);
            $table->enum('status', ['queued', 'preparing', 'building', 'signing', 'ready', 'failed'])->default('queued');
            $table->string('logs_path')->nullable();
            $table->string('artifact_path')->nullable();
            $table->unsignedInteger('version_code')->default(1);
            $table->string('version_name')->default('1.0.0');
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'app_id']);
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('keystores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('app_id')->unique()->constrained('apps')->cascadeOnDelete();
            $table->string('path'); // Encrypted storage path
            $table->string('alias');
            $table->longText('passwords'); // Encrypted JSON
            $table->string('sha1')->nullable();
            $table->string('sha256')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'app_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('keystores');
        Schema::dropIfExists('builds');
        Schema::dropIfExists('integrations');
    }
};
