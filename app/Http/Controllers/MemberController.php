<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Department;
use App\Http\Requests\StoreMemberRequest;
use App\Http\Requests\UpdateMemberRequest;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class MemberController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Member::class);
        
        $user = Auth::user();
        $isRegularMember = $user->hasRole('member') && !$user->hasAnyRole(['super_admin', 'admin', 'pastor', 'treasurer', 'department_leader']);
        
        // Regular members can only see their own profile
        if ($isRegularMember) {
            $member = $user->member;
            if (!$member) {
                abort(404, 'No member profile found. Please contact administrator.');
            }
            return view('members.index', compact('member', 'isRegularMember'));
        }
        
        // Admins and staff see all members
        $query = Member::with('departments');

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Gender filter
        if ($request->filled('gender')) {
            $query->where('gender', $request->gender);
        }

        // Registration type filter
        if ($request->filled('registration_type')) {
            $query->where('registration_type', $request->registration_type);
        }

        $members = $query->orderBy('created_at', 'desc')->paginate(15);
        $isRegularMember = false;
        
        return view('members.index', compact('members', 'isRegularMember'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $this->authorize('create', Member::class);
        $departments = Department::all();
        return view('members.create', compact('departments'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMemberRequest $request): RedirectResponse
    {
        $this->authorize('create', Member::class);
        
        $data = $request->validated();
        
        // Handle profile photo upload
        if ($request->hasFile('profile_photo')) {
            $path = $request->file('profile_photo')->store('profile_photos', 'public');
            $data['profile_photo'] = $path;
        }

        // Generate email if not provided
        if (empty($data['email'])) {
            $baseSlug = \Illuminate\Support\Str::slug($data['full_name'], '.');
            if (empty($baseSlug)) {
                $baseSlug = 'mshiriki';
            }
            $emailCandidate = $baseSlug . rand(100, 999) . '@hosannachurch.org';
            while (\App\Models\Member::where('email', $emailCandidate)->exists() || \App\Models\User::where('email', $emailCandidate)->exists()) {
                $emailCandidate = $baseSlug . rand(1000, 9999) . '@hosannachurch.org';
            }
            $data['email'] = $emailCandidate;
        }

        $plainPassword = !empty($data['password']) ? $data['password'] : 'password123';

        // Create User
        \App\Models\User::$createMemberProfile = false;
        try {
            $user = \App\Models\User::create([
                'name'     => $data['full_name'],
                'email'    => $data['email'],
                'password' => \Illuminate\Support\Facades\Hash::make($plainPassword),
            ]);
        } finally {
            \App\Models\User::$createMemberProfile = true;
        }

        // Assign Role
        $role = $request->input('member_type', 'member');
        if (!empty($role)) {
            $user->assignRole($role);
        }

        $data['user_id'] = $user->id;
        $data['status']  = $data['status'] ?? 'active';

        $member = Member::create($data);
        
        if (isset($data['departments'])) {
            $member->departments()->sync($data['departments']);
        }

        // Send Welcome SMS with login credentials if phone number is present
        if (!empty($member->phone)) {
            \App\Services\SmsService::sendRegistrationWelcome(
                $member->phone,
                $member->full_name,
                $user->email,
                $plainPassword
            );
        }

        return redirect()->route('members.index')->with('success', 'Mshiriki mpya ' . $member->full_name . ' amesajiliwa kikamilifu!');
    }

    /**
     * Resend login credentials SMS to a member.
     */
    public function sendCredentialsSms(Member $member): RedirectResponse
    {
        $this->authorize('update', $member);

        if (empty($member->phone)) {
            return back()->with('error', 'Mshiriki huyu hana namba ya simu.');
        }

        $user = $member->user;
        $defaultPassword = 'password123';

        if (!$user) {
            \App\Models\User::$createMemberProfile = false;
            try {
                $user = \App\Models\User::firstOrCreate(
                    ['email' => $member->email],
                    [
                        'name'     => $member->full_name,
                        'password' => \Illuminate\Support\Facades\Hash::make($defaultPassword),
                    ]
                );
            } finally {
                \App\Models\User::$createMemberProfile = true;
            }
            if (!$user->hasAnyRole(\Spatie\Permission\Models\Role::all())) {
                $user->assignRole('member');
            }
            $member->update(['user_id' => $user->id]);
        } else {
            $user->update([
                'password' => \Illuminate\Support\Facades\Hash::make($defaultPassword),
            ]);
        }

        $sent = \App\Services\SmsService::sendRegistrationWelcome(
            $member->phone,
            $member->full_name,
            $user->email,
            $defaultPassword
        );

        if ($sent) {
            return back()->with('status', 'SMS ya taarifa za kuingia imetumwa kikamilifu kwenda ' . $member->phone);
        }

        return back()->with('error', 'Imeshindwa kutuma SMS. Tafadhali hakikisha kifaa cha SMS Gate kipo hewani.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Member $member): View
    {
        $this->authorize('view', $member);
        return view('members.show', compact('member'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Member $member): View
    {
        $this->authorize('update', $member);
        $departments = Department::all();
        return view('members.edit', compact('member', 'departments'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMemberRequest $request, Member $member): RedirectResponse
    {
        $this->authorize('update', $member);
        $data = $request->validated();
        
        // Handle profile photo upload
        if ($request->hasFile('profile_photo')) {
            // Delete old photo if exists
            if ($member->profile_photo) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($member->profile_photo);
            }
            
            // Store new photo
            $path = $request->file('profile_photo')->store('profile_photos', 'public');
            $data['profile_photo'] = $path;
        }
        
        $member->update($data);
        
        if (isset($data['departments'])) {
            $member->departments()->sync($data['departments']);
        } else {
            $member->departments()->detach();
        }

        return redirect()->route('members.show', $member)->with('status', 'Member updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Member $member): RedirectResponse
    {
        $this->authorize('delete', $member);
        $member->delete();
        return redirect()->route('members.index')->with('status', 'Member deleted successfully');
    }
}
