<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('engagement_projects', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('title', 180);
            $table->text('description');
            $table->decimal('budget_min', 14, 2)->nullable();
            $table->decimal('budget_max', 14, 2)->nullable();
            $table->char('currency', 3);
            $table->string('pricing_type', 16);
            $table->unsignedSmallInteger('estimated_duration_days')->nullable();
            $table->unsignedTinyInteger('experience_min')->nullable();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->boolean('is_remote')->default(false);
            $table->timestamp('application_deadline')->nullable();
            $table->string('status', 24)->default('draft')->index();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'status', 'created_at']);
            $table->index(['status', 'application_deadline']);
        });

        Schema::create('engagement_project_skills', function (Blueprint $table): void {
            $table->foreignId('engagement_project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->primary(['engagement_project_id', 'skill_id']);
            $table->index(['skill_id', 'engagement_project_id']);
        });

        Schema::create('engagement_project_attachments', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('engagement_project_id')->constrained()->cascadeOnDelete();
            $table->string('disk', 32)->default('local');
            $table->string('path', 500);
            $table->string('original_filename', 255);
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('size_bytes');
            $table->timestamps();
            $table->index(['engagement_project_id', 'created_at']);
        });

        Schema::create('saved_engagement_projects', function (Blueprint $table): void {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('engagement_project_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['user_id', 'engagement_project_id']);
        });

        Schema::create('project_proposals', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('engagement_project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('freelancer_id')->constrained('users')->cascadeOnDelete();
            $table->text('cover_letter');
            $table->decimal('proposed_price', 14, 2);
            $table->char('currency', 3);
            $table->unsignedSmallInteger('proposed_duration_days');
            $table->string('status', 24)->default('submitted')->index();
            $table->timestamp('submitted_at');
            $table->timestamp('withdrawn_at')->nullable();
            $table->timestamps();
            $table->unique(['engagement_project_id', 'freelancer_id']);
            $table->index(['engagement_project_id', 'status', 'submitted_at']);
            $table->index(['freelancer_id', 'status', 'created_at']);
        });

        Schema::create('proposal_milestones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_proposal_id')->constrained()->cascadeOnDelete();
            $table->string('title', 180);
            $table->text('description')->nullable();
            $table->decimal('amount', 14, 2)->nullable();
            $table->unsignedSmallInteger('duration_days')->nullable();
            $table->unsignedSmallInteger('position');
            $table->timestamps();
            $table->index(['project_proposal_id', 'position']);
        });

        Schema::create('proposal_portfolio_items', function (Blueprint $table): void {
            $table->foreignId('project_proposal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('freelancer_project_id')->constrained()->cascadeOnDelete();
            $table->primary(['project_proposal_id', 'freelancer_project_id']);
        });

        Schema::create('engagement_project_milestones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('engagement_project_id')->constrained()->cascadeOnDelete();
            $table->string('title', 180);
            $table->text('description')->nullable();
            $table->decimal('amount', 14, 2)->nullable();
            $table->unsignedSmallInteger('duration_days')->nullable();
            $table->unsignedSmallInteger('position');
            $table->string('status', 20)->default('pending');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['engagement_project_id', 'position']);
        });

        Schema::create('project_messages', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('project_proposal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_id')->constrained('users')->restrictOnDelete();
            $table->text('body');
            $table->timestamps();
            $table->index(['project_proposal_id', 'created_at']);
        });

        Schema::create('engagement_project_status_changes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('engagement_project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('from_status', 24);
            $table->string('to_status', 24);
            $table->timestamp('created_at');
            $table->index(['engagement_project_id', 'created_at']);
        });

        Schema::create('project_proposal_status_changes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_proposal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('from_status', 24);
            $table->string('to_status', 24);
            $table->timestamp('created_at');
            $table->index(['project_proposal_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_proposal_status_changes');
        Schema::dropIfExists('engagement_project_status_changes');
        Schema::dropIfExists('project_messages');
        Schema::dropIfExists('engagement_project_milestones');
        Schema::dropIfExists('proposal_portfolio_items');
        Schema::dropIfExists('proposal_milestones');
        Schema::dropIfExists('project_proposals');
        Schema::dropIfExists('saved_engagement_projects');
        Schema::dropIfExists('engagement_project_attachments');
        Schema::dropIfExists('engagement_project_skills');
        Schema::dropIfExists('engagement_projects');
    }
};
