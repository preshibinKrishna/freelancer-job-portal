<?php

declare(strict_types=1);

namespace App\Domain\Recruitment\Actions;

use App\Enums\RecruitmentInterviewStatus;
use App\Models\AuditLog;
use App\Models\RecruitmentInterview;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class UpdateRecruitmentInterview
{
    public function execute(RecruitmentInterview $interview, User $actor, RecruitmentInterviewStatus $status): RecruitmentInterview
    {
        $interview->loadMissing('pipelineEntry.recruitmentRequest');
        Gate::forUser($actor)->authorize('manage', $interview);

        return DB::transaction(function () use ($interview, $actor, $status): RecruitmentInterview {
            $interview = RecruitmentInterview::query()->lockForUpdate()->with('pipelineEntry.recruitmentRequest')->findOrFail($interview->id);
            Gate::forUser($actor)->authorize('manage', $interview);
            if ($interview->status !== RecruitmentInterviewStatus::Scheduled || $status === RecruitmentInterviewStatus::Scheduled) {
                throw ValidationException::withMessages(['interview' => 'Only scheduled interviews can be completed, cancelled, or marked no-show.']);
            }
            $interview->update(['status' => $status]);
            app(RecordRecruitmentActivity::class)->execute($interview->pipelineEntry->recruitmentRequest, $interview->pipelineEntry, $actor, 'interview.'.$status->value, ['interview_public_id' => $interview->public_id]);
            AuditLog::query()->create(['actor_id' => $actor->id, 'subject_id' => $interview->id, 'event' => 'recruitment.interview_status_changed', 'metadata' => ['status' => $status->value]]);

            return $interview->fresh();
        });
    }
}
