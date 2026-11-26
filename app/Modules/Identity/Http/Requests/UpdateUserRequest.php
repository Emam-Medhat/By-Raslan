<?php

namespace App\Modules\Identity\Http\Requests;

use App\Modules\Identity\Support\AccessRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('users.manage') ?? false;
    }

    public function rules(): array
    {
        $navIds = AccessRegistry::permissionIds();
        $userId = $this->route('user')?->id ?? $this->route('user');
        return [
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($userId)],
            'email' => ['nullable', 'email', 'max:190', Rule::unique('users', 'email')->ignore($userId)],
            'password' => ['nullable', 'string', 'min:8', 'max:255'],
            'role_id' => ['nullable', 'exists:roles,id'],
            'active' => ['sometimes', 'boolean'],
            'nav_permissions' => ['nullable', 'array'],
            'nav_permissions.*' => ['string', Rule::in($navIds)],
        ];
    }

    public function resolvedPermissions(): array
    {
        $nav = array_values(array_filter((array) $this->input('nav_permissions', []), fn($v) => is_string($v)));
        $nav = array_values(array_intersect($nav, AccessRegistry::permissionIds()));
        return AccessRegistry::expandPermissions($nav);
    }
}
