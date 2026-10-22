<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignUserRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('assignRoles', $this->route('user')) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['role' => ['required', 'string', 'exists:roles,name']];
    }
}
