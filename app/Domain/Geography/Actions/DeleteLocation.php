<?php

declare(strict_types=1);

namespace App\Domain\Geography\Actions;

use App\Models\AuditLog;
use App\Models\Location;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class DeleteLocation
{
    public function execute(Location $location, User $actor): void
    {
        Gate::forUser($actor)->authorize('delete', $location);
        DB::transaction(function () use ($actor, $location): void {
            $location = Location::query()->lockForUpdate()->findOrFail($location->id);

            if ($location->children()->exists() || $location->assignments()->exists() || $location->primaryProfiles()->exists()) {
                throw ValidationException::withMessages(['location' => 'Move or unassign child locations and records before deleting this location.']);
            }

            $publicId = $location->public_id;
            $location->delete();
            AuditLog::query()->create(['actor_id' => $actor->id, 'event' => 'admin.location_deleted', 'metadata' => ['location_id' => $publicId]]);
        });
    }
}
