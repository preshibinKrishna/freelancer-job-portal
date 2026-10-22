<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('freelancer_profiles', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('user_profile_id')->unique()->constrained('user_profiles')->cascadeOnDelete();
            $table->decimal('hourly_rate', 14, 2)->nullable();
            $table->decimal('project_rate', 14, 2)->nullable();
            $table->char('currency', 3)->nullable();
            $table->unsignedTinyInteger('weekly_capacity')->nullable();
            $table->boolean('is_listed')->default(false)->index();
            $table->string('verification_status', 24)->default('unverified')->index();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->index(['is_listed', 'verification_status']);
        });

        Schema::create('project_categories', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->string('name', 100)->unique();
            $table->string('slug', 120)->unique();
            $table->string('description', 240)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('freelancer_profile_categories', function (Blueprint $table): void {
            $table->foreignId('freelancer_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_category_id')->constrained()->cascadeOnDelete();
            $table->primary(['freelancer_profile_id', 'project_category_id']);
        });

        Schema::create('freelancer_projects', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('freelancer_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_category_id')->constrained()->restrictOnDelete();
            $table->string('title', 180);
            $table->text('summary');
            $table->string('role', 120)->nullable();
            $table->string('project_url', 255)->nullable();
            $table->date('completed_at')->nullable();
            $table->foreignId('requested_company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('verification_status', 24)->default('unverified')->index();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
            $table->index(['freelancer_profile_id', 'project_category_id']);
            $table->index(['requested_company_id', 'verification_status']);
        });

        Schema::create('freelancer_reviews', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('freelancer_project_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->string('title', 160)->nullable();
            $table->text('review');
            $table->string('moderation_status', 24)->default('pending')->index();
            $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'moderation_status']);
            $table->index(['reviewer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('freelancer_reviews');
        Schema::dropIfExists('freelancer_projects');
        Schema::dropIfExists('freelancer_profile_categories');
        Schema::dropIfExists('project_categories');
        Schema::dropIfExists('freelancer_profiles');
    }
};
