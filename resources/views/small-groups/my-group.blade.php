@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="row mb-4 mt-4">
        <div class="col-12">
            <h1 class="m-0 text-dark">{{ $group->name ?? 'My Small Group' }}</h1>
        </div>
    </div>

    @if($group)
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Group Information</h3>
                </div>
                <div class="card-body">
                    <p>{{ $group->description }}</p>
                    <p><strong>Leader:</strong> {{ $group->leader->full_name }}</p>
                    @if($group->meeting_day)
                    <p><strong>Meeting:</strong> {{ $group->meeting_day }} @ {{ $group->meeting_time }}</p>
                    @endif
                    @if($group->location)
                    <p><strong>Location:</strong> {{ $group->location }}</p>
                    @endif
                </div>
            </div>

            <!-- Members List -->
            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title">Members ({{ $group->members->count() }})</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table">
                        <tbody>
                            @foreach($group->members as $member)
                            <tr>
                                <td>{{ $member->full_name }}</td>
                                <td>
                                    @if($member->pivot->role === 'co-leader')
                                    <span class="badge badge-info">Co-Leader</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            
            <!-- My Payments / Debts -->
            @if(isset($myDebts) && $myDebts->count() > 0)
            <div class="card card-danger card-outline mb-3">
                <div class="card-header">
                    <h5 class="card-title text-danger">
                        <i class="fas fa-exclamation-circle"></i> Pending Payments
                    </h5>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @foreach($myDebts as $debt)
                        <li class="list-group-item">
                            <strong>{{ $debt->name }}</strong>
                            <span class="float-right text-danger font-weight-bold">
                                TSh {{ number_format($debt->my_balance) }}
                            </span>
                            <br>
                            <small class="text-muted">Deadline: {{ $debt->deadline ? $debt->deadline->format('M d') : 'None' }}</small>
                            <a href="{{ route('give.form') }}?amount={{ $debt->my_balance }}&category=small_group&ref={{ $debt->id }}" class="btn btn-xs btn-success float-right mt-1">
                                Pay Now
                            </a>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            @endif

            <!-- Leader Tools (Visible only to Leader/Co-Leader) -->
            @php
                $isLeader = $group->leader_id === Auth::user()->member->id;
                $isCoLeader = $group->members->contains(function($member) {
                    return $member->id === Auth::user()->member->id && $member->pivot->role === 'co-leader';
                });
            @endphp

            @if($isLeader || $isCoLeader)
            <div class="card card-outline card-info shadow-sm mb-3">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0 font-weight-bold">
                        <i class="fas fa-crown"></i> Uongozi wa Kanda (Leader Tools)
                    </h5>
                </div>
                <div class="card-body p-3">
                    <button type="button" class="btn btn-success btn-block font-weight-bold" data-toggle="modal" data-target="#scheduleMeetingModal">
                        <i class="far fa-calendar-plus"></i> Rekodi Ratiba ya Ibada
                    </button>
                </div>
            </div>
            @endif

            <!-- Recent Meetings -->
            <div class="card shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h3 class="card-title font-weight-bold mb-0">
                        <i class="fas fa-calendar-alt text-primary mr-1"></i> Ibada za Kanda (Meetings)
                    </h3>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @forelse($group->meetings->sortByDesc('meeting_date')->take(5) as $meeting)
                        <li class="list-group-item p-3">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <div>
                                    <strong class="text-dark">
                                        <i class="far fa-calendar-check text-success"></i> 
                                        {{ \Carbon\Carbon::parse($meeting->meeting_date)->format('d M Y, H:i') }}
                                    </strong>
                                    @if(\Carbon\Carbon::parse($meeting->meeting_date)->isFuture())
                                        <span class="badge badge-warning ml-1">Ijayo</span>
                                    @else
                                        <span class="badge badge-light border ml-1">Ilikwisha</span>
                                    @endif
                                </div>
                                <span class="badge badge-info">
                                    {{ $meeting->attendees_count }} Walikuwepo
                                </span>
                            </div>

                            @if($meeting->topic)
                            <div class="small font-weight-bold text-dark mt-1">
                                <i class="fas fa-book-open text-muted mr-1"></i> {{ $meeting->topic }}
                            </div>
                            @endif

                            <div class="small text-muted mb-2">
                                <i class="fas fa-map-marker-alt text-danger mr-1"></i> {{ $meeting->location ?: ($group->location ?: 'Kanisani') }}
                            </div>

                            @if($isLeader || $isCoLeader)
                            <div class="d-flex flex-wrap gap-2 mt-2 pt-2 border-top">
                                <a href="{{ route('small-groups.meetings.attendance', $meeting) }}" class="btn btn-xs btn-primary mr-1 mb-1 font-weight-bold">
                                    <i class="fas fa-clipboard-check"></i> Itisha Majina / Mahudhurio
                                </a>

                                <form action="{{ route('small-groups.meetings.send-reminder', $meeting) }}" method="POST" class="d-inline mb-1" onsubmit="return confirm('Tuma SMS za mwaliko wa ibada hii kwa wanakanda wote?')">
                                    @csrf
                                    <button type="submit" class="btn btn-xs btn-outline-warning">
                                        <i class="fas fa-bell"></i> 
                                        {{ $meeting->reminder_sent_at ? 'Tuma Mwaliko Tena' : 'Kumbusha (SMS)' }}
                                    </button>
                                </form>
                            </div>

                            @if($meeting->reminder_sent_at)
                            <div class="small text-success mt-1" style="font-size: 0.75rem;">
                                <i class="fas fa-check"></i> Mwaliko ulitumwa: {{ $meeting->reminder_sent_at->format('d/m H:i') }}
                            </div>
                            @endif
                            @endif
                        </li>
                        @empty
                        <li class="list-group-item text-center p-3 text-muted">
                            Hakuna ibada zilizorekodiwa bado.
                        </li>
                        @endforelse
                    </ul>
                </div>
            </div>

            <!-- Modal for scheduling meeting -->
            @if($isLeader || $isCoLeader)
            <div class="modal fade" id="scheduleMeetingModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title font-weight-bold">
                                <i class="far fa-calendar-plus mr-1"></i> Rekodi Ratiba ya Ibada ya Kanda
                            </h5>
                            <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                        </div>
                        <form action="{{ route('small-groups.store-meeting', $group) }}" method="POST">
                            @csrf
                            <div class="modal-body">
                                <div class="form-group">
                                    <label class="font-weight-semibold">Tarehe na Saa ya Ibada *</label>
                                    <input type="datetime-local" name="meeting_date" class="form-control" value="{{ now()->addDay()->format('Y-m-d\T17:00') }}" required>
                                </div>
                                <div class="form-group">
                                    <label class="font-weight-semibold">Mahali / Mwenyeji wa Ibada</label>
                                    <input type="text" name="location" class="form-control" placeholder="Mf: Kwa Mzee Petro au Kanisani" value="{{ $group->location }}">
                                </div>
                                <div class="form-group">
                                    <label class="font-weight-semibold">Mada ya Ibada (Topic)</label>
                                    <input type="text" name="topic" class="form-control" placeholder="Mf: Ushirika na Upendo">
                                </div>
                                <div class="form-group">
                                    <label class="font-weight-semibold">Maelezo ya Ziada (Notes)</label>
                                    <textarea name="notes" class="form-control" rows="2" placeholder="Ujumbe mfupi..."></textarea>
                                </div>
                            </div>
                            <div class="modal-footer bg-light">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Funga</button>
                                <button type="submit" class="btn btn-success font-weight-bold">
                                    <i class="fas fa-save mr-1"></i> Hifadhi Ratiba
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
    @else
    <div class="row">
        <div class="col-12">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> You are not currently part of any small group. Contact your pastor to join one!
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
