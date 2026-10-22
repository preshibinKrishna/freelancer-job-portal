<?php

declare(strict_types=1);

namespace App\Domain\Profiles\Actions;

use App\Models\AuditLog;
use App\Models\ProfileResume;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

final class StoreProfileAsset
{
    public function photo(User $user, UploadedFile $file): UserProfile
    {
        $profile = $user->profile()->firstOrCreate();
        $newPath = $file->storeAs('talent/'.strtolower($user->public_id).'/photos', Str::ulid().'.'.$file->extension(), 'local');
        if ($newPath === false) {
            throw new RuntimeException('The profile photo could not be stored.');
        }
        $oldPath = $profile->photo_path;

        try {
            DB::transaction(function () use ($file, $profile, $newPath, $user): void {
                $profile->forceFill(['photo_disk' => 'local', 'photo_path' => $newPath, 'photo_mime_type' => $file->getMimeType()])->save();
                AuditLog::query()->create(['actor_id' => $user->id, 'subject_id' => $user->id, 'event' => 'talent_profile.photo_updated']);
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($newPath);
            throw $exception;
        }

        if ($oldPath !== null) {
            Storage::disk('local')->delete($oldPath);
        }

        return $profile->fresh();
    }

    public function resume(User $user, UploadedFile $file): ProfileResume
    {
        $profile = $user->profile()->firstOrCreate();
        $newPath = $file->storeAs('talent/'.strtolower($user->public_id).'/resumes', Str::ulid().'.'.$file->extension(), 'local');
        if ($newPath === false) {
            throw new RuntimeException('The resume could not be stored.');
        }

        try {
            return DB::transaction(function () use ($file, $newPath, $profile, $user): ProfileResume {
                $resume = $profile->resumes()->create([
                    'disk' => 'local',
                    'path' => $newPath,
                    'original_filename' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                    'size_bytes' => (int) $file->getSize(),
                    'scan_status' => 'pending',
                    'uploaded_at' => now(),
                ]);
                AuditLog::query()->create(['actor_id' => $user->id, 'subject_id' => $user->id, 'event' => 'talent_profile.resume_uploaded']);

                return $resume;
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($newPath);
            throw $exception;
        }
    }
}
