@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="row mb-4 mt-4">
        <div class="col-12">
            <h1 class="m-0 text-dark">{{ $smallGroup->name }}</h1>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm">
            <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm">
            <i class="fas fa-exclamation-triangle mr-1"></i> {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    <div class="row">
        <!-- Group Info -->
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white">
                    <h3 class="card-title font-weight-bold text-dark">{{ __('Group Information') }}</h3>
                    <div class="card-tools">
                        @if(Auth::user()->hasAnyRole(['super_admin', 'admin', 'pastor']))
                            <a href="{{ route('small-groups.edit', $smallGroup) }}" class="btn btn-sm btn-info shadow-sm">
                                <i class="fas fa-edit mr-1"></i> {{ __('Edit') }}
                            </a>
                            <form action="{{ route('small-groups.destroy', $smallGroup) }}" method="POST" class="d-inline ml-1" onsubmit="return confirm('Je, una uhakika unataka kufuta zone ya {{ addslashes($smallGroup->name) }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger shadow-sm">
                                    <i class="fas fa-trash-alt mr-1"></i> {{ __('Delete') }}
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <p>{{ $smallGroup->description }}</p>
                    <p><strong>{{ __('Leader') }}:</strong> {{ $smallGroup->leader->full_name }}</p>
                    @if($smallGroup->meeting_day)
                    <p><strong>{{ __('Meeting') }}:</strong> {{ __($smallGroup->meeting_day) }} @ {{ $smallGroup->meeting_time }}</p>
                    @endif
                    @if($smallGroup->location)
                    <p><strong>{{ __('Location') }}:</strong> {{ $smallGroup->location }}</p>
                    @endif
                    <p><strong>{{ __('Capacity') }}:</strong> {{ $smallGroup->members->count() }} / {{ $smallGroup->max_members }}</p>
                </div>
            </div>

            <!-- Members -->
            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title">{{ __('Members') }} ({{ $smallGroup->members->count() }})</h3>
                    <div class="card-tools">
                        @if(!$smallGroup->isFull())
                        <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addMemberModal">
                            <i class="fas fa-plus"></i> {{ __('Add Member') }}
                        </button>
                        @endif
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('Role') }}</th>
                                <th>{{ __('Joined') }}</th>
                                <th>{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($smallGroup->members as $member)
                            <tr>
                                <td>{{ $member->full_name }}</td>
                                <td>
                                    @if($member->pivot->role === 'co-leader')
                                    <span class="badge badge-info">{{ __('Co-Leader') }}</span>
                                    @else
                                    <span class="badge badge-secondary">{{ __('Member') }}</span>
                                    @endif
                                </td>
                                <td>{{ \Carbon\Carbon::parse($member->pivot->joined_at)->format('M d, Y') }}</td>
                                <td>
                                    <form action="{{ route('small-groups.remove-member', [$smallGroup, $member]) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Remove this member?') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-md-4">
            <!-- Schedule Meeting / Ratiba ya Ibada -->
            <div class="card card-success card-outline shadow-sm">
                <div class="card-header">
                    <h3 class="card-title font-weight-bold">
                        <i class="far fa-calendar-plus text-success mr-1"></i> {{ __('Ratiba ya Ibada ya Kanda') }}
                    </h3>
                </div>
                <form action="{{ route('small-groups.store-meeting', $smallGroup) }}" method="POST">
                    @csrf
                    <div class="card-body">
                        <div class="form-group">
                            <label class="font-weight-semibold">{{ __('Tarehe na Saa ya Ibada') }} *</label>
                            <input type="datetime-local" name="meeting_date" class="form-control" value="{{ now()->addDay()->format('Y-m-d\T17:00') }}" required>
                            <small class="text-muted">Kama ibada ni kesho, chagua tarehe na saa sahihi.</small>
                        </div>
                        <div class="form-group">
                            <label class="font-weight-semibold">{{ __('Mahali / Mwenyeji wa Ibada') }}</label>
                            <input type="text" name="location" class="form-control" placeholder="Mf: Kwa Mzee Petro au Kanisani" value="{{ $smallGroup->location }}">
                        </div>
                        <div class="form-group">
                            <label class="font-weight-semibold">{{ __('Mada ya Ibada (Topic)') }}</label>
                            <input type="text" name="topic" class="form-control" placeholder="Mf: Ushirika na Upendo">
                        </div>
                        <div class="form-group">
                            <label class="font-weight-semibold">{{ __('Maelezo ya Ziada (Notes)') }}</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Ujumbe mfupi au maelekezo..."></textarea>
                        </div>
                    </div>
                    <div class="card-footer bg-light">
                        <button type="submit" class="btn btn-success btn-block font-weight-bold">
                            <i class="fas fa-save mr-1"></i> {{ __('Hifadhi Ratiba ya Ibada') }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Recent Meetings / Ibada za Kanda -->
            <div class="card card-outline card-primary shadow-sm mt-3">
                <div class="card-header">
                    <h3 class="card-title font-weight-bold">
                        <i class="fas fa-calendar-alt text-primary mr-1"></i> {{ __('Ibada za Kanda') }}
                    </h3>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @forelse($smallGroup->meetings->sortByDesc('meeting_date')->take(10) as $meeting)
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
                                <span class="badge badge-info px-2 py-1">
                                    {{ $meeting->attendees_count }} Walikuwepo
                                </span>
                            </div>

                            @if($meeting->topic)
                            <div class="small font-weight-bold text-dark mt-1">
                                <i class="fas fa-book-open text-muted mr-1"></i> {{ $meeting->topic }}
                            </div>
                            @endif

                            <div class="small text-muted mb-2">
                                <i class="fas fa-map-marker-alt text-danger mr-1"></i> {{ $meeting->location ?: ($smallGroup->location ?: 'Kanisani') }}
                            </div>

                            <!-- Buttons: Roll call & Send Reminder -->
                            <div class="d-flex flex-wrap gap-2 mt-2 pt-2 border-top">
                                <a href="{{ route('small-groups.meetings.attendance', $meeting) }}" class="btn btn-xs btn-primary mr-1 mb-1 font-weight-bold">
                                    <i class="fas fa-clipboard-check"></i> Itisha Majina / Mahudhurio
                                </a>

                                <form action="{{ route('small-groups.meetings.send-reminder', $meeting) }}" method="POST" class="d-inline mb-1" onsubmit="return confirm('Tuma SMS za mwaliko wa ibada hii kwa wanakanda wote?')">
                                    @csrf
                                    <button type="submit" class="btn btn-xs btn-outline-warning">
                                        <i class="fas fa-bell"></i> 
                                        {{ $meeting->reminder_sent_at ? 'Tuma Mwaliko Tena' : 'Kumbusha (Tuma SMS)' }}
                                    </button>
                                </form>
                            </div>

                            @if($meeting->reminder_sent_at)
                            <div class="small text-success mt-1" style="font-size: 0.75rem;">
                                <i class="fas fa-check"></i> Mwaliko ulitumwa: {{ $meeting->reminder_sent_at->format('d/m/Y H:i') }}
                            </div>
                            @endif
                        </li>
                        @empty
                        <li class="list-group-item text-center p-4 text-muted">
                            <i class="far fa-calendar-times fa-2x d-block mb-2"></i>
                            {{ __('Hakuna ratiba za ibada zilizorekodiwa bado.') }}
                        </li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Member Modal -->
<div class="modal fade" id="addMemberModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Add Member to Group') }}</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form action="{{ route('small-groups.add-member', $smallGroup) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>{{ __('Select Member') }}</label>
                        <select name="member_id" class="form-control" required>
                            <option value="">{{ __('Choose...') }}</option>
                            @foreach($availableMembers as $member)
                                <option value="{{ $member->id }}">{{ $member->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>{{ __('Role') }}</label>
                        <select name="role" class="form-control">
                            <option value="member">{{ __('Member') }}</option>
                            <option value="co-leader">{{ __('Co-Leader') }}</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Close') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Add Member') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
