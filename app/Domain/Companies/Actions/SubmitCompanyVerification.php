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

class SubmitCompanyVerification
{
    public function execute(Company $company, User $actor, string $note): CompanyVerification
    {
        return DB::transaction(function () use ($company, $actor, $note): CompanyVerification {
            $company = Company::query()->lockForUpdate()->findOrFail($company->id);
            if ($company->verification_status === CompanyVerificationStatus::Verified) {
                throw ValidationException::withMessages(['verification' => 'This company is already verified.']);
            }
            if ($company->verification_status === CompanyVerificationStatus::Pending) {
                throw ValidationException::withMessages(['verification' => 'A verification request is already under review.']);
            }

            $verification = $company->verificationSubmissions()->create([
                'submitted_by' => $actor->id,
                'status' => CompanyVerificationStatus::Pending,
                'submission_note' => trim($note),
            ]);
            $company->update(['verification_status' => CompanyVerificationStatus::Pending]);
            AuditLog::query()->create([
                'actor_id' => $actor->id,
                'event' => 'company.verification_submitted',
                'metadata' => ['company_id' => $company->id, 'verification_id' => $verification->id],
            ]);

            return $verification;
        });
    }
}
