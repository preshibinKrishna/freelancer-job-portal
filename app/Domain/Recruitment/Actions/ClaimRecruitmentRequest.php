<?php

declare(strict_types=1);

namespace App\Domain\Recruitment\Actions;

use App\Enums\RecruitmentRequestStatus;
use App\Models\AuditLog;
use App\Models\RecruitmentRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class ClaimRecruitmentRequest
{
    public function execute(RecruitmentRequest $request, User $actor): RecruitmentRequest
    {
        Gate::forUser($actor)->authorize('claim', $request);

        return DB::transaction(function () use ($request, $actor): RecruitmentRequest {
            $request = RecruitmentRequest::query()->lockForUpdate()->findOrFail($request->id);
            Gate::forUser($actor)->authorize('claim', $request);
            if ($request->assigned_recruiter_id !== null || in_array($request->status, [RecruitmentRequestStatus::Filled, RecruitmentRequestStatus::Cancelled], true)) {
                throw ValidationException::withMessages(['request' => 'This requisition is already assigned or closed.']);
            }
            $request->update(['assigned_recruiter_id' => $actor->id, 'status' => RecruitmentRequestStatus::Active]);
            app(RecordRecruitmentActivity::class)->execute($request, null, $actor, 'request.claimed');
            AuditLog::query()->create(['actor_id' => $actor->id, 'subject_id' => $request->id, 'event' => 'recruitment.request_claimed', 'metadata' => ['request_public_id' => $request->public_id]]);

            return $request->fresh(['company', 'assignedRecruiter', 'skills']);
        });
    }
}
