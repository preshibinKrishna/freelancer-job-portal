<?php

declare(strict_types=1);

namespace App\Domain\Projects\Actions;

use App\Models\AuditLog;
use App\Models\EngagementProject;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

final class StoreProjectAttachment
{
    public function execute(EngagementProject $project, User $actor, UploadedFile $file): void
    {
        Gate::forUser($actor)->authorize('update', $project);
        $path = $file->storeAs('projects/'.strtolower($project->public_id).'/attachments', Str::ulid().'.'.$file->extension(), 'local');
        if ($path === false) {
            throw new RuntimeException('The project attachment could not be stored.');
        }

        try {
            DB::transaction(function () use ($actor, $file, $path, $project): void {
                $attachment = $project->attachments()->create(['disk' => 'local', 'path' => $path, 'original_filename' => $file->getClientOriginalName(), 'mime_type' => $file->getMimeType() ?: 'application/octet-stream', 'size_bytes' => (int) $file->getSize()]);
                AuditLog::query()->create(['actor_id' => $actor->id, 'subject_id' => $project->id, 'event' => 'project.attachment_added', 'metadata' => ['attachment_public_id' => $attachment->public_id]]);
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }
    }
}
