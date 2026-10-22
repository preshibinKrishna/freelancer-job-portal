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

final class MoveCandidatePipelineStage
{
    public function execute(RecruitmentPipelineEntry $entry, User $actor, CandidatePipelineStage $to, ?string $note = null): RecruitmentPipelineEntry
    {
        $entry->loadMissing('recruitmentRequest');
        Gate::forUser($actor)->authorize('view', $entry->recruitmentRequest);

        return DB::transaction(function () use ($entry, $actor, $to, $note): RecruitmentPipelineEntry {
            $request = RecruitmentRequest::query()->lockForUpdate()->findOrFail($entry->recruitment_request_id);
            $entry = RecruitmentPipelineEntry::query()->lockForUpdate()->findOrFail($entry->id);
            if ($entry->recruitment_request_id !== $request->id) {
                throw ValidationException::withMessages(['entry' => 'Candidate pipeline entry no longer belongs to this requisition.']);
            }
            $isRecruiter = Gate::forUser($actor)->allows('managePipeline', $request);
            $isClient = Gate::forUser($actor)->allows('clientReview', $request);
            $from = $entry->stage;
            if ($from === CandidatePipelineStage::ClientReview) {
                Gate::forUser($actor)->authorize('review', $entry);
            } elseif (! $isRecruiter) {
                Gate::forUser($actor)->authorize('manage', $entry);
            }
            if (! in_array($to, $from->nextStages(), true)) {
                throw ValidationException::withMessages(['stage' => 'That pipeline stage transition is not allowed.']);
            }
            if ($from === CandidatePipelineStage::ClientReview && ! $isClient) {
                throw ValidationException::withMessages(['stage' => 'Only the company hiring team can decide at Client Review.']);
            }
            if ($request->status !== RecruitmentRequestStatus::Active) {
                throw ValidationException::withMessages(['request' => 'Pipeline stages can change only for active requisitions.']);
            }
            $entry->update(['stage' => $to, 'placed_at' => $to === CandidatePipelineStage::Placed ? now() : null]);
            RecruitmentPipelineStageChange::query()->create(['pipeline_entry_id' => $entry->id, 'actor_id' => $actor->id, 'from_stage' => $from->value, 'to_stage' => $to->value, 'note' => $note, 'created_at' => now()]);
            app(RecordRecruitmentActivity::class)->execute($request, $entry, $actor, 'candidate.stage_changed', ['from' => $from->value, 'to' => $to->value]);
            if ($to === CandidatePipelineStage::Placed && $request->pipelineEntries()->where('stage', CandidatePipelineStage::Placed)->count() >= $request->number_of_openings) {
                $request->update(['status' => RecruitmentRequestStatus::Filled]);
                app(RecordRecruitmentActivity::class)->execute($request, null, $actor, 'request.filled');
            }
            AuditLog::query()->create(['actor_id' => $actor->id, 'subject_id' => $entry->id, 'event' => 'recruitment.pipeline_stage_changed', 'metadata' => ['request_public_id' => $request->public_id, 'from' => $from->value, 'to' => $to->value]]);

            return $entry->fresh(['candidate.profile.skills', 'recruitmentRequest']);
        });
    }
}
