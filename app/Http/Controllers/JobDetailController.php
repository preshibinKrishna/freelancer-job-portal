<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\JobStatus;
use App\Enums\JobVisibility;
use App\Models\Job;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class JobDetailController extends Controller
{
    public function show(Job $job): View
    {
        $job->load(['company.headquarters', 'company.itPark', 'location.parent', 'itPark.parent', 'skills']);
        abort_unless(Gate::allows('view', $job), 404);

        $acceptingApplications = $job->acceptsApplications();
        $isPubliclyIndexable = $job->status === JobStatus::Published
            && $job->visibility === JobVisibility::Public
            && $acceptingApplications
            && $job->company->isPublished();

        $structuredData = $isPubliclyIndexable ? [
            '@context' => 'https://schema.org',
            '@type' => 'JobPosting',
            'title' => $job->title,
            'description' => $job->description,
            'datePosted' => $job->published_at?->toAtomString(),
            'validThrough' => $job->application_deadline?->toAtomString(),
            'employmentType' => strtoupper(str_replace('_', '-', $job->employment_type->value)),
            'identifier' => ['@type' => 'PropertyValue', 'name' => $job->company->name, 'value' => $job->public_id],
            'directApply' => true,
            'hiringOrganization' => [
                '@type' => 'Organization',
                'name' => $job->company->name,
                'url' => route('companies.show', $job->company->slug),
                'sameAs' => $job->company->website,
                'logo' => $job->company->logo_path ? Storage::disk('public')->url($job->company->logo_path) : null,
            ],
            'jobLocationType' => $job->workplace_type->value === 'remote' ? 'TELECOMMUTE' : null,
            'jobLocation' => $job->location ? ['@type' => 'Place', 'address' => ['@type' => 'PostalAddress', 'addressLocality' => $job->location->name, 'addressRegion' => $job->location->parent?->name]] : null,
        ] : null;

        return view('jobs.show', [
            'job' => $job,
            'indexable' => $isPubliclyIndexable,
            'acceptingApplications' => $acceptingApplications,
            'structuredData' => $structuredData,
        ]);
    }
}
