<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Messaging\Actions\StartConversation;
use App\Models\ConversationAttachment;
use App\Models\JobApplication;
use App\Models\ProjectProposal;
use App\Models\RecruitmentPipelineEntry;
use App\Models\RecruitmentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ConversationController
{
    public function start(Request $request, string $type, string $publicId, StartConversation $start)
    {
        $context = match ($type) {
            'application' => JobApplication::query()->where('public_id', $publicId)->firstOrFail(),
            'proposal' => ProjectProposal::query()->where('public_id', $publicId)->firstOrFail(),
            'recruitment-candidate' => RecruitmentPipelineEntry::query()->where('public_id', $publicId)->firstOrFail(),
            'recruitment' => RecruitmentRequest::query()->where('public_id', $publicId)->firstOrFail(),
            default => abort(404),
        };
        $conversation = $start->execute($request->user(), $context);

        return redirect()->route('messages.show', $conversation);
    }

    public function download(Request $request, ConversationAttachment $attachment): StreamedResponse
    {
        abort_unless($request->user()->can('view', $attachment->message->conversation), 404);
        abort_unless(Storage::disk($attachment->disk)->exists($attachment->path), 404);

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name, [
            'Content-Type' => $attachment->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
