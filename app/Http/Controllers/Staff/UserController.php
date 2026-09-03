<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStaffUserRequest;
use App\Http\Requests\UpdateStaffUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $this->authorize('manage-users');

        $users = User::query()
            ->role(['admin', 'cashier'])
            ->orderBy('name')
            ->get();

        return view('staff.users.index', compact('users'));
    }

    public function store(StoreStaffUserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $user->syncRoles([$data['role']]);

        return back()->with('status', 'Staff account created.');
    }

    public function update(UpdateStaffUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        if (! empty($data['password'])) {
            $user->update(['password' => Hash::make($data['password'])]);
        }

        $user->syncRoles([$data['role']]);

        return back()->with('status', 'Staff account updated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('manage-users');

        abort_if($user->id === auth()->id(), 403, 'You cannot delete your own account.');

        $user->delete();

        return back()->with('status', 'Staff account removed.');
    }
}
