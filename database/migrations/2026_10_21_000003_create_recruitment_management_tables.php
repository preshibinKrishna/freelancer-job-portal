<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment_requests', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('assigned_recruiter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('position', 180);
            $table->unsignedSmallInteger('number_of_openings')->default(1);
            $table->unsignedTinyInteger('experience_min')->nullable();
            $table->unsignedTinyInteger('experience_max')->nullable();
            $table->decimal('salary_min', 14, 2)->nullable();
            $table->decimal('salary_max', 14, 2)->nullable();
            $table->char('currency', 3)->nullable();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('workplace_type', 16);
            $table->unsignedSmallInteger('notice_period_days')->nullable();
            $table->string('urgency', 16)->default('normal');
            $table->text('description');
            $table->string('status', 20)->default('submitted')->index();
            $table->timestamp('submitted_at');
            $table->timestamps();
            $table->index(['assigned_recruiter_id', 'status', 'created_at']);
            $table->index(['company_id', 'status', 'created_at']);
        });

        Schema::create('recruitment_request_skills', function (Blueprint $table): void {
            $table->foreignId('recruitment_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->primary(['recruitment_request_id', 'skill_id']);
            $table->index(['skill_id', 'recruitment_request_id']);
        });

        Schema::create('recruitment_pipeline_entries', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('recruitment_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('candidate_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('added_by')->constrained('users')->restrictOnDelete();
            $table->string('stage', 24)->default('new')->index();
            $table->text('private_notes')->nullable();
            $table->timestamp('added_at');
            $table->timestamp('placed_at')->nullable();
            $table->timestamps();
            $table->unique(['recruitment_request_id', 'candidate_id']);
            $table->index(['recruitment_request_id', 'stage', 'created_at']);
            $table->index(['candidate_id', 'created_at']);
        });

        Schema::create('recruitment_pipeline_stage_changes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pipeline_entry_id')->constrained('recruitment_pipeline_entries')->cascadeOnDelete();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('from_stage', 24)->nullable();
            $table->string('to_stage', 24);
            $table->text('note')->nullable();
            $table->timestamp('created_at');
            $table->index(['pipeline_entry_id', 'created_at']);
        });

        Schema::create('recruitment_interviews', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('pipeline_entry_id')->constrained('recruitment_pipeline_entries')->cascadeOnDelete();
            $table->foreignId('scheduled_by')->constrained('users')->restrictOnDelete();
            $table->string('title', 180);
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('location_or_link', 500)->nullable();
            $table->text('private_notes')->nullable();
            $table->string('status', 16)->default('scheduled')->index();
            $table->timestamps();
            $table->index(['starts_at', 'status']);
            $table->index(['pipeline_entry_id', 'starts_at']);
        });

        Schema::create('recruitment_activities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('recruitment_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pipeline_entry_id')->nullable()->constrained('recruitment_pipeline_entries')->nullOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('activity_type', 48);
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->index();
            $table->index(['recruitment_request_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_activities');
        Schema::dropIfExists('recruitment_interviews');
        Schema::dropIfExists('recruitment_pipeline_stage_changes');
        Schema::dropIfExists('recruitment_pipeline_entries');
        Schema::dropIfExists('recruitment_request_skills');
        Schema::dropIfExists('recruitment_requests');
    }
};
