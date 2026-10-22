<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('freelancer_profiles', function (Blueprint $table): void {
            $table->index(['is_listed', 'created_at'], 'freelancer_profiles_listed_created_at_index');
        });

        Schema::table('engagement_projects', function (Blueprint $table): void {
            $table->index(['status', 'published_at'], 'engagement_projects_status_published_at_index');
        });

        Schema::table('recruitment_interviews', function (Blueprint $table): void {
            $table->index(['status', 'starts_at'], 'recruitment_interviews_status_starts_at_index');
        });

        Schema::table('conversation_participants', function (Blueprint $table): void {
            $table->index(['user_id', 'archived_at', 'conversation_id', 'last_read_at'], 'conversation_participants_user_inbox_index');
            $table->dropIndex(['user_id', 'archived_at', 'last_read_at']);
        });
    }

    public function down(): void
    {
        Schema::table('conversation_participants', function (Blueprint $table): void {
            $table->index(['user_id', 'archived_at', 'last_read_at']);
            $table->dropIndex('conversation_participants_user_inbox_index');
        });

        Schema::table('recruitment_interviews', function (Blueprint $table): void {
            $table->dropIndex('recruitment_interviews_status_starts_at_index');
        });

        Schema::table('engagement_projects', function (Blueprint $table): void {
            $table->dropIndex('engagement_projects_status_published_at_index');
        });

        Schema::table('freelancer_profiles', function (Blueprint $table): void {
            $table->dropIndex('freelancer_profiles_listed_created_at_index');
        });
    }
};
