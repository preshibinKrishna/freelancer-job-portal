<?php

declare(strict_types=1);

namespace App\Domain\Projects\Actions;

use App\Models\EngagementProject;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class ToggleSavedProject
{
    public function execute(EngagementProject $project, User $actor): bool
    {
        Gate::forUser($actor)->authorize('save', $project);

        return DB::transaction(function () use ($actor, $project): bool {
            $saved = $actor->savedEngagementProjects()->whereKey($project->id)->exists();
            if ($saved) {
                $actor->savedEngagementProjects()->detach($project->id);

                return false;
            }
            $actor->savedEngagementProjects()->attach($project->id);

            return true;
        });
    }
}
