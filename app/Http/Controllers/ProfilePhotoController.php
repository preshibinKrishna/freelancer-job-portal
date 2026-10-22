<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfilePhotoController extends Controller
{
    public function show(User $user): StreamedResponse
    {
        $profile = $user->profile;
        abort_if($profile === null || $profile->photo_path === null || $profile->photo_disk === null, 404);
        Gate::authorize('viewPublic', $profile);
        $stream = Storage::disk($profile->photo_disk)->readStream($profile->photo_path);
        abort_unless(is_resource($stream), 404);

        return response()->stream(static function () use ($stream): void {
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => $profile->photo_mime_type ?? 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="profile-photo"',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'no-referrer',
        ]);
    }
}
