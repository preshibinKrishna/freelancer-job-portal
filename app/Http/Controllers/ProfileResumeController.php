<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ProfileResume;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfileResumeController extends Controller
{
    public function download(ProfileResume $resume): StreamedResponse
    {
        Gate::authorize('download', $resume);
        $stream = Storage::disk($resume->disk)->readStream($resume->path);
        abort_unless(is_resource($stream), 404);
        $extension = pathinfo($resume->original_filename, PATHINFO_EXTENSION);
        $filename = Str::slug(pathinfo($resume->original_filename, PATHINFO_FILENAME)).($extension !== '' ? '.'.strtolower($extension) : '');
        $filename = $filename !== '' ? $filename : 'resume-'.$resume->public_id;

        return response()->streamDownload(static function () use ($stream): void {
            fpassthru($stream);
            fclose($stream);
        }, $filename, [
            'Content-Type' => 'application/octet-stream',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'no-referrer',
        ]);
    }
}
