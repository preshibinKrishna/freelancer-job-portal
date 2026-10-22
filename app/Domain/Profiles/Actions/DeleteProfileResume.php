<?php

declare(strict_types=1);

namespace App\Domain\Profiles\Actions;

use App\Models\AuditLog;
use App\Models\ProfileResume;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

final class DeleteProfileResume
{
    public function execute(ProfileResume $resume, User $actor): void
    {
        Gate::forUser($actor)->authorize('delete', $resume);
        $disk = $resume->disk;
        $path = $resume->path;

        DB::transaction(function () use ($actor, $resume): void {
            AuditLog::query()->create(['actor_id' => $actor->id, 'subject_id' => $actor->id, 'event' => 'talent_profile.resume_deleted']);
            $resume->delete();
        });

        Storage::disk($disk)->delete($path);
    }
}
