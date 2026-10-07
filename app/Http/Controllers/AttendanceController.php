<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Event;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $isLeader = $user->hasAnyRole(['super_admin', 'admin', 'pastor', 'department_leader', 'secretary', 'deacon', 'shemasi']) 
            || \App\Models\SmallGroup::where('leader_id', $user->id)->exists();
        
        // If regular member without leadership privileges, show personal attendance
        if (!$isLeader) {
            $member = $user->member;
            
            if (!$member) {
                $attendances = collect();
                return view('attendance.my-attendance', [
                    'attendances' => $attendances,
                    'member' => (object) ['full_name' => $user->name]
                ]);
            }
            
            $attendances = Attendance::where('member_id', $member->id)
                ->with('event')
                ->orderBy('created_at', 'desc')
                ->get();
            
            return view('attendance.my-attendance', compact('attendances', 'member'));
        }

        // =========================================================================
        // EXECUTIVE ATTENDANCE DASHBOARD FOR LEADERS
        // =========================================================================
        
        // 1. Week Range Calculation
        $weekOffset = (int) $request->get('week_offset', 0);
        if ($request->filled('week_start')) {
            $selectedWeekStart = \Carbon\Carbon::parse($request->week_start)->startOfWeek(\Carbon\Carbon::MONDAY);
        } else {
            $selectedWeekStart = now()->addWeeks($weekOffset)->startOfWeek(\Carbon\Carbon::MONDAY);
        }
        $selectedWeekEnd = (clone $selectedWeekStart)->endOfWeek(\Carbon\Carbon::SUNDAY);

        // 2. Active Church Members
        $totalMembers = Member::where('status', 'active')->count();

        // 3. Recent Events for switcher dropdown (last 60 days or 30 events)
        $allRecentEvents = Event::orderBy('date', 'desc')->take(30)->get();

        // 4. Events in the selected week
        $weekEvents = Event::whereBetween('date', [$selectedWeekStart->toDateString(), $selectedWeekEnd->toDateString()])
            ->with(['attendances.member'])
            ->orderBy('date', 'desc')
            ->get();

        // 5. Determine Selected Event for deep-dive summary
        $selectedEvent = null;
        if ($request->filled('event_id')) {
            $selectedEvent = Event::with(['attendances.member.smallGroups'])->find($request->event_id);
        }

        if (!$selectedEvent && $weekEvents->isNotEmpty()) {
            $selectedEvent = $weekEvents->first();
        }

        if (!$selectedEvent) {
            // Fallback to the latest event that has attendances or latest event overall
            $selectedEvent = Event::whereDate('date', '<=', now())
                ->has('attendances')
                ->with(['attendances.member.smallGroups'])
                ->orderBy('date', 'desc')
                ->first();

            if (!$selectedEvent) {
                $selectedEvent = Event::with(['attendances.member.smallGroups'])->orderBy('date', 'desc')->first();
            }
        }

        // 6. Calculate Specific Event Summary
        $selectedEventStats = [
            'present' => 0,
            'late' => 0,
            'absent' => 0,
            'total_attended' => 0,
            'rate' => 0,
            'male' => 0,
            'female' => 0,
            'visitors' => 0,
            'present_attendances' => collect(),
            'absent_members' => collect(),
        ];

        if ($selectedEvent) {
            $eventAttendances = $selectedEvent->attendances;
            $present = $eventAttendances->where('status', 'present');
            $late = $eventAttendances->where('status', 'late');
            $absent = $eventAttendances->where('status', 'absent');

            $totalAttended = $present->count() + $late->count();
            $rate = $totalMembers > 0 ? round(($totalAttended / $totalMembers) * 100, 1) : 0;

            // Demographics of attendees
            $male = $eventAttendances->whereIn('status', ['present', 'late'])->filter(function ($att) {
                $gender = strtolower($att->member->gender ?? '');
                return in_array($gender, ['male', 'm', 'me', 'mwanaume']);
            })->count();

            $female = $eventAttendances->whereIn('status', ['present', 'late'])->filter(function ($att) {
                $gender = strtolower($att->member->gender ?? '');
                return in_array($gender, ['female', 'f', 'ke', 'mwanamke']);
            })->count();

            // Visitors on the event date
            $visitorsCount = \App\Models\Visitor::whereDate('visit_date', $selectedEvent->date)->count();

            // Absent active members (who did not attend this service)
            $attendedMemberIds = $eventAttendances->whereIn('status', ['present', 'late'])->pluck('member_id')->toArray();
            $absentMembers = Member::where('status', 'active')
                ->whereNotIn('id', $attendedMemberIds)
                ->with('smallGroups')
                ->orderBy('first_name')
                ->take(50)
                ->get();

            $selectedEventStats = [
                'present' => $present->count(),
                'late' => $late->count(),
                'absent' => $absent->count(),
                'total_attended' => $totalAttended,
                'rate' => $rate,
                'male' => $male,
                'female' => $female,
                'visitors' => $visitorsCount,
                'present_attendances' => $eventAttendances->whereIn('status', ['present', 'late']),
                'absent_members' => $absentMembers,
            ];
        }

        // 7. Calculate Week-Level Summary
        $weekTotalAttended = 0;
        $weekMemberIds = [];
        foreach ($weekEvents as $we) {
            $attended = $we->attendances->whereIn('status', ['present', 'late']);
            $weekTotalAttended += $attended->count();
            foreach ($attended as $att) {
                $weekMemberIds[$att->member_id] = true;
            }
        }
        $weekUniqueMembersCount = count($weekMemberIds);
        $weekEventsCount = $weekEvents->count();
        $weekAvg = $weekEventsCount > 0 ? round($weekTotalAttended / $weekEventsCount) : 0;
        $weekRate = $totalMembers > 0 ? round(($weekUniqueMembersCount / $totalMembers) * 100, 1) : 0;

        $weekStats = [
            'total_attended' => $weekTotalAttended,
            'unique_members' => $weekUniqueMembersCount,
            'events_count' => $weekEventsCount,
            'avg_attendance' => $weekAvg,
            'rate' => $weekRate,
        ];

        // 8. Zone / Small Group Weekly Meeting Attendance Summary
        $smallGroups = \App\Models\SmallGroup::with(['leader', 'members'])->get();
        $zoneMeetingStats = $smallGroups->map(function ($group) use ($selectedWeekStart, $selectedWeekEnd) {
            $meeting = \App\Models\SmallGroupMeeting::where('small_group_id', $group->id)
                ->whereBetween('meeting_date', [
                    $selectedWeekStart->startOfDay(),
                    $selectedWeekEnd->endOfDay()
                ])
                ->with('attendances')
                ->latest('meeting_date')
                ->first();

            $groupMembersCount = $group->members->count();
            $attendedCount = 0;
            $status = 'pending';

            if ($meeting) {
                $attendedCount = $meeting->attendees_count ?: $meeting->attendances->where('status', 'present')->count();
                $status = 'recorded';
            }

            $rate = $groupMembersCount > 0 ? round(($attendedCount / $groupMembersCount) * 100) : 0;

            return [
                'group' => $group,
                'leader_name' => $group->leader->full_name ?? ($group->leader->name ?? 'Haijajazwa'),
                'total_members' => $groupMembersCount,
                'attended' => $attendedCount,
                'rate' => $rate,
                'status' => $status,
                'meeting' => $meeting,
            ];
        });

        // 9. Trend Chart Data (Last 8 past events)
        $trendEvents = Event::where('date', '<=', now())
            ->has('attendances')
            ->with('attendances')
            ->orderBy('date', 'desc')
            ->take(8)
            ->get()
            ->reverse();

        if ($trendEvents->isEmpty()) {
            $trendEvents = Event::with('attendances')->orderBy('date', 'desc')->take(8)->get()->reverse();
        }

        $trendLabels = $trendEvents->map(function ($e) {
            return $e->date->format('d/m') . ' - ' . \Illuminate\Support\Str::limit($e->name, 10);
        })->values()->toArray();

        $trendData = $trendEvents->map(function ($e) {
            return $e->attendances->whereIn('status', ['present', 'late'])->count();
        })->values()->toArray();

        // 10. Pass all variables to view
        return view('attendance.index', compact(
            'totalMembers',
            'weekOffset',
            'selectedWeekStart',
            'selectedWeekEnd',
            'weekEvents',
            'allRecentEvents',
            'selectedEvent',
            'selectedEventStats',
            'weekStats',
            'zoneMeetingStats',
            'trendLabels',
            'trendData'
        ));
    }

    /**
     * Entry point for manual attendance recording from sidebar or dashboard
     */
    public function recordManual(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasAnyRole(['super_admin', 'admin', 'pastor', 'department_leader']) && !\App\Models\SmallGroup::where('leader_id', $user->id)->exists()) {
            return redirect()->route('attendance.index')->with('error', 'Huna ruhusa ya kurekodi mahudhurio.');
        }

        $allEvents = Event::orderBy('date', 'desc')->take(30)->get();

        $event = null;
        if ($request->filled('event_id')) {
            $event = Event::find($request->event_id);
        }

        if (!$event) {
            // Check today's event
            $event = Event::whereDate('date', today())->first();
        }

        if (!$event) {
            // Find closest event
            $event = Event::whereDate('date', '>=', today())->orderBy('date', 'asc')->first() 
                ?? Event::orderBy('date', 'desc')->first();
        }

        if (!$event) {
            return redirect()->route('events.create')->with('info', 'Tafadhali unda ibada au tukio kwanza ili uweze kurekodi mahudhurio.');
        }

        $attendances = Attendance::where('event_id', $event->id)
            ->with('member')
            ->get()
            ->keyBy('member_id');

        $members = Member::where('status', 'active')
            ->with('smallGroups')
            ->orderBy('full_name')
            ->get();

        $smallGroups = \App\Models\SmallGroup::all();

        return view('attendance.show', compact('event', 'allEvents', 'attendances', 'members', 'smallGroups'));
    }

    public function show(Event $event)
    {
        if (!auth()->user()->hasAnyRole(['super_admin', 'admin', 'pastor', 'department_leader']) && !\App\Models\SmallGroup::where('leader_id', auth()->id())->exists()) {
            return redirect()->route('attendance.index')->with('error', 'Huna ruhusa ya kufikia ukurasa huu.');
        }
        
        $allEvents = Event::orderBy('date', 'desc')->take(30)->get();

        $attendances = Attendance::where('event_id', $event->id)
            ->with('member')
            ->get()
            ->keyBy('member_id');

        $members = Member::where('status', 'active')
            ->with('smallGroups')
            ->orderBy('full_name')
            ->get();

        $smallGroups = \App\Models\SmallGroup::all();

        return view('attendance.show', compact('event', 'allEvents', 'attendances', 'members', 'smallGroups'));
    }

    public function markAttendance(Request $request, Event $event)
    {
        $validated = $request->validate([
            'member_id' => 'required|exists:members,id',
            'status' => 'required|in:present,absent,late',
        ]);

        $attendance = Attendance::updateOrCreate(
            [
                'event_id' => $event->id,
                'member_id' => $validated['member_id'],
            ],
            [
                'status' => $validated['status'],
                'scanned_by' => auth()->id(),
                'scanned_at' => now(),
            ]
        );

        // Return JSON response for AJAX requests
        if ($request->expectsJson() || $request->ajax()) {
            // Get updated counts
            $counts = [
                'present' => Attendance::where('event_id', $event->id)->where('status', 'present')->count(),
                'late' => Attendance::where('event_id', $event->id)->where('status', 'late')->count(),
                'absent' => Attendance::where('event_id', $event->id)->where('status', 'absent')->count(),
                'total' => Member::where('status', 'active')->count(),
            ];
            $counts['not_marked'] = $counts['total'] - ($counts['present'] + $counts['late'] + $counts['absent']);

            return response()->json([
                'success' => true,
                'message' => 'Attendance updated successfully.',
                'member_id' => $validated['member_id'],
                'status' => $validated['status'],
                'counts' => $counts,
            ]);
        }

        return back()->with('status', 'Attendance updated successfully.');
    }

    public function bulkMark(Request $request, Event $event)
    {
        $validated = $request->validate([
            'present' => 'array',
            'present.*' => 'exists:members,id',
            'absent' => 'array',
            'absent.*' => 'exists:members,id',
        ]);

        DB::beginTransaction();
        try {
            // Mark present
            if (isset($validated['present'])) {
                foreach ($validated['present'] as $memberId) {
                    Attendance::updateOrCreate(
                        [
                            'event_id' => $event->id,
                            'member_id' => $memberId,
                        ],
                        [
                            'status' => 'present',
                            'scanned_by' => auth()->id(),
                            'scanned_at' => now(),
                        ]
                    );
                }
            }

            // Mark absent
            if (isset($validated['absent'])) {
                foreach ($validated['absent'] as $memberId) {
                    Attendance::updateOrCreate(
                        [
                            'event_id' => $event->id,
                            'member_id' => $memberId,
                        ],
                        [
                            'status' => 'absent',
                            'scanned_by' => auth()->id(),
                            'scanned_at' => now(),
                        ]
                    );
                }
            }

            DB::commit();
            
            // Return JSON response for AJAX requests
            if ($request->expectsJson() || $request->ajax()) {
                // Get updated counts
                $counts = [
                    'present' => Attendance::where('event_id', $event->id)->where('status', 'present')->count(),
                    'late' => Attendance::where('event_id', $event->id)->where('status', 'late')->count(),
                    'absent' => Attendance::where('event_id', $event->id)->where('status', 'absent')->count(),
                    'total' => Member::where('status', 'active')->count(),
                ];
                $counts['not_marked'] = $counts['total'] - ($counts['present'] + $counts['late'] + $counts['absent']);

                return response()->json([
                    'success' => true,
                    'message' => 'Attendance marked successfully.',
                    'counts' => $counts,
                ]);
            }
            
            return back()->with('status', 'Attendance marked successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error marking attendance: ' . $e->getMessage(),
                ], 500);
            }
            
            return back()->with('error', 'Error marking attendance: ' . $e->getMessage());
        }
    }

    public function scanQr(Request $request, $memberNumber)
    {
        // Allow clearing the selected event from session
        if ($request->input('clear_event')) {
            session()->forget('selected_attendance_event_id');
        }

        // Find active member
        $member = Member::where('member_number', trim($memberNumber))->first();
        if (!$member) {
            return view('attendance.scan-result', [
                'status' => 'error',
                'message' => 'Mwanachama mwenye namba hii hajapatikana kwenye mfumo yetu.',
                'member' => null
            ]);
        }

        // Find today's events
        $todayEvents = Event::whereDate('date', '=', \Carbon\Carbon::today())->get();

        // If no events scheduled today, look at the closest upcoming or latest event
        if ($todayEvents->isEmpty()) {
            $closestEvent = Event::whereDate('date', '>=', \Carbon\Carbon::today())
                ->orderBy('date', 'asc')
                ->orderBy('start_time', 'asc')
                ->first();

            if (!$closestEvent) {
                $closestEvent = Event::orderBy('date', 'desc')
                    ->orderBy('start_time', 'desc')
                    ->first();
            }
            
            $todayEvents = $closestEvent ? collect([$closestEvent]) : collect();
        }

        // Determine which event to check in
        $event = null;
        $eventIdFromRequest = $request->input('event_id');
        $eventIdFromSession = session('selected_attendance_event_id');

        if ($eventIdFromRequest) {
            $event = Event::find($eventIdFromRequest);
            if ($event) {
                session(['selected_attendance_event_id' => $event->id]);
            }
        } elseif ($eventIdFromSession) {
            $event = Event::find($eventIdFromSession);
        }

        // If we still don't have a selected event, check the count of events
        if (!$event) {
            if ($todayEvents->count() === 1) {
                $event = $todayEvents->first();
                session(['selected_attendance_event_id' => $event->id]);
            } elseif ($todayEvents->count() > 1) {
                // Multiple events today! We need the usher to choose first.
                return view('attendance.scan-result', [
                    'status' => 'choose_event',
                    'message' => 'Kuna ibada/matukio zaidi ya moja leo. Tafadhali chagua ibada unayosajili mahudhurio ya ' . $member->full_name . ':',
                    'member' => $member,
                    'events' => $todayEvents
                ]);
            }
        }

        if (!$event) {
            return view('attendance.scan-result', [
                'status' => 'error',
                'message' => 'Hakuna ibada au tukio lolote lililosajiliwa leo kwa ajili ya mahudhurio.',
                'member' => $member
            ]);
        }

        // Check if user is authenticated. If not, show login form on this page
        $user = auth()->user();
        if (!$user) {
            return view('attendance.scan-result', [
                'status' => 'login_required',
                'message' => 'Ufunguo wa usalama unahitajika. Tafadhali ingia kwenye mfumo ili kusajili mahudhurio ya ' . $member->full_name . '.',
                'member' => $member,
                'event' => $event
            ]);
        }

        // Mark attendance
        $attendance = Attendance::where('event_id', $event->id)
            ->where('member_id', $member->id)
            ->first();

        if ($attendance) {
            if ($attendance->status === 'registered') {
                $attendance->update([
                    'status' => 'present',
                    'scanned_by' => auth()->id(),
                    'scanned_at' => now(),
                ]);
                $status = 'success';
                $message = "Mahudhurio yamesajiliwa kwa mwanachama aliyekuwa amejiandikisha.";
            } else {
                $status = 'warning';
                $message = "Mwanachama huyu tayari ameshawekewa mahudhurio ya tukio hili.";
            }
        } else {
            Attendance::create([
                'event_id' => $event->id,
                'member_id' => $member->id,
                'scanned_by' => auth()->id(),
                'scanned_at' => now(),
                'status' => 'present',
            ]);
            $status = 'success';
            $message = "Mahudhurio ya mwanachama yamesajiliwa kikamilifu.";
        }

        return view('attendance.scan-result', [
            'status' => $status,
            'message' => $message,
            'member' => $member,
            'event' => $event,
            'show_change_event' => $todayEvents->count() > 1
        ]);
    }

    public function scanQrLogin(Request $request, $memberNumber)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            
            // Now authenticated, record the attendance
            return $this->scanQr($request, $memberNumber);
        }

        // Authentication failed - Find details to render view again
        $member = Member::where('member_number', trim($memberNumber))->first();
        
        $todayEvents = Event::whereDate('date', '=', \Carbon\Carbon::today())->get();
        if ($todayEvents->isEmpty()) {
            $closestEvent = Event::whereDate('date', '>=', \Carbon\Carbon::today())
                ->orderBy('date', 'asc')
                ->orderBy('start_time', 'asc')
                ->first();
            $todayEvents = $closestEvent ? collect([$closestEvent]) : collect();
        }

        $event = null;
        $eventIdFromSession = session('selected_attendance_event_id');
        if ($eventIdFromSession) {
            $event = Event::find($eventIdFromSession);
        }
        if (!$event) {
            $event = $todayEvents->first();
        }

        return view('attendance.scan-result', [
            'status' => 'login_required',
            'message' => 'Ufunguo wa usalama unahitajika.',
            'member' => $member,
            'event' => $event,
            'login_error' => 'Barua pepe au nenosiri si sahihi. Jaribu tena.'
        ]);
    }

    public function myAttendance()
    {
        $user = auth()->user();
        $member = $user->member;
        
        if (!$member) {
            $attendances = collect();
            return view('attendance.my-attendance', [
                'attendances' => $attendances,
                'member' => (object) ['full_name' => $user->name]
            ]);
        }
        
        $attendances = Attendance::where('member_id', $member->id)
            ->with('event')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('attendance.my-attendance', compact('attendances', 'member'));
    }
}
