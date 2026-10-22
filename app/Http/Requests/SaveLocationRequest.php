<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\LocationType;
use App\Models\Location;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $location = $this->route('location');

        return $location instanceof Location
            ? ($this->user()?->can('update', $location) ?? false)
            : ($this->user()?->can('create', Location::class) ?? false);
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(LocationType::class)],
            'name' => ['required', 'string', 'max:120'],
            'parent_id' => ['nullable', 'integer', 'exists:locations,id'],
            'country_code' => ['nullable', 'string', 'size:2', 'regex:/^[A-Za-z]{2}$/', Rule::requiredIf($this->input('type') === LocationType::Country->value)],
        ];
    }
}
