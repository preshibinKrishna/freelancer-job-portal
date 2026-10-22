<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            $table->string('name', 160);
            $table->string('slug', 180)->unique();
            $table->string('logo_path')->nullable();
            $table->string('cover_image_path')->nullable();
            $table->text('description')->nullable();
            $table->string('industry', 120)->nullable()->index();
            $table->string('website', 255)->nullable();
            $table->string('company_size', 16)->nullable();
            $table->unsignedSmallInteger('founded_year')->nullable();
            $table->foreignId('headquarters_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('it_park_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->json('social_links')->nullable();
            $table->string('contact_name', 160)->nullable();
            $table->string('contact_email', 254)->nullable();
            $table->string('contact_phone', 40)->nullable();
            $table->json('benefits')->nullable();
            $table->json('technology_stack')->nullable();
            $table->text('company_culture')->nullable();
            $table->string('verification_status', 24)->default('unverified')->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
            $table->index(['owner_id', 'published_at']);
        });

        Schema::create('company_memberships', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 24);
            $table->string('status', 24)->default('active');
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['company_id', 'user_id']);
            $table->index(['user_id', 'status']);
            $table->index(['company_id', 'role', 'status']);
        });

        Schema::create('company_verifications', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('submitted_by')->constrained('users')->restrictOnDelete();
            $table->string('status', 24)->index();
            $table->text('submission_note');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reviewer_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_verifications');
        Schema::dropIfExists('company_memberships');
        Schema::dropIfExists('companies');
    }
};
