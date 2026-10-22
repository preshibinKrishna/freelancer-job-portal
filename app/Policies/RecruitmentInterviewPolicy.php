<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\RecruitmentInterview;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class RecruitmentInterviewPolicy
{
    public function view(User $actor, RecruitmentInterview $interview): bool
    {
        return Gate::forUser($actor)->allows('view', $interview->pipelineEntry->recruitmentRequest);
    }

    public function manage(User $actor, RecruitmentInterview $interview): bool
    {
        return Gate::forUser($actor)->allows('manage', $interview->pipelineEntry);
    }
}
