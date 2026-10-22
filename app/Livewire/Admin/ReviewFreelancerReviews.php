<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Domain\Freelancers\Actions\ModerateFreelancerReview as ModerateReviewAction;
use App\Enums\FreelancerReviewStatus;
use App\Models\FreelancerReview;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

class ReviewFreelancerReviews extends Component
{
    public function moderate(string $reviewId, string $decision, ModerateReviewAction $moderate): void
    {
        abort_unless(in_array($decision, [FreelancerReviewStatus::Approved->value, FreelancerReviewStatus::Rejected->value], true), 422);
        $review = FreelancerReview::query()->where('public_id', $reviewId)->where('moderation_status', FreelancerReviewStatus::Pending)->firstOrFail();
        $moderate->execute($review, Auth::user(), FreelancerReviewStatus::from($decision));
        session()->flash('status', 'Review moderation decision saved.');
    }

    public function render(): View
    {
        Gate::authorize('moderate', FreelancerReview::class);

        return view('livewire.admin.review-freelancer-reviews', [
            'reviews' => FreelancerReview::query()->where('moderation_status', FreelancerReviewStatus::Pending)->with(['reviewer', 'company', 'project.freelancerProfile.talentProfile.user', 'project.category'])->oldest()->get(),
        ])->extends('layouts.livewire', ['title' => 'Review moderation · M Talent']);
    }
}
