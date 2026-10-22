<?php

declare(strict_types=1);

namespace App\Domain\Companies\Actions;

use App\Enums\CompanyVerificationStatus;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\CompanyVerification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewCompanyVerification
{
    public function decide(CompanyVerification $verification, User $reviewer, CompanyVerificationStatus $decision, ?string $notes): CompanyVerification
    {
        if (! in_array($decision, [CompanyVerificationStatus::Verified, CompanyVerificationStatus::Rejected], true)) {
            throw ValidationException::withMessages(['decision' => 'Choose approve or reject.']);
        }

        $verificationId = $verification->id;

        return DB::transaction(function () use ($verificationId, $reviewer, $decision, $notes): CompanyVerification {
            $verification = CompanyVerification::query()->lockForUpdate()->findOrFail($verificationId);
            if ($verification->status !== CompanyVerificationStatus::Pending) {
                throw ValidationException::withMessages(['verification' => 'This request has already been reviewed.']);
            }
            $verification->update([
                'status' => $decision,
                'reviewed_by' => $reviewer->id,
                'reviewer_notes' => $notes ? trim($notes) : null,
                'reviewed_at' => now(),
            ]);
            Company::query()->whereKey($verification->company_id)->lockForUpdate()->update(['verification_status' => $decision->value]);
            AuditLog::query()->create([
                'actor_id' => $reviewer->id,
                'event' => 'company.verification_reviewed',
                'metadata' => ['company_id' => $verification->company_id, 'verification_id' => $verification->id, 'decision' => $decision->value],
            ]);

            return $verification->fresh() ?? $verification;
        });
    }
}
