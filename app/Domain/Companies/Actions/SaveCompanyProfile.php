<?php

declare(strict_types=1);

namespace App\Domain\Companies\Actions;

use App\Enums\CompanyMemberRole;
use App\Enums\LocationType;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\CompanyMembership;
use App\Models\Location;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SaveCompanyProfile
{
    /** @param array<string, mixed> $attributes */
    public function create(User $owner, array $attributes): Company
    {
        return DB::transaction(function () use ($owner, $attributes): Company {
            $this->validateLocations($attributes);
            $company = Company::query()->create($this->companyAttributes($attributes) + [
                'owner_id' => $owner->id,
                'slug' => $this->uniqueSlug((string) $attributes['name']),
            ]);

            CompanyMembership::query()->create([
                'company_id' => $company->id,
                'user_id' => $owner->id,
                'role' => CompanyMemberRole::Owner,
                'status' => 'active',
            ]);
            $this->grantEmployerCapability($owner, $owner);
            $this->syncOfficeLocations($company, $attributes['office_location_ids'] ?? []);
            AuditLog::query()->create(['actor_id' => $owner->id, 'event' => 'company.created', 'metadata' => ['company_id' => $company->id]]);

            return $company->fresh(['headquarters', 'itPark', 'officeLocations']) ?? $company;
        });
    }

    /** @param array<string, mixed> $attributes */
    public function update(Company $company, User $actor, array $attributes): Company
    {
        return DB::transaction(function () use ($company, $actor, $attributes): Company {
            $company = Company::query()->lockForUpdate()->findOrFail($company->id);
            $this->validateLocations($attributes);
            $company->fill($this->companyAttributes($attributes) + ['slug' => $this->uniqueSlug((string) $attributes['name'], $company)]);
            $company->save();
            $this->syncOfficeLocations($company, $attributes['office_location_ids'] ?? []);
            AuditLog::query()->create(['actor_id' => $actor->id, 'event' => 'company.profile_updated', 'metadata' => ['company_id' => $company->id]]);

            return $company->fresh(['headquarters', 'itPark', 'officeLocations']) ?? $company;
        });
    }

    /** @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    private function companyAttributes(array $attributes): array
    {
        return [
            'name' => trim((string) $attributes['name']),
            'description' => $attributes['description'] ?: null,
            'industry' => $attributes['industry'] ?: null,
            'website' => $attributes['website'] ?: null,
            'company_size' => $attributes['company_size'] ?: null,
            'founded_year' => $attributes['founded_year'] ? (int) $attributes['founded_year'] : null,
            'headquarters_location_id' => $attributes['headquarters_location_id'] ?: null,
            'it_park_id' => $attributes['it_park_id'] ?: null,
            'social_links' => array_filter($attributes['social_links'] ?? [], fn (mixed $url): bool => is_string($url) && $url !== ''),
            'contact_name' => $attributes['contact_name'] ?: null,
            'contact_email' => $attributes['contact_email'] ?: null,
            'contact_phone' => $attributes['contact_phone'] ?: null,
            'benefits' => $attributes['benefits'] ?? [],
            'technology_stack' => $attributes['technology_stack'] ?? [],
            'company_culture' => $attributes['company_culture'] ?: null,
            'published_at' => ($attributes['published'] ?? false) ? now() : null,
            'logo_path' => $attributes['logo_path'] ?? null,
            'cover_image_path' => $attributes['cover_image_path'] ?? null,
        ];
    }

    /** @param array<string, mixed> $attributes */
    private function validateLocations(array $attributes): void
    {
        $headquartersId = $attributes['headquarters_location_id'] ?? null;
        if ($headquartersId && ! Location::query()->whereKey($headquartersId)->whereIn('type', [LocationType::City, LocationType::Area, LocationType::ItPark])->exists()) {
            throw ValidationException::withMessages(['headquartersLocationId' => 'Choose a city, area, or IT park for headquarters.']);
        }

        $parkId = $attributes['it_park_id'] ?? null;
        if ($parkId && ! Location::query()->whereKey($parkId)->where('type', LocationType::ItPark->value)->exists()) {
            throw ValidationException::withMessages(['itParkId' => 'Choose a location recorded as an IT park.']);
        }

        $officeIds = $attributes['office_location_ids'] ?? [];
        if ($officeIds !== [] && Location::query()->whereIn('id', $officeIds)->whereNotIn('type', [LocationType::City, LocationType::Area, LocationType::ItPark])->exists()) {
            throw ValidationException::withMessages(['officeLocationIds' => 'Office locations must be cities, areas, or IT parks.']);
        }
    }

    /** @param array<int, int|string> $locationIds */
    private function syncOfficeLocations(Company $company, array $locationIds): void
    {
        $currentIds = $company->officeLocations()->pluck('locations.id')->all();
        if ($currentIds !== []) {
            $company->locations()->wherePivot('context', 'office')->detach($currentIds);
        }

        foreach (array_unique($locationIds) as $locationId) {
            $company->locations()->attach($locationId, ['context' => 'office']);
        }
    }

    private function uniqueSlug(string $name, ?Company $ignore = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 2;

        while (Company::query()->where('slug', $slug)->when($ignore, fn ($query) => $query->whereKeyNot($ignore->id))->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    private function grantEmployerCapability(User $user, User $actor): void
    {
        $role = Role::query()->where('name', 'employer')->firstOrFail();
        $user->roles()->syncWithoutDetaching([$role->id => ['assigned_by' => $actor->id]]);
    }
}
