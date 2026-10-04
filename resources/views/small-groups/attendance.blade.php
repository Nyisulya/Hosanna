@extends('layouts.admin')

@section('title', __('Mahudhurio ya Zone / Kanda: ') . $group->name)

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-3 mt-3 align-items-center">
        <div class="col-sm-6">
            <h1 class="m-0 text-dark">
                <i class="fas fa-clipboard-check text-primary mr-1"></i> {{ __('Mahudhurio ya Zone / Kanda') }}
            </h1>
            <p class="text-muted mb-0">{{ __('Simamia ratiba za ibada, tuma mialiko kwa SMS na itisha majina ya mahudhurio.') }}</p>
        </div>
        <div class="col-sm-6 text-sm-right mt-3 mt-sm-0">
            @if(!Auth::user()->hasRole('pastor') || Auth::user()->hasRole('super_admin'))
            <button type="button" class="btn btn-success shadow-sm" data-toggle="modal" data-target="#newMeetingModal">
                <i class="fas fa-calendar-plus mr-1"></i> {{ __('Panga Ibada Mpya ya Zone') }}
            </button>
            @endif
            <a href="{{ route('small-groups.show', $group) }}" class="btn btn-outline-primary ml-2">
                <i class="fas fa-eye mr-1"></i> {{ __('Tazama Kanda') }}
            </a>
        </div>
    </div>

    <!-- Zone Selector & Info Card -->
    <div class="card card-outline card-primary shadow-sm mb-4">
        <div class="card-body py-3">
            <div class="row align-items-center">
                <div class="col-md-6 mb-3 mb-md-0">
                    <form action="{{ route('small-groups.attendance') }}" method="GET" class="d-flex align-items-center">
                        <label for="group_id" class="mr-2 mb-0 font-weight-bold text-nowrap">
                            <i class="fas fa-map-marked-alt text-primary"></i> {{ __('Chagua Kanda:') }}
                        </label>
                        <select name="group_id" id="group_id" class="form-control select2" onchange="this.form.submit()">
                            @foreach($groups as $grp)
                                <option value="{{ $grp->id }}" {{ $group->id == $grp->id ? 'selected' : '' }}>
                                    {{ $grp->name }} ({{ $grp->members->count() }} wanakanda)
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div>
                <div class="col-md-6 text-md-right">
                    <span class="badge badge-light border px-3 py-2 mr-2">
                        <i class="fas fa-user-tie text-secondary"></i> Kiongozi: <strong>{{ $group->leader->full_name ?? 'Hajapangiwa' }}</strong>
                    </span>
                    <span class="badge badge-light border px-3 py-2 mr-2">
                        <i class="fas fa-users text-info"></i> Wanakanda: <strong>{{ $group->members->count() }}</strong>
                    </span>
                    <span class="badge badge-light border px-3 py-2">
                        <i class="fas fa-map-marker-alt text-danger"></i> {{ $group->location ?: 'Makao ya Kanda' }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Featured Upcoming / Today's Meeting Card -->
    @if($upcomingMeeting)
    <div class="card bg-gradient-primary text-white shadow-sm mb-4 border-0">
        <div class="card-body p-4">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <div class="d-flex align-items-center mb-2">
                        <span class="badge badge-warning text-dark font-weight-bold px-3 py-1 mr-2">
                            <i class="fas fa-bell"></i> IBADA YA KARIBUNI / IJAYO
                        </span>
                        <span class="text-white-50">
                            {{ \Carbon\Carbon::parse($upcomingMeeting->meeting_date)->diffForHumans() }}
                        </span>
                    </div>
                    <h3 class="font-weight-bold text-white mb-2">
                        <i class="far fa-calendar-alt mr-1"></i> {{ \Carbon\Carbon::parse($upcomingMeeting->meeting_date)->format('l, d/m/Y') }} saa {{ \Carbon\Carbon::parse($upcomingMeeting->meeting_date)->format('H:i') }}
                    </h3>
                    <div class="d-flex flex-wrap text-white-50 gap-3 mb-2">
                        <span class="mr-3">
                            <i class="fas fa-map-marker-alt text-warning"></i> <strong>Mahali:</strong> {{ $upcomingMeeting->location ?: ($group->location ?: 'Kwenye Kanda') }}
                        </span>
                        @if($upcomingMeeting->topic)
                        <span>
                            <i class="fas fa-book-open text-warning"></i> <strong>Mada:</strong> {{ $upcomingMeeting->topic }}
                        </span>
                        @endif
                    </div>
                    <div class="small">
                        @if($upcomingMeeting->reminder_sent_at)
                            <span class="text-warning"><i class="fas fa-check-circle"></i> Mwaliko wa SMS ulitumwa {{ $upcomingMeeting->reminder_sent_at->format('d/m/Y H:i') }}</span>
                        @else
                            <span class="text-white-50"><i class="fas fa-info-circle"></i> Mwaliko wa SMS bado haujatumwa kwa wanakanda.</span>
                        @endif
                    </div>
                </div>
                <div class="col-lg-4 text-lg-right mt-3 mt-lg-0">
                    <div class="d-flex flex-column flex-sm-row justify-content-lg-end gap-2">
                        @if(!Auth::user()->hasRole('pastor') || Auth::user()->hasRole('super_admin'))
                        <form action="{{ route('small-groups.meetings.send-reminder', $upcomingMeeting) }}" method="POST" class="d-inline mr-2 mb-2" onsubmit="return confirm('Tuma SMS za mwaliko wa ibada hii sasa kwa wanakanda wote?')">
                            @csrf
                            <button type="submit" class="btn btn-warning shadow-sm btn-block font-weight-bold">
                                <i class="fas fa-sms mr-1"></i> {{ $upcomingMeeting->reminder_sent_at ? 'Tuma Mwaliko Tena (SMS)' : 'Kumbusha Wanakanda (SMS)' }}
                            </button>
                        </form>
                        <a href="{{ route('small-groups.meetings.attendance', $upcomingMeeting) }}" class="btn btn-light shadow-sm btn-block mb-2 font-weight-bold text-primary">
                            <i class="fas fa-clipboard-check mr-1"></i> Chukua Mahudhurio
                        </a>
                        @else
                        <a href="{{ route('small-groups.meetings.attendance', $upcomingMeeting) }}" class="btn btn-light shadow-sm btn-block mb-2 font-weight-bold text-primary">
                            <i class="fas fa-eye mr-1"></i> Tazama Mahudhurio
                        </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- All Meetings List -->
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap">
            <h3 class="card-title font-weight-bold text-dark mb-0">
                <i class="fas fa-history text-secondary mr-1"></i> {{ __('Ratiba na Mahudhurio ya Ibada za Kanda') }} ({{ $meetings->total() }})
            </h3>
            @if(!Auth::user()->hasRole('pastor') || Auth::user()->hasRole('super_admin'))
            <button type="button" class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#newMeetingModal">
                <i class="fas fa-plus"></i> {{ __('Panga Ibada') }}
            </button>
            @endif
        </div>

        <div class="card-body p-0 table-responsive">
            <table class="table table-hover table-striped align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Tarehe & Siku</th>
                        <th>Saa</th>
                        <th>Mahali / Mwenyeji</th>
                        <th>Mada ya Ibada</th>
                        <th>Waliohudhuria</th>
                        <th>SMS ya Mwaliko</th>
                        <th class="text-right" style="width: 280px;">Hatua</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($meetings as $index => $meeting)
                        @php
                            $meetingCarbon = \Carbon\Carbon::parse($meeting->meeting_date);
                            $isUpcoming = $meetingCarbon->isFuture();
                            $isToday = $meetingCarbon->isToday();
                        @endphp
                        <tr>
                            <td>{{ $meetings->firstItem() + $index }}</td>
                            <td>
                                <strong>{{ $meetingCarbon->format('d/m/Y') }}</strong>
                                <br>
                                <small class="text-muted">{{ $meetingCarbon->format('l') }}</small>
                                @if($isToday)
                                    <span class="badge badge-success ml-1">Leo</span>
                                @elseif($isUpcoming)
                                    <span class="badge badge-info ml-1">Ijayo</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-light border">
                                    <i class="far fa-clock text-secondary"></i> {{ $meetingCarbon->format('H:i') }}
                                </span>
                            </td>
                            <td>
                                <i class="fas fa-map-marker-alt text-danger mr-1"></i>
                                {{ $meeting->location ?: ($group->location ?: 'Makao ya Kanda') }}
                            </td>
                            <td>
                                @if($meeting->topic)
                                    <span class="font-weight-bold">{{ $meeting->topic }}</span>
                                @else
                                    <span class="text-muted font-italic">Ibada ya Kanda ya Kawaida</span>
                                @endif
                            </td>
                            <td>
                                @if($meeting->attendances_count > 0 || $meeting->attendees_count > 0)
                                    <span class="badge badge-success px-2 py-1">
                                        <i class="fas fa-user-check"></i> {{ max($meeting->attendances_count, $meeting->attendees_count) }} / {{ $group->members->count() }}
                                    </span>
                                @else
                                    <span class="badge badge-secondary px-2 py-1">
                                        Haijachukuliwa
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($meeting->reminder_sent_at)
                                    <span class="badge badge-light border text-success">
                                        <i class="fas fa-check"></i> Imetumwa ({{ $meeting->reminder_sent_at->format('d/m H:i') }})
                                    </span>
                                @else
                                    <span class="badge badge-light border text-muted">
                                        Bado
                                    </span>
                                @endif
                            </td>
                            <td class="text-right">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('small-groups.meetings.attendance', $meeting) }}" class="btn {{ (!Auth::user()->hasRole('pastor') || Auth::user()->hasRole('super_admin')) ? 'btn-primary' : 'btn-outline-info' }}" title="{{ (!Auth::user()->hasRole('pastor') || Auth::user()->hasRole('super_admin')) ? 'Chukua Mahudhurio / Itisha Majina' : 'Tazama Mahudhurio' }}">
                                        <i class="fas {{ (!Auth::user()->hasRole('pastor') || Auth::user()->hasRole('super_admin')) ? 'fa-clipboard-check' : 'fa-eye' }}"></i> {{ (!Auth::user()->hasRole('pastor') || Auth::user()->hasRole('super_admin')) ? __('Itisha Majina') : __('Tazama') }}
                                    </a>

                                    @if(!Auth::user()->hasRole('pastor') || Auth::user()->hasRole('super_admin'))
                                    <form action="{{ route('small-groups.meetings.send-reminder', $meeting) }}" method="POST" class="d-inline" onsubmit="return confirm('Je, una uhakika unataka kutuma SMS ya mwaliko/ukumbusho wa ibada hii kwa wanakanda wote?')">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-warning" title="{{ $meeting->reminder_sent_at ? 'Tuma Mwaliko Tena (SMS)' : 'Kumbusha Wanakanda (SMS)' }}">
                                            <i class="fas fa-bell"></i> SMS
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="fas fa-calendar-times fa-3x mb-3 text-secondary d-block"></i>
                                <h5>Bado hakuna ratiba ya ibada iliyorekodiwa kwa kanda hii.</h5>
                                <p class="text-muted">Bonyeza kitufe hapa chini kupanga ibada ya kwanza ili kuweza kutuma mwaliko wa SMS na kuchukua mahudhurio.</p>
                                @if(!Auth::user()->hasRole('pastor') || Auth::user()->hasRole('super_admin'))
                                <button type="button" class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#newMeetingModal">
                                    <i class="fas fa-plus mr-1"></i> Panga Ibada ya Kwanza Sasa
                                </button>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($meetings->hasPages())
        <div class="card-footer bg-white d-flex justify-content-end">
            {{ $meetings->appends(['group_id' => $group->id])->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Modal: Panga Ibada Mpya ya Zone -->
<div class="modal fade" id="newMeetingModal" tabindex="-1" role="dialog" aria-labelledby="newMeetingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form action="{{ route('small-groups.store-meeting', $group) }}" method="POST">
                @csrf
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title font-weight-bold" id="newMeetingModalLabel">
                        <i class="fas fa-calendar-plus mr-1"></i> {{ __('Panga Ibada Mpya ya Kanda: ') . $group->name }}
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="meeting_date">Tarehe na Saa ya Ibada <span class="text-danger">*</span></label>
                        <input type="datetime-local" class="form-control" id="meeting_date" name="meeting_date" required value="{{ now()->addDay()->format('Y-m-d\T17:00') }}">
                        <small class="form-text text-muted">Chagua tarehe na saa ambayo ibada ya kanda itafanyika.</small>
                    </div>

                    <div class="form-group">
                        <label for="location">Mahali / Nyumba ya Mwenyeji</label>
                        <input type="text" class="form-control" id="location" name="location" value="{{ $group->location }}" placeholder="Mf: Nyumbani kwa Mzee John au Kanisani">
                    </div>

                    <div class="form-group">
                        <label for="topic">Mada ya Ibada / Somo</label>
                        <input type="text" class="form-control" id="topic" name="topic" placeholder="Mf: Maombi na Kujifunza Neno la Mungu">
                    </div>

                    <div class="form-group">
                        <label for="notes">Maelezo ya Ziada (Hiari)</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="Maelezo yoyote ya ziada kuhusu ibada hii..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Ghairi</button>
                    <button type="submit" class="btn btn-success font-weight-bold">
                        <i class="fas fa-save mr-1"></i> Hifadhi Ibada
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
