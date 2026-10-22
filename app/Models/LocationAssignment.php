<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\LocationAssignmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class LocationAssignment extends Model
{
    /** @use HasFactory<LocationAssignmentFactory> */
    use HasFactory;

    public $timestamps = true;

    protected $fillable = ['location_id', 'context'];

    protected static function newFactory(): LocationAssignmentFactory
    {
        return LocationAssignmentFactory::new();
    }

    /** @return BelongsTo<Location, $this> */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /** @return MorphTo<Model, $this> */
    public function locationable(): MorphTo
    {
        return $this->morphTo();
    }
}
