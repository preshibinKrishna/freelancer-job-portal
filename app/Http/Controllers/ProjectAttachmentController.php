<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\EngagementProjectAttachment;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectAttachmentController extends Controller
{
    public function download(EngagementProjectAttachment $attachment): StreamedResponse
    {
        Gate::authorize('download', $attachment);
        $stream = Storage::disk($attachment->disk)->readStream($attachment->path);
        abort_unless(is_resource($stream), 404);
        $filename = Str::slug(pathinfo($attachment->original_filename, PATHINFO_FILENAME)).'.'.strtolower(pathinfo($attachment->original_filename, PATHINFO_EXTENSION));

        return response()->streamDownload(static function () use ($stream): void {
            fpassthru($stream);
            fclose($stream);
        }, $filename, ['Content-Type' => 'application/octet-stream', 'Cache-Control' => 'private, no-store, max-age=0', 'X-Content-Type-Options' => 'nosniff', 'Referrer-Policy' => 'no-referrer']);
    }
}
