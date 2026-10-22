<?php

declare(strict_types=1);

namespace App\Domain\Recruitment\Actions;

use App\Domain\Recruitment\Events\RecruitmentActivityRecorded;
use App\Models\RecruitmentActivity;
use App\Models\RecruitmentPipelineEntry;
use App\Models\RecruitmentRequest;
use App\Models\User;

final class RecordRecruitmentActivity
{
    /** @param array<string, scalar|null> $metadata */
    public function execute(RecruitmentRequest $request, ?RecruitmentPipelineEntry $entry, ?User $actor, string $type, array $metadata = []): void
    {
        RecruitmentActivity::query()->create([
            'recruitment_request_id' => $request->id,
            'pipeline_entry_id' => $entry?->id,
            'actor_id' => $actor?->id,
            'activity_type' => $type,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
        RecruitmentActivityRecorded::dispatch($request->id, $type);
    }
}
