<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', User::class);
        $users = User::with('roles')->orderBy('created_at', 'desc')->paginate(15);
        return view('users.index', compact('users'));
    }

    public function create(): View
    {
        $this->authorize('create', User::class);
        $roles = Role::all();
        $members = Member::whereNull('user_id')
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'email', 'member_number']);
        return view('users.create', compact('roles', 'members'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'member_id' => 'required|exists:members,id',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|exists:roles,name',
        ]);

        $member = Member::findOrFail($validated['member_id']);

        // A member can only be linked to a single user account
        if ($member->user_id) {
            return back()->withInput()->withErrors([
                'member_id' => 'This member already has a user account.',
            ]);
        }

        // The member profile already exists, so skip auto-creating one
        User::$createMemberProfile = false;

        $user = User::create([
            'name' => $member->full_name,
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
        ]);

        $member->update(['user_id' => $user->id]);

        $user->assignRole($validated['role']);

        return redirect()->route('users.index')
            ->with('status', 'User created successfully');
    }

    public function show(User $user): View
    {
        $this->authorize('view', $user);
        return view('users.show', compact('user'));
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);
        $roles = Role::all();
        return view('users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|exists:roles,name',
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if ($request->filled('password')) {
            $user->update(['password' => bcrypt($validated['password'])]);
        }

        $user->syncRoles([$validated['role']]);

        return redirect()->route('users.index')
            ->with('status', 'User updated successfully');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account');
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('status', 'User deleted successfully');
    }
}
