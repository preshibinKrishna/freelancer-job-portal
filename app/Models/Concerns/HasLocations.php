<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Location;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasLocations
{
    /** @return MorphToMany<Location, $this> */
    public function locations(): MorphToMany
    {
        return $this->morphToMany(Location::class, 'locationable', 'location_assignments')
            ->withPivot('context')
            ->withTimestamps();
    }
}
