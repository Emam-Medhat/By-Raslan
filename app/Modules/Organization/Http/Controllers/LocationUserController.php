<?php

namespace App\Modules\Organization\Http\Controllers;

use App\Models\User;
use App\Modules\Organization\Models\Location;
use App\Modules\Organization\Models\LocationUser;
use App\Modules\Organization\Services\LocationUserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LocationUserController
{
    public function __construct(private readonly LocationUserService $service)
    {
    }

    /**
     * List all users assigned to a location.
     */
    public function index(Location $location): View
    {
        $assignments = $this->service->getLocationUsers($location);
        $availableUsers = User::whereDoesntHave('locations', function ($q) use ($location) {
            $q->where('locations.id', $location->id);
        })->orderBy('name')->get();

        return view('locations.users.index', compact('location', 'assignments', 'availableUsers'));
    }

    /**
     * Assign a user to a location.
     */
    public function store(Request $request, Location $location): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'role' => ['required', 'string', 'in:' . implode(',', array_keys(LocationUser::ROLES))],
            'is_primary' => ['nullable', 'boolean'],
            'permissions' => ['nullable', 'array'],
        ]);

        $this->service->assign(
            User::findOrFail($validated['user_id']),
            $location,
            $validated['role'],
            $validated['permissions'] ?? [],
            $validated['is_primary'] ?? false
        );

        return redirect()->route('locations.users.index', $location)
            ->with('status', 'تم إسناد المستخدم للفرع');
    }

    /**
     * Update a user's assignment.
     */
    public function update(Request $request, Location $location, LocationUser $assignment): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'string', 'in:' . implode(',', array_keys(LocationUser::ROLES))],
            'is_primary' => ['nullable', 'boolean'],
            'permissions' => ['nullable', 'array'],
        ]);

        $this->service->updateRole(
            $assignment,
            $validated['role'],
            $validated['permissions'] ?? [],
            $validated['is_primary'] ?? false
        );

        return redirect()->route('locations.users.index', $location)
            ->with('status', 'تم تحديث إسناد المستخدم');
    }

    /**
     * Remove a user from a location.
     */
    public function destroy(Location $location, LocationUser $assignment): RedirectResponse
    {
        $this->service->remove($location, $assignment->user);

        return redirect()->route('locations.users.index', $location)
            ->with('status', 'تم إزالة المستخدم من الفرع');
    }
}