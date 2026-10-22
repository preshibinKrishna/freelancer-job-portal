<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skills', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->string('name', 80);
            $table->string('slug', 100)->unique();
            $table->timestamps();
            $table->unique('name');
        });

        Schema::create('job_listings', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('title', 180);
            $table->string('slug', 220)->unique();
            $table->longText('description');
            $table->string('employment_type', 24);
            $table->string('workplace_type', 24);
            $table->unsignedTinyInteger('experience_min')->nullable();
            $table->unsignedTinyInteger('experience_max')->nullable();
            $table->decimal('salary_min', 14, 2)->nullable();
            $table->decimal('salary_max', 14, 2)->nullable();
            $table->char('currency', 3)->nullable();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('it_park_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->json('responsibilities')->nullable();
            $table->json('requirements')->nullable();
            $table->json('benefits')->nullable();
            $table->date('application_deadline')->nullable();
            $table->string('visibility', 24)->default('public');
            $table->string('status', 24)->default('draft')->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'status', 'created_at']);
            $table->index(['status', 'visibility', 'published_at']);
            $table->index(['location_id', 'employment_type', 'workplace_type']);
        });

        Schema::create('job_skill', function (Blueprint $table): void {
            $table->foreignId('job_id')->constrained('job_listings')->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->primary(['job_id', 'skill_id']);
            $table->index(['skill_id', 'job_id']);
        });

        Schema::create('saved_jobs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('job_id')->constrained('job_listings')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['job_id', 'user_id']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('job_applications', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('job_id')->constrained('job_listings')->restrictOnDelete();
            $table->foreignId('candidate_id')->constrained('users')->restrictOnDelete();
            $table->text('cover_letter')->nullable();
            $table->string('status', 24)->default('applied')->index();
            $table->timestamp('submitted_at');
            $table->timestamp('withdrawn_at')->nullable();
            $table->timestamps();
            $table->unique(['job_id', 'candidate_id']);
            $table->index(['job_id', 'status', 'submitted_at']);
            $table->index(['candidate_id', 'status', 'submitted_at']);
        });

        Schema::create('job_application_status_changes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('job_application_id')->constrained('job_applications')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('from_status', 24)->nullable();
            $table->string('to_status', 24);
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['job_application_id', 'created_at']);
        });

        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['notifiable_type', 'notifiable_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('job_application_status_changes');
        Schema::dropIfExists('job_applications');
        Schema::dropIfExists('saved_jobs');
        Schema::dropIfExists('job_skill');
        Schema::dropIfExists('job_listings');
        Schema::dropIfExists('skills');
    }
};
