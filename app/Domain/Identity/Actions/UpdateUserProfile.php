<?php

declare(strict_types=1);

namespace App\Domain\Identity\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;

final class UpdateUserProfile
{
    /** @param array{name: string, headline?: string|null, bio?: string|null, location?: string|null, location_id?: int|null, timezone: string, locale: string} $attributes */
    public function handle(User $user, array $attributes): User
    {
        return DB::transaction(function () use ($user, $attributes): User {
            $user->update([
                'name' => $attributes['name'],
                'timezone' => $attributes['timezone'],
                'locale' => $attributes['locale'],
            ]);

            $user->profile()->updateOrCreate([], [
                'headline' => $attributes['headline'] ?? null,
                'bio' => $attributes['bio'] ?? null,
                'location' => $attributes['location'] ?? $user->profile?->location,
                'location_id' => array_key_exists('location_id', $attributes) ? $attributes['location_id'] : $user->profile?->location_id,
            ]);

            return $user->load('profile');
        });
    }
}
