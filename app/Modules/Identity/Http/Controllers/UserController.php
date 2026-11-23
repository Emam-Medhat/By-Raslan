<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Models\User;
use App\Modules\Identity\Http\Requests\StoreUserRequest;
use App\Modules\Identity\Models\Permission;
use App\Modules\Identity\Models\Role;
use App\Modules\Identity\Services\PermissionService;
use App\Shared\Support\Result;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController
{
    public function __construct(private readonly PermissionService $permissions)
    {
    }

    public function index(): View
    {
        $users = User::with(['roles', 'permissions'])->orderBy('name')->get();

        return view('users.index', compact('users'));
    }

    public function create(): View
    {
        $permissions = Permission::orderBy('grp')->orderBy('id')->get();
        $roles = Role::orderBy('name')->get();

        return view('users.create', compact('permissions', 'roles'));
    }

    public function edit(User $user): View
    {
        $permissions = Permission::orderBy('grp')->orderBy('id')->get();

        return view('users.edit', compact('user', 'permissions'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($user->id)],
            'email' => ['nullable', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'max:255'],
            'active' => ['sometimes', 'boolean'],
        ]);

        $user->name = $data['name'];
        $user->phone = preg_replace('/\D+/', '', (string) $data['phone']);
        $user->email = $data['email'] ?? null;
        $user->active = $data['active'] ?? $user->active;
        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();

        return redirect()->route('users.index')->with('status', 'تم تحديث المستخدم');
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'phone' => preg_replace('/\D+/', '', (string) $data['phone']),
            'email' => $data['email'] ?? null,
            'password' => Hash::make($data['password']),
            'active' => $data['active'] ?? true,
        ]);

        if (! empty($data['role_id'])) {
            $user->roles()->sync([(int) $data['role_id']]);
        }

        // Grant any permissions submitted with the new user (anti-escalation applies).
        foreach ((array) $request->input('permissions', []) as $perm) {
            $this->permissions->grant($request->user(), $user, $perm);
        }

        return redirect()->route('users.index')->with('status', "تمت إضافة المستخدم {$user->name}");
    }

    public function toggle(Request $request, User $user): RedirectResponse
    {
        $user->update(['active' => ! $user->active]);

        return back()->with('status', $user->active ? 'تم تفعيل الحساب' : 'تم تعطيل الحساب');
    }

    public function grant(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'permission' => ['required', 'string', 'exists:permissions,id'],
            'until' => ['nullable', 'date'],
            'max_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $result = $this->permissions->grant(
            $request->user(),
            $user,
            $validated['permission'],
            $validated['until'] ?? null,
            isset($validated['max_amount']) ? (float) $validated['max_amount'] : null,
        );

        return $this->respond($result, 'users.index');
    }

    public function revoke(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'permission' => ['required', 'string', 'exists:permissions,id'],
        ]);

        $result = $this->permissions->revoke($request->user(), $user, $validated['permission']);

        return $this->respond($result, 'users.index');
    }

    private function respond(Result $result, string $redirect): RedirectResponse
    {
        if (! $result->ok) {
            return back()->withErrors(['permission' => $result->error]);
        }

        return redirect()->route($redirect)->with('status', 'تم تحديث الصلاحيات');
    }
}