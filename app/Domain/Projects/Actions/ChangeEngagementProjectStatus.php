<?php

declare(strict_types=1);

namespace App\Domain\Projects\Actions;

use App\Enums\EngagementProjectStatus;
use App\Models\AuditLog;
use App\Models\EngagementProject;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class ChangeEngagementProjectStatus
{
    public function execute(EngagementProject $project, User $actor, EngagementProjectStatus $status): EngagementProject
    {
        Gate::forUser($actor)->authorize('manage', $project);

        return DB::transaction(function () use ($actor, $project, $status): EngagementProject {
            $project = EngagementProject::query()->lockForUpdate()->findOrFail($project->id);
            Gate::forUser($actor)->authorize('manage', $project);
            $allowed = match ($project->status) {
                EngagementProjectStatus::Draft => [EngagementProjectStatus::Published, EngagementProjectStatus::Cancelled],
                EngagementProjectStatus::Published => [EngagementProjectStatus::Reviewing, EngagementProjectStatus::Cancelled, EngagementProjectStatus::Closed],
                EngagementProjectStatus::Reviewing => [EngagementProjectStatus::Published, EngagementProjectStatus::Cancelled, EngagementProjectStatus::Closed],
                EngagementProjectStatus::Awarded => [EngagementProjectStatus::InProgress, EngagementProjectStatus::Cancelled],
                EngagementProjectStatus::InProgress => [EngagementProjectStatus::Completed, EngagementProjectStatus::Cancelled],
                EngagementProjectStatus::Completed, EngagementProjectStatus::Cancelled, EngagementProjectStatus::Closed => [],
            };
            if (! in_array($status, $allowed, true)) {
                throw ValidationException::withMessages(['status' => 'That project status change is not allowed.']);
            }
            if ($status === EngagementProjectStatus::Published) {
                if (! $project->company()->whereNotNull('published_at')->exists()) {
                    throw ValidationException::withMessages(['status' => 'Publish the company profile before publishing a project.']);
                }
                if (blank($project->title) || mb_strlen((string) $project->description) < 50 || ($project->application_deadline !== null && $project->application_deadline->isPast())) {
                    throw ValidationException::withMessages(['status' => 'Add a complete project brief and a future application deadline before publishing.']);
                }
                $project->published_at ??= now();
            }
            $from = $project->status;
            $project->update(['status' => $status, 'published_at' => $project->published_at]);
            DB::table('engagement_project_status_changes')->insert(['engagement_project_id' => $project->id, 'actor_id' => $actor->id, 'from_status' => $from->value, 'to_status' => $status->value, 'created_at' => now()]);
            AuditLog::query()->create(['actor_id' => $actor->id, 'subject_id' => $project->id, 'event' => 'project.status_changed', 'metadata' => ['from' => $from->value, 'to' => $status->value]]);

            return $project->fresh();
        });
    }
}
