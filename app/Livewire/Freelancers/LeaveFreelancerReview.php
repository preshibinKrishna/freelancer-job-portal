<?php

declare(strict_types=1);

namespace App\Livewire\Freelancers;

use App\Domain\Freelancers\Actions\SubmitFreelancerReview;
use App\Models\FreelancerProject;
use App\Models\FreelancerReview;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

class LeaveFreelancerReview extends Component
{
    public FreelancerProject $project;

    public int $rating = 5;

    public string $title = '';

    public string $reviewText = '';

    public bool $submitted = false;

    public function mount(FreelancerProject $project): void
    {
        $this->project = $project->load(['requestedCompany', 'freelancerProfile.talentProfile']);
        Gate::authorize('create', [FreelancerReview::class, $project]);
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return ['rating' => ['required', 'integer', 'between:1,5'], 'title' => ['nullable', 'string', 'max:160'], 'reviewText' => ['required', 'string', 'min:20', 'max:2000']];
    }

    public function submit(SubmitFreelancerReview $submit): void
    {
        $data = $this->validate();
        $submit->execute($this->project, Auth::user(), $data['rating'], $data['title'] ?: null, $data['reviewText']);
        $this->submitted = true;
        session()->flash('status', 'Thank you. Your review is queued for moderation before publication.');
    }

    public function render(): View
    {
        Gate::authorize('create', [FreelancerReview::class, $this->project]);

        return view('livewire.freelancers.leave-freelancer-review');
    }
}
