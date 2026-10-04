@extends('layouts.admin')

@section('title', __('Mahudhurio ya Ibada ya Kanda: ') . $group->name)

@section('content')
<div class="container-fluid">
    <div class="row mb-3 mt-3 align-items-center">
        <div class="col-sm-6">
            <h1 class="m-0 text-dark">
                <i class="fas fa-clipboard-check text-primary"></i> {{ __('Mahudhurio ya Ibada ya Kanda') }}
            </h1>
            <p class="text-muted mb-0">{{ $group->name }}</p>
        </div>
        <div class="col-sm-6 text-right">
            <a href="{{ route('small-groups.show', $group) }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> {{ __('Rudi Kwenye Kanda') }}
            </a>
            @if(Route::has('zones.my-group'))
            <a href="{{ route('zones.my-group') }}" class="btn btn-outline-info">
                <i class="fas fa-users"></i> {{ __('Kanda Yangu') }}
            </a>
            @endif
        </div>
    </div>

    <!-- Meeting Info Banner -->
    <div class="card card-outline card-primary shadow-sm mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="d-flex flex-wrap gap-3">
                        <div class="mr-4 mb-2">
                            <span class="text-muted d-block"><i class="far fa-calendar-alt text-primary"></i> Tarehe & Siku:</span>
                            <strong class="h6">{{ \Carbon\Carbon::parse($meeting->meeting_date)->format('l, d M Y') }}</strong>
                        </div>
                        <div class="mr-4 mb-2">
                            <span class="text-muted d-block"><i class="far fa-clock text-info"></i> Muda:</span>
                            <strong class="h6">{{ \Carbon\Carbon::parse($meeting->meeting_date)->format('H:i') }}</strong>
                        </div>
                        <div class="mr-4 mb-2">
                            <span class="text-muted d-block"><i class="fas fa-map-marker-alt text-danger"></i> Mahali / Mwenyeji:</span>
                            <strong class="h6">{{ $meeting->location ?: ($group->location ?: 'Kwenye Kanda / Kanisani') }}</strong>
                        </div>
                        @if($meeting->topic)
                        <div class="mb-2">
                            <span class="text-muted d-block"><i class="fas fa-book-open text-warning"></i> Mada ya Ibada:</span>
                            <strong class="h6">{{ $meeting->topic }}</strong>
                        </div>
                        @endif
                    </div>
                </div>
                <div class="col-md-4 text-md-right mt-3 mt-md-0">
                    @if(!Auth::user()->hasRole('pastor') || Auth::user()->hasRole('super_admin'))
                    <!-- Quick reminder button if not yet started -->
                    <form action="{{ route('small-groups.meetings.send-reminder', $meeting) }}" method="POST" class="d-inline" onsubmit="return confirm('Je, una uhakika unataka kutuma SMS ya mwaliko/ukumbusho wa ibada hii kwa wanakanda wote sasa?')">
                        @csrf
                        <button type="submit" class="btn btn-warning shadow-sm">
                            <i class="fas fa-bell"></i> {{ __('Tuma Mwaliko / Ukumbusho (SMS)') }}
                        </button>
                    </form>
                    @endif
                    @if($meeting->reminder_sent_at)
                    <div class="small text-success mt-1">
                        <i class="fas fa-check-circle"></i> Mwaliko ulitumwa: {{ $meeting->reminder_sent_at->format('d/m/Y H:i') }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Attendance Form -->
    <form action="{{ route('small-groups.meetings.save-attendance', $meeting) }}" method="POST" id="attendanceForm">
        @csrf

        <div class="card shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h3 class="card-title font-weight-bold text-dark mb-0">
                        <i class="fas fa-user-check text-success mr-1"></i> Orodha ya Wanakanda ({{ $group->members->count() }})
                    </h3>
                </div>
                @if(!Auth::user()->hasRole('pastor') || Auth::user()->hasRole('super_admin'))
                <div class="mt-2 mt-sm-0">
                    <button type="button" class="btn btn-sm btn-outline-success mr-2" id="btnMarkAllPresent">
                        <i class="fas fa-check-double"></i> Weka Wote Walikuwepo
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" id="btnMarkAllAbsent">
                        <i class="fas fa-times-circle"></i> Weka Wote Hawakuwepo
                    </button>
                </div>
                @endif
            </div>

            <div class="card-body p-0 table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Mwanakanda</th>
                            <th>Namba ya Simu</th>
                            <th>Hali ya Mahudhurio</th>
                            <th class="text-center" style="width: 250px;">SMS ya Mfumo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($group->members as $index => $member)
                            @php
                                $att = $attendances->get($member->id);
                                // Default to present if record exists and is present, else absent if already marked absent, or default present if new
                                $currentStatus = $att ? $att->status : 'present';
                            @endphp
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    <strong>{{ $member->full_name }}</strong>
                                    @if($member->id === $group->leader_id)
                                        <span class="badge badge-primary ml-1">Kiongozi</span>
                                    @elseif($member->pivot->role === 'co-leader')
                                        <span class="badge badge-info ml-1">Msaidizi</span>
                                    @endif
                                    <div class="small text-muted">{{ $member->member_number }}</div>
                                </td>
                                <td>
                                    @if(!empty($member->phone))
                                        <span class="text-monospace"><i class="fas fa-phone-alt text-success mr-1"></i> {{ $member->phone }}</span>
                                    @else
                                        <span class="text-danger small"><i class="fas fa-exclamation-triangle"></i> Haina namba</span>
                                    @endif
                                </td>
                                <td>
                                    @if(!Auth::user()->hasRole('pastor') || Auth::user()->hasRole('super_admin'))
                                    <div class="btn-group btn-group-toggle" data-toggle="buttons">
                                        <label class="btn btn-sm btn-outline-success status-label {{ $currentStatus === 'present' ? 'active' : '' }}">
                                            <input type="radio" name="attendance[{{ $member->id }}]" value="present" class="attendance-radio" {{ $currentStatus === 'present' ? 'checked' : '' }}>
                                            <i class="fas fa-check"></i> Alikuwepo
                                        </label>
                                        <label class="btn btn-sm btn-outline-danger status-label {{ $currentStatus === 'absent' ? 'active' : '' }}">
                                            <input type="radio" name="attendance[{{ $member->id }}]" value="absent" class="attendance-radio" {{ $currentStatus === 'absent' ? 'checked' : '' }}>
                                            <i class="fas fa-times"></i> Hakufika
                                        </label>
                                    </div>
                                    @else
                                        @if($currentStatus === 'present')
                                            <span class="badge badge-success px-2 py-1"><i class="fas fa-check"></i> Alikuwepo</span>
                                        @elseif($currentStatus === 'absent')
                                            <span class="badge badge-danger px-2 py-1"><i class="fas fa-times"></i> Hakufika</span>
                                        @else
                                            <span class="badge badge-light border text-muted px-2 py-1">Haijachukuliwa</span>
                                        @endif
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($att && $att->sms_status === 'sent')
                                        <span class="badge badge-success px-2 py-1">
                                            <i class="fas fa-check-circle"></i> SMS Imetumwa
                                        </span>
                                        <div class="small text-muted">{{ $att->sms_sent_at ? $att->sms_sent_at->format('d/m H:i') : '' }}</div>
                                    @elseif($att && $att->sms_status === 'failed')
                                        <span class="badge badge-danger px-2 py-1">
                                            <i class="fas fa-times-circle"></i> SMS Imefeli
                                        </span>
                                    @else
                                        <span class="badge badge-light border text-muted px-2 py-1">
                                            Haijatumwa
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="fas fa-users-slash fa-2x mb-2 d-block"></i>
                                    Hakuna wanakanda waliosajiliwa kwenye kanda hii.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Footer & Submission Panel -->
            @if($group->members->count() > 0)
            <div class="card-footer bg-light p-3">
                <div class="row align-items-center">
                    @if(!Auth::user()->hasRole('pastor') || Auth::user()->hasRole('super_admin'))
                    <div class="col-md-7 mb-2 mb-md-0">
                        <div class="text-muted small">
                            <i class="fas fa-magic text-success mr-1"></i>
                            <strong>Mfumo unatuma SMS kiotomatiki:</strong> Waliohudhuria watatumiwa ujumbe wa shukrani, na wasiofika watatumiwa ujumbe wa kuwakumbuka na kuwakaribisha tena.
                        </div>
                    </div>
                    <div class="col-md-5 text-md-right">
                        <button type="submit" class="btn btn-success btn-lg px-4 shadow font-weight-bold">
                            <i class="fas fa-save mr-1"></i> {{ __('Hifadhi Mahudhurio') }}
                        </button>
                    </div>
                    @else
                    <div class="col-12 text-center text-muted py-1">
                        <i class="fas fa-eye text-primary mr-1"></i> <strong>Mtazamo wa Mchungaji:</strong> Hali ya mahudhurio ya kanda (Kuangalia tu — Kiongozi wa kanda ndiye anayerekodi).
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Mark All Present
    $('#btnMarkAllPresent').on('click', function() {
        $('input.attendance-radio[value="present"]').each(function() {
            $(this).prop('checked', true);
            $(this).closest('.btn-group-toggle').find('.status-label').removeClass('active');
            $(this).parent().addClass('active');
        });
    });

    // Mark All Absent
    $('#btnMarkAllAbsent').on('click', function() {
        $('input.attendance-radio[value="absent"]').each(function() {
            $(this).prop('checked', true);
            $(this).closest('.btn-group-toggle').find('.status-label').removeClass('active');
            $(this).parent().addClass('active');
        });
    });
});
</script>
@endpush
