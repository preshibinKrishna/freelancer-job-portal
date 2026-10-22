<?php

declare(strict_types=1);

namespace App\Domain\Jobs\Actions;

use App\Enums\JobStatus;
use App\Models\AuditLog;
use App\Models\Job;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class DuplicateJob
{
    public function execute(Job $source, User $actor): Job
    {
        return DB::transaction(function () use ($source, $actor): Job {
            $source = Job::query()->with('skills')->lockForUpdate()->findOrFail($source->id);
            Gate::forUser($actor)->authorize('manage', $source);
            $duplicate = $source->replicate(['public_id', 'slug', 'status', 'published_at', 'closed_at', 'created_at', 'updated_at']);
            $duplicate->title = $source->title.' (Copy)';
            $duplicate->status = JobStatus::Draft;
            $duplicate->published_at = null;
            $duplicate->closed_at = null;
            $duplicate->application_deadline = null;
            $duplicate->created_by = $actor->id;
            $duplicate->slug = $this->uniqueSlug($duplicate->title);
            $duplicate->save();
            $duplicate->skills()->sync($source->skills->modelKeys());
            AuditLog::query()->create([
                'actor_id' => $actor->id,
                'event' => 'job.duplicated',
                'metadata' => ['source_job_id' => $source->public_id, 'job_id' => $duplicate->public_id],
            ]);

            return $duplicate;
        });
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'technology-job';
        $slug = $base;
        $suffix = 2;
        while (Job::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
