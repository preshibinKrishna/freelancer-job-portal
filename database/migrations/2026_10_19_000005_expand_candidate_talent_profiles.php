<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table): void {
            $table->string('profile_slug', 220)->nullable()->unique();
            $table->string('visibility', 20)->default('private')->index();
            $table->string('availability', 32)->nullable();
            $table->unsignedSmallInteger('experience_years')->nullable();
            $table->unsignedSmallInteger('notice_period_days')->nullable();
            $table->decimal('expected_salary', 14, 2)->nullable();
            $table->char('expected_salary_currency', 3)->nullable();
            $table->json('preferred_work_types')->nullable();
            $table->string('linkedin_url', 255)->nullable();
            $table->string('github_url', 255)->nullable();
            $table->string('photo_disk', 32)->nullable();
            $table->string('photo_path', 500)->nullable();
            $table->string('photo_mime_type', 80)->nullable();
        });

        Schema::create('talent_profile_skills', function (Blueprint $table): void {
            $table->foreignId('user_profile_id')->constrained('user_profiles')->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->primary(['user_profile_id', 'skill_id']);
            $table->index(['skill_id', 'user_profile_id']);
        });

        Schema::create('talent_profile_preferred_locations', function (Blueprint $table): void {
            $table->foreignId('user_profile_id')->constrained('user_profiles')->cascadeOnDelete();
            $table->foreignId('location_id')->constrained()->restrictOnDelete();
            $table->primary(['user_profile_id', 'location_id']);
        });

        $this->createOwnedProfileTable('profile_experiences', function (Blueprint $table): void {
            $table->string('title', 160);
            $table->string('company', 160);
            $table->string('location', 160)->nullable();
            $table->text('description')->nullable();
            $table->date('started_at')->nullable();
            $table->date('ended_at')->nullable();
            $table->boolean('is_current')->default(false);
        });

        $this->createOwnedProfileTable('profile_educations', function (Blueprint $table): void {
            $table->string('institution', 180);
            $table->string('degree', 160)->nullable();
            $table->string('field_of_study', 160)->nullable();
            $table->date('started_at')->nullable();
            $table->date('ended_at')->nullable();
        });

        $this->createOwnedProfileTable('profile_certifications', function (Blueprint $table): void {
            $table->string('name', 180);
            $table->string('issuer', 180)->nullable();
            $table->string('credential_url', 255)->nullable();
            $table->date('issued_at')->nullable();
            $table->date('expires_at')->nullable();
        });

        $this->createOwnedProfileTable('profile_languages', function (Blueprint $table): void {
            $table->string('language', 80);
            $table->string('proficiency', 32)->nullable();
        });

        $this->createOwnedProfileTable('profile_portfolio_links', function (Blueprint $table): void {
            $table->string('title', 160);
            $table->string('url', 255);
            $table->text('description')->nullable();
        });

        Schema::create('profile_resumes', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('user_profile_id')->constrained('user_profiles')->cascadeOnDelete();
            $table->string('disk', 32)->default('local');
            $table->string('path', 500);
            $table->string('original_filename', 255);
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('size_bytes');
            $table->string('scan_status', 24)->default('pending')->index();
            $table->timestamp('uploaded_at');
            $table->timestamps();
            $table->index(['user_profile_id', 'uploaded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_resumes');
        Schema::dropIfExists('profile_portfolio_links');
        Schema::dropIfExists('profile_languages');
        Schema::dropIfExists('profile_certifications');
        Schema::dropIfExists('profile_educations');
        Schema::dropIfExists('profile_experiences');
        Schema::dropIfExists('talent_profile_preferred_locations');
        Schema::dropIfExists('talent_profile_skills');
        Schema::table('user_profiles', function (Blueprint $table): void {
            $table->dropUnique(['profile_slug']);
            $table->dropIndex(['visibility']);
            $table->dropColumn(['profile_slug', 'visibility', 'availability', 'experience_years', 'notice_period_days', 'expected_salary', 'expected_salary_currency', 'preferred_work_types', 'linkedin_url', 'github_url', 'photo_disk', 'photo_path', 'photo_mime_type']);
        });
    }

    /** @param callable(Blueprint): void $columns */
    private function createOwnedProfileTable(string $name, callable $columns): void
    {
        Schema::create($name, function (Blueprint $table) use ($columns): void {
            $table->id();
            $table->foreignId('user_profile_id')->constrained('user_profiles')->cascadeOnDelete();
            $columns($table);
            $table->timestamps();
            $table->index(['user_profile_id', 'id']);
        });
    }
};
