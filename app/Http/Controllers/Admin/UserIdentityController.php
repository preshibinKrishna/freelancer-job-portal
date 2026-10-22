<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Identity\Actions\AssignRole;
use App\Domain\Identity\Actions\ChangeAccountStatus;
use App\Domain\Identity\Actions\RemoveUserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssignUserRoleRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class UserIdentityController extends Controller
{
    public function assignRole(AssignUserRoleRequest $request, User $user, AssignRole $assignRole): RedirectResponse
    {
        $role = Role::query()->where('name', $request->validated('role'))->firstOrFail();
        $assignRole->handle($user, $role, $request->user());

        return back()->with('status', 'role-assigned');
    }

    public function removeRole(User $user, Role $role, RemoveUserRole $removeRole): RedirectResponse
    {
        $removeRole->handle($user, $role, request()->user());

        return back()->with('status', 'role-removed');
    }

    public function suspend(User $user, ChangeAccountStatus $changeStatus): RedirectResponse
    {
        $changeStatus->suspend($user, request()->user());

        return back()->with('status', 'account-suspended');
    }

    public function activate(User $user, ChangeAccountStatus $changeStatus): RedirectResponse
    {
        $changeStatus->activate($user, request()->user());

        return back()->with('status', 'account-activated');
    }
}
