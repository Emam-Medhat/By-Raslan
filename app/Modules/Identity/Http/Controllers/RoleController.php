<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Modules\Identity\Models\Permission;
use App\Modules\Identity\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RoleController
{
    public function index(): View
    {
        $roles = Role::withCount('users')->with('permissions')->orderBy('name')->get();
        $permissions = Permission::orderBy('grp')->orderBy('id')->get()->groupBy('grp');

        return view('roles.index', compact('roles', 'permissions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:64', 'alpha_dash:ascii', Rule::unique('roles', 'slug')],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,id'],
        ], [
            'name.required' => 'اسم الدور مطلوب.',
            'slug.required' => 'المعرّف مطلوب.',
            'slug.unique' => 'المعرّف مستخدم بالفعل.',
            'slug.alpha_dash' => 'المعرّف يجب أن يكون حروف/أرقام/شرطات إنجليزية.',
        ]);

        $this->guardPermissionEscalation($request, $data['permissions'] ?? []);

        $role = Role::create([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
        ]);
        $role->permissions()->sync($data['permissions'] ?? []);

        return redirect()->route('roles.index')->with('status', "تم إنشاء الدور {$role->name}");
    }

    public function edit(Role $role): View
    {
        $permissions = Permission::orderBy('grp')->orderBy('id')->get()->groupBy('grp');
        $role->load('permissions');

        return view('roles.edit', compact('role', 'permissions'));
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:64', 'alpha_dash:ascii', Rule::unique('roles', 'slug')->ignore($role->id)],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,id'],
        ], [
            'name.required' => 'اسم الدور مطلوب.',
            'slug.required' => 'المعرّف مطلوب.',
            'slug.unique' => 'المعرّف مستخدم بالفعل.',
        ]);

        $this->guardPermissionEscalation($request, $data['permissions'] ?? []);

        $role->update([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
        ]);
        $role->permissions()->sync($data['permissions'] ?? []);

        return redirect()->route('roles.index')->with('status', "تم تحديث الدور {$role->name}");
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->users()->exists()) {
            return back()->withErrors(['role' => 'لا يمكن حذف دور مرتبط بمستخدمين. انقل المستخدمين أولاً.']);
        }

        $name = $role->name;
        $role->permissions()->detach();
        $role->delete();

        return redirect()->route('roles.index')->with('status', "تم حذف الدور {$name}");
    }

    private function guardPermissionEscalation(Request $request, array $permissions): void
    {
        $actor = $request->user();
        if ($actor->isOwner()) {
            return;
        }

        $allowed = $actor->effectivePermissions()->all();
        foreach ($permissions as $perm) {
            if (! in_array($perm, $allowed, true)) {
                abort(403, 'لا يمكنك إسناد صلاحية لا تملكها: ' . $perm);
            }
        }

        if (in_array('users.manage', $permissions, true) && ! $actor->isOwner()) {
            abort(403, 'لا يمكنك إسناد صلاحية إدارة المستخدمين');
        }
    }
}
