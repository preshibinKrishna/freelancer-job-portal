<?php

declare(strict_types=1);

namespace App\Domain\Recruitment\Actions;

use App\Enums\CandidatePipelineStage;
use App\Enums\RecruitmentRequestStatus;
use App\Models\AuditLog;
use App\Models\RecruitmentPipelineEntry;
use App\Models\RecruitmentPipelineStageChange;
use App\Models\RecruitmentRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class AddCandidateToPipeline
{
    public function execute(RecruitmentRequest $request, User $candidate, User $actor): RecruitmentPipelineEntry
    {
        Gate::forUser($actor)->authorize('managePipeline', $request);
        if (! $candidate->isActive() || ! $candidate->hasVerifiedEmail() || ! $candidate->hasRole('candidate')
            || ! $candidate->profile()->where('visibility', 'public')->exists()) {
            throw ValidationException::withMessages(['candidate' => 'Only active candidates with a public profile can be added.']);
        }

        return DB::transaction(function () use ($request, $candidate, $actor): RecruitmentPipelineEntry {
            $request = RecruitmentRequest::query()->lockForUpdate()->findOrFail($request->id);
            Gate::forUser($actor)->authorize('managePipeline', $request);
            if ($request->status !== RecruitmentRequestStatus::Active) {
                throw ValidationException::withMessages(['request' => 'Only active requisitions can accept candidates.']);
            }
            if ($request->pipelineEntries()->where('candidate_id', $candidate->id)->exists()) {
                throw ValidationException::withMessages(['candidate' => 'This candidate is already in the requisition pipeline.']);
            }
            $entry = $request->pipelineEntries()->create(['candidate_id' => $candidate->id, 'added_by' => $actor->id, 'stage' => CandidatePipelineStage::New, 'added_at' => now()]);
            RecruitmentPipelineStageChange::query()->create(['pipeline_entry_id' => $entry->id, 'actor_id' => $actor->id, 'from_stage' => null, 'to_stage' => CandidatePipelineStage::New->value, 'created_at' => now()]);
            app(RecordRecruitmentActivity::class)->execute($request, $entry, $actor, 'candidate.added', ['candidate_public_id' => $candidate->public_id]);
            AuditLog::query()->create(['actor_id' => $actor->id, 'subject_id' => $entry->id, 'event' => 'recruitment.candidate_added', 'metadata' => ['request_public_id' => $request->public_id, 'candidate_public_id' => $candidate->public_id]]);

            return $entry->load(['candidate.profile.skills', 'recruitmentRequest']);
        });
    }
}
