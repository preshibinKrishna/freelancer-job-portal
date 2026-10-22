<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Geography\Actions\DeleteLocation;
use App\Domain\Geography\Actions\SaveLocation;
use App\Enums\LocationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\SaveLocationRequest;
use App\Models\Location;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class LocationController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Location::class);

        return view('admin.locations.index', [
            'locations' => Location::query()->with('parent')->orderBy('type')->orderBy('name')->paginate(30),
            'types' => LocationType::cases(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Location::class);

        return view('admin.locations.form', [
            'location' => new Location,
            'types' => LocationType::cases(),
            'parents' => Location::query()->orderBy('name')->get(),
        ]);
    }

    public function store(SaveLocationRequest $request, SaveLocation $saveLocation): RedirectResponse
    {
        $location = $saveLocation->create($request->validated(), $request->user());

        return to_route('admin.locations.index')->with('status', "{$location->name} was added.");
    }

    public function edit(Location $location): View
    {
        Gate::authorize('update', $location);

        return view('admin.locations.form', [
            'location' => $location,
            'types' => LocationType::cases(),
            'parents' => Location::query()->whereKeyNot($location->id)->orderBy('name')->get(),
        ]);
    }

    public function update(SaveLocationRequest $request, Location $location, SaveLocation $saveLocation): RedirectResponse
    {
        $location = $saveLocation->update($location, $request->validated(), $request->user());

        return to_route('admin.locations.index')->with('status', "{$location->name} was updated.");
    }

    public function destroy(Location $location, DeleteLocation $deleteLocation): RedirectResponse
    {
        Gate::authorize('delete', $location);
        $deleteLocation->execute($location, request()->user());

        return to_route('admin.locations.index')->with('status', 'Location removed.');
    }
}
