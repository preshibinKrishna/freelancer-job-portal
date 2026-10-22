<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Domain\Admin\Services\AdminSectionCatalog;
use App\Domain\Identity\Actions\ChangeAccountStatus;
use App\Models\Company;
use App\Models\ConversationReport;
use App\Models\Job;
use App\Models\User;
use App\Models\Verification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class AdminConsole extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function setAccountStatus(string $publicId, string $status, ChangeAccountStatus $changeStatus): void
    {
        abort_unless(in_array($status, ['active', 'suspended'], true), 422);
        $user = User::query()->where('public_id', $publicId)->firstOrFail();
        Gate::authorize('suspend', $user);

        if ($status === 'suspended') {
            $changeStatus->suspend($user, Auth::user());
        } else {
            $changeStatus->activate($user, Auth::user());
        }
        session()->flash('status', 'Account status updated.');
    }

    public function render(AdminSectionCatalog $catalog): View
    {
        $sections = $catalog->sections();
        $sectionKey = request()->route('section') ?: 'dashboard';
        abort_unless(isset($sections[$sectionKey]), 404);
        $definition = $sections[$sectionKey];
        Gate::authorize('admin.section', $definition['permission']);

        $availableSections = array_filter($sections, fn (array $section): bool => Auth::user()->hasPermission($section['permission']));
        $records = $this->records($sectionKey, $definition);

        return view('livewire.admin.console', [
            'sections' => $availableSections,
            'activeSection' => $sectionKey,
            'definition' => $definition,
            'records' => $records,
            'metrics' => $sectionKey === 'dashboard' ? $this->metrics() : [],
        ])->extends('layouts.admin', ['title' => $definition['label'].' · M Talent administration', 'adminSections' => $availableSections, 'activeSection' => $sectionKey]);
    }

    /** @param array<string, mixed> $definition */
    private function records(string $section, array $definition): ?LengthAwarePaginator
    {
        if ($section === 'dashboard' || $definition['model'] === null) {
            return null;
        }

        /** @var class-string<Model> $model */
        $model = $definition['model'];
        $query = $model::query()->with($definition['relations']);
        if ($section === 'candidates') {
            $query->whereHas('user.roles', fn (Builder $roles) => $roles->where('name', 'candidate'));
        }
        if ($section === 'freelancers') {
            $query->whereHas('talentProfile.user.roles', fn (Builder $roles) => $roles->where('name', 'freelancer'));
        }
        if (isset($definition['scope']) && $definition['scope'] instanceof \Closure) {
            ($definition['scope'])($query);
        }
        if (trim($this->search) !== '') {
            $term = '%'.trim($this->search).'%';
            $searchable = $definition['search'];
            $query->where(function (Builder $builder) use ($searchable, $term): void {
                foreach ($searchable as $path) {
                    $this->wherePath($builder, $path, $term);
                }
            });
        }

        return $query->latest()->paginate(30);
    }

    private function wherePath(Builder $query, string $path, string $term): void
    {
        $parts = explode('.', $path, 2);
        if (count($parts) === 2) {
            $query->orWhereHas($parts[0], fn (Builder $related) => $this->wherePath($related, $parts[1], $term));

            return;
        }
        $query->orWhere($path, 'like', $term);
    }

    /** @return array<string, int> */
    private function metrics(): array
    {
        return [
            'Users' => User::query()->count(),
            'Companies' => Company::query()->count(),
            'Open jobs' => Job::query()->where('status', 'published')->count(),
            'Pending verification' => Verification::query()->where('status', 'pending')->count(),
            'Reports' => ConversationReport::query()->count(),
        ];
    }
}
