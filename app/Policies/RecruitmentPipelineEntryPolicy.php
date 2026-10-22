<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\RecruitmentPipelineEntry;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class RecruitmentPipelineEntryPolicy
{
    public function view(User $actor, RecruitmentPipelineEntry $entry): bool
    {
        return Gate::forUser($actor)->allows('view', $entry->recruitmentRequest);
    }

    public function manage(User $actor, RecruitmentPipelineEntry $entry): bool
    {
        return Gate::forUser($actor)->allows('managePipeline', $entry->recruitmentRequest);
    }

    public function review(User $actor, RecruitmentPipelineEntry $entry): bool
    {
        return Gate::forUser($actor)->allows('clientReview', $entry->recruitmentRequest)
            && $entry->stage->value === 'client_review';
    }
}
