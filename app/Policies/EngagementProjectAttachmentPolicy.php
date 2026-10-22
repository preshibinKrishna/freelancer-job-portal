<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\EngagementProjectAttachment;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class EngagementProjectAttachmentPolicy
{
    public function download(User $actor, EngagementProjectAttachment $attachment): bool
    {
        $project = $attachment->project;

        return $project !== null && Gate::forUser($actor)->allows('accessAttachment', $project);
    }
}
