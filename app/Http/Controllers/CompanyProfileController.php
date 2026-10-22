<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CompanyProfileController extends Controller
{
    public function show(Company $company): View
    {
        $user = auth()->user();
        abort_unless($company->isPublished() || ($user !== null && Gate::allows('view', $company)), 404);
        $company->load(['headquarters.parent', 'itPark.parent', 'officeLocations.parent']);
        $company->setRelation('openJobs', $company->jobs()->discoverable()->with(['location', 'itPark', 'skills'])->latest('published_at')->limit(6)->get());

        $canonical = route('companies.show', $company->slug);
        $description = $company->description ? Str::limit(strip_tags($company->description), 155) : $company->name.' company profile on M Talent.';
        $indexable = $company->isPublished();
        $sameAs = array_filter([$company->website, ...($company->social_links ?? [])]);

        return view('companies.show', [
            'company' => $company,
            'canonical' => $canonical,
            'description' => $description,
            'indexable' => $indexable,
            'structuredData' => $indexable ? [
                '@context' => 'https://schema.org',
                '@type' => 'Organization',
                'name' => $company->name,
                'url' => $canonical,
                'description' => strip_tags((string) $company->description),
                'logo' => $company->logo_path ? Storage::disk('public')->url($company->logo_path) : null,
                'sameAs' => $sameAs,
                'address' => $company->headquarters ? ['@type' => 'PostalAddress', 'addressLocality' => $company->headquarters->name, 'addressRegion' => $company->headquarters->parent?->name] : null,
            ] : null,
        ]);
    }
}
