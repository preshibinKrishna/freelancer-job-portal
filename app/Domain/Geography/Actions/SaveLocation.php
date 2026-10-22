<?php

declare(strict_types=1);

namespace App\Domain\Geography\Actions;

use App\Enums\LocationType;
use App\Models\AuditLog;
use App\Models\Location;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SaveLocation
{
    /** @param array{type: LocationType|string, name: string, parent_id: int|string|null, country_code?: string|null} $data */
    public function create(array $data, User $actor): Location
    {
        Gate::forUser($actor)->authorize('create', Location::class);

        return DB::transaction(function () use ($actor, $data): Location {
            $location = Location::query()->create($this->normalize($data));
            AuditLog::query()->create(['actor_id' => $actor->id, 'event' => 'admin.location_created', 'metadata' => ['location_id' => $location->public_id]]);

            return $location;
        });
    }

    /** @param array{type: LocationType|string, name: string, parent_id: int|string|null, country_code?: string|null} $data */
    public function update(Location $location, array $data, User $actor): Location
    {
        Gate::forUser($actor)->authorize('update', $location);

        return DB::transaction(function () use ($actor, $location, $data): Location {
            $location = Location::query()->lockForUpdate()->findOrFail($location->id);
            $parentId = $data['parent_id'];

            $candidateParent = $parentId === null ? null : Location::query()->findOrFail($parentId);
            while ($candidateParent !== null) {
                if ($candidateParent->is($location)) {
                    throw ValidationException::withMessages(['parent_id' => 'A location cannot be moved beneath itself or one of its descendants.']);
                }
                $candidateParent = $candidateParent->parent;
            }

            $attributes = $this->normalize($data);

            if ($location->type !== $attributes['type'] && $location->children()->exists()) {
                throw ValidationException::withMessages(['type' => 'A location with child locations cannot change type.']);
            }

            $location->fill($attributes);
            $location->save();
            AuditLog::query()->create(['actor_id' => $actor->id, 'event' => 'admin.location_updated', 'metadata' => ['location_id' => $location->public_id]]);

            return $location;
        });
    }

    /** @param array{type: LocationType|string, name: string, parent_id: int|string|null, country_code?: string|null} $data
     * @return array{type: LocationType, name: string, slug: string, parent_id: int|null, country_code: string|null}
     */
    private function normalize(array $data): array
    {
        $type = $data['type'] instanceof LocationType ? $data['type'] : LocationType::from($data['type']);
        $parent = $data['parent_id'] === null ? null : Location::query()->findOrFail($data['parent_id']);
        if ($type !== LocationType::Country && $parent === null) {
            throw ValidationException::withMessages(['parent_id' => 'Choose a parent location for this location type.']);
        }
        if ($parent !== null && ! in_array($parent->type, $type->allowedParentTypes(), true)) {
            throw ValidationException::withMessages(['parent_id' => 'Choose a parent location that matches this location type.']);
        }

        if ($type === LocationType::Country && $parent !== null) {
            throw ValidationException::withMessages(['parent_id' => 'A country must be a root location.']);
        }

        if ($type !== LocationType::Country && isset($data['country_code']) && $data['country_code'] !== '') {
            throw ValidationException::withMessages(['country_code' => 'Country codes can only be set on countries.']);
        }

        $countryCode = strtoupper($data['country_code'] ?? '');
        if ($type === LocationType::Country && ! preg_match('/^[A-Z]{2}$/', $countryCode)) {
            throw ValidationException::withMessages(['country_code' => 'Enter a two-letter country code.']);
        }

        $slug = Str::slug($data['name']);
        if ($slug === '') {
            throw ValidationException::withMessages(['name' => 'Enter a name that can be used in a location URL.']);
        }

        return [
            'type' => $type,
            'name' => trim($data['name']),
            'slug' => $slug,
            'parent_id' => $parent?->id,
            'country_code' => $type === LocationType::Country ? $countryCode : null,
        ];
    }
}
