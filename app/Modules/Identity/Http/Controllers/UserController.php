<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Models\User;
use App\Modules\Identity\Http\Requests\StoreUserRequest;
use App\Modules\Identity\Http\Requests\UpdateUserRequest;
use App\Modules\Identity\Models\Permission;
use App\Modules\Identity\Models\Role;
use App\Modules\Identity\Services\PermissionService;
use App\Modules\Identity\Support\AccessRegistry;
use App\Modules\Identity\Support\ModuleRegistry;
use App\Shared\Support\Result;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController
{
    public function __construct(private readonly PermissionService $permissions)
    {
    }

    public function index(): View
    {
        $users = User::with(['roles', 'permissions'])->orderBy('name')->get();
        $modules = ModuleRegistry::modules();

        return view('users.index', compact('users', 'modules'));
    }

    public function create(): View
    {
        $roles = Role::orderBy('name')->get();
        $accessGroups = AccessRegistry::groups();

        return view('users.create', compact('roles', 'accessGroups'));
    }

    public function edit(User $user): View
    {
        $roles = Role::orderBy('name')->get();
        $accessGroups = AccessRegistry::groups();
        $user->load(['roles', 'permissions']);

        return view('users.edit', compact('user', 'roles', 'accessGroups'));
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $actor = $request->user();
        $perms = $request->resolvedPermissions();
        $this->assertCanAssignPermissions($actor, $perms);

        $user = DB::transaction(function () use ($request, $data, $actor, $perms) {
            $user = User::create([
                'name' => $data['name'],
                'phone' => preg_replace('/\D+/', '', (string) $data['phone']),
                'email' => $data['email'] ?? null,
                'password' => Hash::make($data['password']),
                'active' => $request->boolean('active', true),
            ]);

            $user->roles()->sync(! empty($data['role_id']) ? [(int) $data['role_id']] : []);

            $sync = [];
            foreach ($perms as $perm) {
                $sync[$perm] = ['until' => null, 'max_amount' => null, 'granted_by' => $actor->id];
            }
            $user->permissions()->sync($sync);

            return $user;
        });

        return redirect()->route('users.index')->with('status', "تمت إضافة المستخدم {$user->name}");
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();
        $actor = $request->user();
        $perms = $request->resolvedPermissions();
        $this->assertCanAssignPermissions($actor, $perms);

        DB::transaction(function () use ($request, $data, $user, $actor, $perms) {
            $user->name = $data['name'];
            $user->phone = preg_replace('/\D+/', '', (string) $data['phone']);
            $user->email = $data['email'] ?? null;
            $user->active = $request->boolean('active', false);
            if (! empty($data['password'])) {
                $user->password = Hash::make($data['password']);
            }
            $user->save();

            $user->roles()->sync(! empty($data['role_id']) ? [(int) $data['role_id']] : []);

            $sync = [];
            foreach ($perms as $perm) {
                $sync[$perm] = ['until' => null, 'max_amount' => null, 'granted_by' => $actor->id];
            }
            $user->permissions()->sync($sync);
        });

        return redirect()->route('users.index')->with('status', 'تم تحديث المستخدم');
    }

    public function toggle(Request $request, User $user): RedirectResponse
    {
        abort_if($user->isOwner(), 403, 'لا يمكن تعطيل حساب المالك');
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

    private function assertCanAssignPermissions(User $actor, array $perms): void
    {
        if ($actor->isOwner()) {
            return;
        }

        $allowed = $actor->effectivePermissions()->all();
        foreach ($perms as $perm) {
            if (! in_array($perm, $allowed, true)) {
                abort(403, 'لا يمكنك إسناد صلاحية لا تملكها: ' . $perm);
            }
        }
        if (in_array('users.manage', $perms, true)) {
            abort(403, 'لا يمكنك إسناد صلاحية إدارة المستخدمين');
        }
    }

    private function respond(Result $result, string $redirect): RedirectResponse
    {
        if (! $result->ok) {
            return back()->withErrors(['permission' => $result->error]);
        }

        return redirect()->route($redirect)->with('status', 'تم تحديث الصلاحيات');
    }
}
