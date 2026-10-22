<?php

declare(strict_types=1);

namespace App\Domain\Recruitment\Actions;

use App\Enums\CandidatePipelineStage;
use App\Models\AuditLog;
use App\Models\RecruitmentInterview;
use App\Models\RecruitmentPipelineEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class ScheduleRecruitmentInterview
{
    /** @param array{title: string, starts_at: string, ends_at: string, location_or_link: string|null, private_notes: string|null} $data */
    public function execute(RecruitmentPipelineEntry $entry, User $actor, array $data): RecruitmentInterview
    {
        $entry->loadMissing('recruitmentRequest');
        Gate::forUser($actor)->authorize('manage', $entry);
        if ($entry->stage !== CandidatePipelineStage::Interview || strtotime($data['ends_at']) <= strtotime($data['starts_at'])) {
            throw ValidationException::withMessages(['interview' => 'Schedule interviews only for Interview-stage candidates and provide a valid time range.']);
        }

        return DB::transaction(function () use ($entry, $actor, $data): RecruitmentInterview {
            $entry = RecruitmentPipelineEntry::query()->lockForUpdate()->with('recruitmentRequest')->findOrFail($entry->id);
            Gate::forUser($actor)->authorize('manage', $entry);
            if ($entry->stage !== CandidatePipelineStage::Interview) {
                throw ValidationException::withMessages(['interview' => 'Move the candidate to Interview before scheduling.']);
            }
            $interview = $entry->interviews()->create(['scheduled_by' => $actor->id, ...$data]);
            app(RecordRecruitmentActivity::class)->execute($entry->recruitmentRequest, $entry, $actor, 'interview.scheduled', ['interview_public_id' => $interview->public_id, 'starts_at' => $interview->starts_at->toIso8601String()]);
            AuditLog::query()->create(['actor_id' => $actor->id, 'subject_id' => $interview->id, 'event' => 'recruitment.interview_scheduled', 'metadata' => ['request_public_id' => $entry->recruitmentRequest->public_id, 'pipeline_entry_public_id' => $entry->public_id]]);

            return $interview;
        });
    }
}
