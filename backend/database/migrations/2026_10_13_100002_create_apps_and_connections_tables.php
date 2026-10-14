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
        Schema::create('themes', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('preview_url')->nullable();
            $table->unsignedInteger('schema_version')->default(1);
            $table->json('defaults')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('apps', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->enum('platform', ['woo', 'shopify'])->default('woo');
            $table->string('package_name')->unique();
            $table->string('app_key')->unique();
            $table->string('setup_step')->default('platform');
            $table->enum('status', ['draft', 'verified', 'configured', 'paid', 'building', 'live'])->default('draft');
            $table->string('firebase_project_id')->nullable();
            $table->string('firebase_app_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
        });

        Schema::create('connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('app_id')->constrained('apps')->cascadeOnDelete();
            $table->enum('platform', ['woo', 'shopify'])->default('woo');
            $table->string('site_url');
            $table->longText('credentials')->nullable(); // Encrypted JSON payload
            $table->enum('status', ['not_started', 'configured', 'verified', 'failed'])->default('not_started');
            $table->timestamp('last_verified_at')->nullable();
            $table->string('last_error_code')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'app_id']);
        });

        Schema::create('app_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('app_id')->constrained('apps')->cascadeOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->enum('state', ['draft', 'published'])->default('draft');
            $table->json('config');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'app_id', 'state']);
            $table->index(['app_id', 'version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_configs');
        Schema::dropIfExists('connections');
        Schema::dropIfExists('apps');
        Schema::dropIfExists('themes');
    }
};
