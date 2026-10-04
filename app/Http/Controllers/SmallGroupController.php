<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\SmallGroup;
use App\Models\SmallGroupMeeting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SmallGroupController extends Controller
{
    public function index()
    {
        $groups = SmallGroup::with(['leader', 'members'])->where('status', 'active')->get();
        return view('small-groups.index', compact('groups'));
    }

    public function create()
    {
        $members = Member::where('status', 'active')->orderBy('full_name')->get();
        return view('small-groups.create', compact('members'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'leader_id' => 'required|exists:members,id',
            'meeting_day' => 'nullable|string',
            'meeting_time' => 'nullable',
            'location' => 'nullable|string|max:255',
            'max_members' => 'required|integer|min:5|max:50',
        ]);

        SmallGroup::create($validated);

        return redirect()->route('small-groups.index')->with('success', 'Small Group created successfully.');
    }

    public function show(SmallGroup $smallGroup)
    {
        $smallGroup->load(['leader', 'members', 'meetings.creator']);
        $availableMembers = Member::where('status', 'active')
            ->whereDoesntHave('smallGroups', function ($query) use ($smallGroup) {
                $query->where('small_group_id', $smallGroup->id);
            })
            ->orderBy('full_name')
            ->get();

        return view('small-groups.show', compact('smallGroup', 'availableMembers'));
    }

    public function edit(SmallGroup $smallGroup)
    {
        $members = Member::where('status', 'active')->orderBy('full_name')->get();
        return view('small-groups.edit', compact('smallGroup', 'members'));
    }

    public function update(Request $request, SmallGroup $smallGroup)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'leader_id' => 'required|exists:members,id',
            'meeting_day' => 'nullable|string',
            'meeting_time' => 'nullable',
            'location' => 'nullable|string|max:255',
            'max_members' => 'required|integer|min:5|max:50',
            'status' => 'required|in:active,inactive',
        ]);

        $smallGroup->update($validated);

        return redirect()->route('small-groups.index')->with('success', 'Small Group updated successfully.');
    }

    public function destroy(SmallGroup $smallGroup)
    {
        if (!Auth::user()->hasAnyRole(['super_admin', 'admin', 'pastor'])) {
            return back()->with('error', 'Huna ruhusa ya kufuta zone.');
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($smallGroup) {
            // Detach members
            $smallGroup->members()->detach();

            // Delete meeting attendances and meetings
            foreach ($smallGroup->meetings as $meeting) {
                $meeting->attendances()->delete();
                $meeting->delete();
            }

            // Delete offerings and responses
            $smallGroup->offerings()->delete();
            $smallGroup->responses()->delete();

            // Delete group
            $smallGroup->delete();
        });

        return redirect()->route('small-groups.index')->with('success', 'Zone ya ' . $smallGroup->name . ' imefutwa kikamilifu.');
    }

    // Add member to group
    public function addMember(Request $request, SmallGroup $smallGroup)
    {
        $validated = $request->validate([
            'member_id' => 'required|exists:members,id',
            'role' => 'nullable|in:member,co-leader',
        ]);

        if ($smallGroup->isFull()) {
            return back()->with('error', 'This group is full.');
        }

        $smallGroup->members()->attach($validated['member_id'], [
            'role' => $validated['role'] ?? 'member',
            'joined_at' => now(),
        ]);

        return back()->with('success', 'Member added to group.');
    }

    // Remove member from group
    public function removeMember(SmallGroup $smallGroup, Member $member)
    {
        $smallGroup->members()->detach($member->id);
        return back()->with('success', 'Member removed from group.');
    }

    // Store meeting / Ratiba ya Ibada ya Kanda
    public function storeMeeting(Request $request, SmallGroup $smallGroup)
    {
        $validated = $request->validate([
            'meeting_date' => 'required',
            'location' => 'nullable|string|max:255',
            'topic' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'attendees_count' => 'nullable|integer|min:0',
        ]);

        $meeting = $smallGroup->meetings()->create([
            'meeting_date' => $validated['meeting_date'],
            'location' => !empty($validated['location']) ? $validated['location'] : $smallGroup->location,
            'topic' => $validated['topic'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'attendees_count' => $validated['attendees_count'] ?? 0,
            'created_by' => Auth::id(),
        ]);

        return back()->with('success', 'Ratiba ya ibada ya kanda imehifadhiwa kikamilifu.');
    }

    /**
     * Tuma SMS za Mwaliko / Ukumbusho wa Ibada ya Kanda kwa Wanakanda wote
     */
    public function sendMeetingReminder(SmallGroupMeeting $meeting)
    {
        if (!$this->canManageMeeting($meeting)) {
            return back()->with('error', 'Huna mamlaka ya kutuma mwaliko kwa kanda hii.');
        }

        $group = $meeting->smallGroup;
        $members = $group->members()
            ->where('status', 'active')
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->get();

        if ($members->isEmpty()) {
            return back()->with('warning', 'Hakuna wanakanda wenye namba za simu waliosajiliwa kwenye kanda hii.');
        }

        [$dateStr, $timeStr] = self::formatMeetingDateTime($meeting->meeting_date);
        $location = $meeting->location ?: ($group->location ?: 'Kwenye Kanda / Kanisani');

        $sentCount = 0;
        foreach ($members as $member) {
            $msg = \App\Services\SmsService::buildZoneMeetingInvitationMessage(
                $member->full_name,
                $group->name,
                $dateStr,
                $timeStr,
                $location,
                $meeting->topic
            );

            try {
                $sent = \App\Services\SmsService::send($member->phone, $msg);
                if ($sent) {
                    $sentCount++;
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Zone meeting reminder SMS failed for member {$member->id}: " . $e->getMessage());
            }
        }

        $meeting->update(['reminder_sent_at' => now()]);

        return back()->with('success', "SMS za mwaliko wa ibada zimetumwa kwa wanakanda {$sentCount} kati ya {$members->count()} kikamilifu!");
    }

    /**
     * Ukurasa wa Kuitisha Majina / Mahudhurio ya Ibada ya Kanda
     */
    public function meetingAttendance(SmallGroupMeeting $meeting)
    {
        if (!$this->canManageMeeting($meeting)) {
            return redirect()->route('small-groups.index')->with('error', 'Huna ruhusa ya kufikia mahudhurio ya kanda hii.');
        }

        $meeting->load(['smallGroup.members', 'attendances.member']);
        $group = $meeting->smallGroup;

        // Key attendances by member_id
        $attendances = $meeting->attendances->keyBy('member_id');

        return view('small-groups.meetings.attendance', compact('meeting', 'group', 'attendances'));
    }

    /**
     * Hifadhi Mahudhurio ya Ibada ya Kanda na Kutuma SMS za Shukrani na Kukumbuka (Miss You)
     */
    public function saveMeetingAttendance(Request $request, SmallGroupMeeting $meeting)
    {
        if (!$this->canManageMeeting($meeting)) {
            return back()->with('error', 'Huna mamlaka ya kubadilisha mahudhurio ya kanda hii.');
        }

        $meeting->load('smallGroup.members');
        $group = $meeting->smallGroup;
        $groupMembers = $group->members;

        // Input attendance: [member_id => 'present'|'absent']
        $submittedAttendance = $request->input('attendance', []);

        $dateStr = \Carbon\Carbon::parse($meeting->meeting_date)->format('d/m/Y');

        $presentCount = 0;
        $absentCount = 0;
        $presentSmsCount = 0;
        $absentSmsCount = 0;

        foreach ($groupMembers as $member) {
            $status = isset($submittedAttendance[$member->id]) && $submittedAttendance[$member->id] === 'present'
                ? 'present'
                : 'absent';

            if ($status === 'present') {
                $presentCount++;
            } else {
                $absentCount++;
            }

            // Find or create attendance record
            $attRecord = \App\Models\SmallGroupMeetingAttendance::firstOrNew([
                'small_group_meeting_id' => $meeting->id,
                'member_id' => $member->id,
            ]);
            $attRecord->status = $status;

            // Kiotomatiki: Aliyepo anatumiwa Shukrani, asiyekuwepo anatumiwa salamu za Kumiss
            if (!empty($member->phone)) {
                if ($status === 'present') {
                    $smsContent = \App\Services\SmsService::buildZoneAttendanceThankYouMessage(
                        $member->full_name,
                        $group->name,
                        $dateStr
                    );
                } else {
                    $smsContent = \App\Services\SmsService::buildZoneAbsenceMissYouMessage(
                        $member->full_name,
                        $group->name
                    );
                }

                try {
                    $sent = \App\Services\SmsService::send($member->phone, $smsContent);
                    if ($sent) {
                        $attRecord->sms_status = 'sent';
                        $attRecord->sms_sent_at = now();

                        if ($status === 'present') {
                            $presentSmsCount++;
                        } else {
                            $absentSmsCount++;
                        }
                    } else {
                        $attRecord->sms_status = 'failed';
                    }
                } catch (\Exception $e) {
                    $attRecord->sms_status = 'failed';
                    \Illuminate\Support\Facades\Log::error("Meeting attendance SMS failed: " . $e->getMessage());
                }
            }

            $attRecord->save();
        }

        // Update overall meeting attendees count
        $meeting->update(['attendees_count' => $presentCount]);

        $feedback = "Mahudhurio yamehifadhiwa kikamilifu! (Waliohudhuria: {$presentCount}, Wasiofika: {$absentCount}). SMS zimetumwa: Shukrani ({$presentSmsCount}), Salamu za kukumbukwa ({$absentSmsCount}).";

        return redirect()->route('small-groups.meetings.attendance', $meeting)->with('success', $feedback);
    }

    /**
     * Check if user can manage meeting
     */
    protected function canManageMeeting(SmallGroupMeeting $meeting): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        if ($user->hasAnyRole(['super_admin', 'admin', 'pastor', 'Super Admin', 'Admin', 'Pastor']) || $user->can('manage-small-groups')) {
            return true;
        }
        if ($user->member) {
            $group = $meeting->smallGroup;
            if ($group && $group->leader_id === $user->member->id) {
                return true;
            }
            if ($group && $group->members()->where('member_id', $user->member->id)->wherePivot('role', 'co-leader')->exists()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Helper to format meeting date and time in Swahili
     */
    public static function formatMeetingDateTime($dateTime)
    {
        $carbon = \Carbon\Carbon::parse($dateTime);
        $days = [
            'Sunday' => 'Jumapili',
            'Monday' => 'Jumatatu',
            'Tuesday' => 'Jumanne',
            'Wednesday' => 'Jumatano',
            'Thursday' => 'Alhamisi',
            'Friday' => 'Ijumaa',
            'Saturday' => 'Jumamosi',
        ];
        $dayName = $days[$carbon->format('l')] ?? $carbon->format('l');

        if ($carbon->isToday()) {
            $prefix = "Leo {$dayName}";
        } elseif ($carbon->isTomorrow()) {
            $prefix = "Kesho {$dayName}";
        } else {
            $prefix = "{$dayName}";
        }

        $dateStr = "{$prefix}, " . $carbon->format('d/m/Y');
        
        $hour = (int)$carbon->format('H');
        if ($hour >= 4 && $hour < 12) {
            $period = "Asubuhi";
        } elseif ($hour >= 12 && $hour < 16) {
            $period = "Mchana";
        } elseif ($hour >= 16 && $hour < 19) {
            $period = "Jioni";
        } else {
            $period = "Usiku";
        }

        $timeStr = "Saa " . $carbon->format('H:i') . " ({$period})";

        return [$dateStr, $timeStr];
    }


    // My group (member view)
    public function myGroup()
    {
        $user = Auth::user();
        if (!$user->member) {
            return redirect()->route('profile.index')->with('warning', 'Please create a member profile first.');
        }

        $group = $user->member->smallGroups()->with(['leader', 'members', 'meetings' => function($query) {
            $query->latest()->take(5);
        }])->first();

        // Fetch active offerings and calculate debts
        $myDebts = collect();
        if ($group) {
            $offerings = $group->offerings()->where('is_active', true)->get();
            foreach ($offerings as $offering) {
                $balance = $offering->getMemberBalance($user->member->id);
                if ($balance > 0) {
                    $offering->my_balance = $balance;
                    $myDebts->push($offering);
                }
            }
        }

        return view('small-groups.my-group', compact('group', 'myDebts'));
    }

    // Kanda Attendance View
    public function groupAttendance(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $isAdminOrPastor = $user->hasAnyRole(['super_admin', 'admin', 'pastor', 'Super Admin', 'Admin', 'Pastor']) || $user->can('manage-small-groups');

        $groups = collect();
        if ($isAdminOrPastor) {
            $groups = SmallGroup::with(['leader', 'members'])->orderBy('name')->get();
        } elseif ($user->member) {
            // Groups where user is leader or co-leader
            $leadGroups = SmallGroup::where('leader_id', $user->member->id)->with(['leader', 'members'])->get();
            $coLeadGroups = $user->member->smallGroups()->wherePivot('role', 'co-leader')->with(['leader', 'members'])->get();
            $groups = $leadGroups->merge($coLeadGroups)->unique('id');
            
            // If still empty, check if user belongs to any group
            if ($groups->isEmpty()) {
                $groups = $user->member->smallGroups()->with(['leader', 'members'])->get();
            }
        }

        if ($groups->isEmpty()) {
            return redirect()->route('small-groups.index')->with('warning', 'Hakuna Zone au Kanda yoyote iliyopatikana kwa ajili yako.');
        }

        $selectedGroupId = $request->get('group_id');
        $group = null;
        if ($selectedGroupId) {
            $group = $groups->firstWhere('id', $selectedGroupId);
        }
        if (!$group) {
            $group = $groups->first();
        }

        $group->load(['leader', 'members']);

        // Load meetings for this group
        $meetings = $group->meetings()
            ->withCount('attendances')
            ->orderBy('meeting_date', 'desc')
            ->paginate(15);

        // Find upcoming or today's meeting
        $upcomingMeeting = $group->meetings()
            ->whereDate('meeting_date', '>=', now()->toDateString())
            ->orderBy('meeting_date', 'asc')
            ->first();

        return view('small-groups.attendance', compact('group', 'groups', 'meetings', 'upcomingMeeting', 'isAdminOrPastor'));
    }

    // Mark Kanda Attendance
    public function markGroupAttendance(Request $request)
    {
        $validated = $request->validate([
            'event_id' => 'required|exists:events,id',
            'member_id' => 'required|exists:members,id',
            'status' => 'required|in:present,absent,late',
        ]);

        $user = Auth::user();
        if (!$user->member) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Verify the user is the leader of the group this member belongs to
        $group = SmallGroup::where('leader_id', $user->member->id)->first();
        if (!$group || !$group->members()->where('member_id', $validated['member_id'])->exists()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized or member not in your group.'], 403);
        }

        \App\Models\Attendance::updateOrCreate(
            [
                'event_id' => $validated['event_id'],
                'member_id' => $validated['member_id'],
            ],
            [
                'status' => $validated['status'],
                'scanned_by' => $user->id,
                'scanned_at' => now(),
            ]
        );

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Mahudhurio yamewekwa kikamilifu.',
                'member_id' => $validated['member_id'],
                'status' => $validated['status']
            ]);
        }

        return back()->with('success', 'Mahudhurio yamewekwa kikamilifu.');
    }

    // Mark Kanda Attendance (Bulk)
    public function bulkMarkGroupAttendance(Request $request)
    {
        $validated = $request->validate([
            'event_id' => 'required|exists:events,id',
            'present' => 'array',
            'present.*' => 'exists:members,id',
            'absent' => 'array',
            'absent.*' => 'exists:members,id',
        ]);

        $user = Auth::user();
        if (!$user->member) {
            file_put_contents(storage_path('logs/attendance_debug.log'), "[" . date('Y-m-d H:i:s') . "] User has no member profile.\n", FILE_APPEND);
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Verify the user is the leader of the group
        $group = SmallGroup::where('leader_id', $user->member->id)->first();
        if (!$group) {
            file_put_contents(storage_path('logs/attendance_debug.log'), "[" . date('Y-m-d H:i:s') . "] User (Member ID: {$user->member->id}) is not a leader of any group.\n", FILE_APPEND);
            return response()->json(['success' => false, 'message' => 'Unauthorized. Not a leader.'], 403);
        }

        // Get group member IDs
        $groupMemberIds = $group->members->pluck('id')->map(fn($id) => (int)$id)->toArray();

        $present = array_map('intval', $validated['present'] ?? []);
        $absent = array_map('intval', $validated['absent'] ?? []);

        file_put_contents(storage_path('logs/attendance_debug.log'), "[" . date('Y-m-d H:i:s') . "] Group: {$group->name} (ID: {$group->id})\n", FILE_APPEND);
        file_put_contents(storage_path('logs/attendance_debug.log'), "Group Member IDs: " . json_encode($groupMemberIds) . "\n", FILE_APPEND);
        file_put_contents(storage_path('logs/attendance_debug.log'), "Present IDs received: " . json_encode($present) . "\n", FILE_APPEND);
        file_put_contents(storage_path('logs/attendance_debug.log'), "Absent IDs received: " . json_encode($absent) . "\n", FILE_APPEND);

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $markedPresentCount = 0;
            $markedAbsentCount = 0;

            foreach ($present as $memberId) {
                if (in_array($memberId, $groupMemberIds, true)) {
                    \App\Models\Attendance::updateOrCreate(
                        [
                            'event_id' => $validated['event_id'],
                            'member_id' => $memberId,
                        ],
                        [
                            'status' => 'present',
                            'scanned_by' => $user->id,
                            'scanned_at' => now(),
                        ]
                    );
                    $markedPresentCount++;
                } else {
                    file_put_contents(storage_path('logs/attendance_debug.log'), "Member ID {$memberId} not in group member IDs!\n", FILE_APPEND);
                }
            }

            foreach ($absent as $memberId) {
                if (in_array($memberId, $groupMemberIds, true)) {
                    \App\Models\Attendance::updateOrCreate(
                        [
                            'event_id' => $validated['event_id'],
                            'member_id' => $memberId,
                        ],
                        [
                            'status' => 'absent',
                            'scanned_by' => $user->id,
                            'scanned_at' => now(),
                        ]
                    );
                    $markedAbsentCount++;
                } else {
                    file_put_contents(storage_path('logs/attendance_debug.log'), "Member ID {$memberId} not in group member IDs!\n", FILE_APPEND);
                }
            }

            \Illuminate\Support\Facades\DB::commit();
            file_put_contents(storage_path('logs/attendance_debug.log'), "Successfully saved. Marked Present: {$markedPresentCount}, Marked Absent: {$markedAbsentCount}\n\n", FILE_APPEND);

            // Calculate new counts for this specific group
            $attendances = \App\Models\Attendance::where('event_id', $validated['event_id'])
                ->whereIn('member_id', $groupMemberIds)
                ->get();
                
            $counts = [
                'present' => $attendances->where('status', 'present')->count(),
                'absent' => $attendances->where('status', 'absent')->count(),
            ];
            $counts['not_marked'] = count($groupMemberIds) - $attendances->count();

            return response()->json([
                'success' => true,
                'message' => 'Mahudhurio yamehifadhiwa kikamilifu!',
                'counts' => $counts
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            file_put_contents(storage_path('logs/attendance_debug.log'), "Error: " . $e->getMessage() . "\n\n", FILE_APPEND);
            return response()->json([
                'success' => false,
                'message' => 'Kuna tatizo: ' . $e->getMessage()
            ], 500);
        }
    }
}
