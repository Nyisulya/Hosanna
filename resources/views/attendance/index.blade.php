@extends('layouts.admin')

@section('title', 'Dashibodi ya Mahudhurio')

@push('styles')
<style>
    .stat-card-kpi {
        border-radius: 14px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.06);
        border: none;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        overflow: hidden;
    }
    .stat-card-kpi:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }
    .kpi-gradient-blue { background: linear-gradient(135deg, #1e3c72, #2a5298); color: #fff; }
    .kpi-gradient-green { background: linear-gradient(135deg, #11998e, #38ef7d); color: #fff; }
    .kpi-gradient-purple { background: linear-gradient(135deg, #654ea3, #eaafc8); color: #fff; }
    .kpi-gradient-orange { background: linear-gradient(135deg, #f7971e, #ffd200); color: #fff; }
    
    .event-badge-pill {
        border-radius: 20px;
        padding: 4px 12px;
        font-size: 0.8rem;
        font-weight: 700;
    }
    .avatar-circle {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.9rem;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    
    <!-- HEADER & PERIOD SELECTOR -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 mt-2 pb-2 border-bottom">
        <div>
            <h2 class="h3 font-weight-bold text-dark mb-1">
                <i class="fas fa-chart-line text-primary mr-2"></i> Dashibodi ya Mahudhurio ya Kanisa
            </h2>
            <p class="text-muted mb-0 small">
                Muhtasari wa mahudhurio kwa viongozi: Takwimu za wiki, ibada maalum, na kanda (zone).
            </p>
        </div>

        <div class="d-flex flex-wrap align-items-center mt-3 mt-md-0" style="gap: 10px;">
            <!-- Week Navigator -->
            <div class="btn-group shadow-sm">
                <a href="{{ route('attendance.index', ['week_offset' => $weekOffset - 1, 'event_id' => request('event_id')]) }}" 
                   class="btn btn-outline-secondary btn-sm" title="Wiki Iliyopita">
                    <i class="fas fa-chevron-left"></i> Wiki Iliyopita
                </a>
                <span class="btn btn-light btn-sm font-weight-bold border-top border-bottom text-dark">
                    <i class="far fa-calendar-alt text-primary mr-1"></i>
                    {{ $selectedWeekStart->format('d M') }} - {{ $selectedWeekEnd->format('d M, Y') }}
                    @if($weekOffset === 0)
                        <span class="badge badge-success ml-1">Wiki Hii</span>
                    @endif
                </span>
                <a href="{{ route('attendance.index', ['week_offset' => $weekOffset + 1, 'event_id' => request('event_id')]) }}" 
                   class="btn btn-outline-secondary btn-sm" title="Wiki Ijayo">
                    Wiki Ijayo <i class="fas fa-chevron-right"></i>
                </a>
            </div>

            @if($weekOffset !== 0)
                <a href="{{ route('attendance.index') }}" class="btn btn-sm btn-outline-primary shadow-sm font-weight-bold">
                    <i class="fas fa-undo mr-1"></i> Rudi Wiki Hii
                </a>
            @endif

            @if(auth()->user()->hasAnyRole(['super_admin', 'admin', 'department_leader', 'secretary', 'deacon', 'shemasi']) && (!auth()->user()->hasRole('pastor') || auth()->user()->hasRole('super_admin')))
                <a href="{{ route('attendance.record') }}" class="btn btn-success btn-sm shadow-sm font-weight-bold">
                    <i class="fas fa-user-check mr-1"></i> Rekodi Mahudhurio
                </a>
            @endif
        </div>
    </div>

    <!-- FLASH MESSAGES -->
    @if(session('status'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm">
            <i class="fas fa-check-circle mr-1"></i> {{ session('status') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    <!-- ROW 1: TOP EXECUTIVE KPI CARDS -->
    <div class="row mb-4">
        <!-- KPI 1: Mahudhurio ya Wiki Hii -->
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card stat-card-kpi kpi-gradient-blue h-100 p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-uppercase small font-weight-bold text-white-50">Mahudhurio Wiki Hii</span>
                        <h2 class="font-weight-bold mb-0 text-white mt-1">{{ number_format($weekStats['total_attended']) }}</h2>
                    </div>
                    <div class="p-3 bg-white text-primary rounded-circle shadow-sm">
                        <i class="fas fa-users fa-2x"></i>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-top border-white-50 small text-white">
                    <i class="fas fa-check-double mr-1"></i> <strong>{{ $weekStats['unique_members'] }}</strong> kati ya <strong>{{ $totalMembers }}</strong> waumini walio hai (<strong>{{ $weekStats['rate'] }}%</strong>)
                </div>
            </div>
        </div>

        <!-- KPI 2: Ibada Iliyochaguliwa -->
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card stat-card-kpi kpi-gradient-green h-100 p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-uppercase small font-weight-bold text-white-50">Ibada Hii / Ya Karibuni</span>
                        <h2 class="font-weight-bold mb-0 text-white mt-1">{{ number_format($selectedEventStats['total_attended']) }}</h2>
                    </div>
                    <div class="p-3 bg-white text-success rounded-circle shadow-sm">
                        <i class="fas fa-church fa-2x"></i>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-top border-white-50 small text-white text-truncate" title="{{ $selectedEvent ? $selectedEvent->name : 'Hakuna Ibada' }}">
                    <i class="fas fa-calendar-day mr-1"></i> 
                    @if($selectedEvent)
                        <strong>{{ Str::limit($selectedEvent->name, 22) }}</strong> ({{ $selectedEventStats['rate'] }}%)
                    @else
                        <span>Hakuna ibada iliyorekodiwa</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- KPI 3: Mchanganuo wa Ibada (Present vs Late vs Absent) -->
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card stat-card-kpi kpi-gradient-purple h-100 p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-uppercase small font-weight-bold text-white-50">Hali ya Mahudhurio</span>
                        <h4 class="font-weight-bold mb-0 text-white mt-1">
                            <span class="text-light">{{ $selectedEventStats['present'] }}</span> <small style="font-size:0.75rem;">Waliwahi</small> | 
                            <span class="text-warning">{{ $selectedEventStats['late'] }}</span> <small style="font-size:0.75rem;">Kuchelewa</small>
                        </h4>
                    </div>
                    <div class="p-3 bg-white text-purple rounded-circle shadow-sm" style="color: #654ea3;">
                        <i class="fas fa-user-clock fa-2x"></i>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-top border-white-50 small text-white">
                    <i class="fas fa-user-times mr-1"></i> Wasiofika: <strong>{{ max(0, $totalMembers - $selectedEventStats['total_attended']) }}</strong> waumini
                </div>
            </div>
        </div>

        <!-- KPI 4: Kanda / Zone Roll Call -->
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card stat-card-kpi kpi-gradient-orange h-100 p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-uppercase small font-weight-bold text-white-50">Kanda / Zone Wiki Hii</span>
                        <h2 class="font-weight-bold mb-0 text-white mt-1">
                            {{ $zoneMeetingStats->where('status', 'recorded')->count() }} <small style="font-size:0.9rem;">/ {{ $zoneMeetingStats->count() }} Zone</small>
                        </h2>
                    </div>
                    <div class="p-3 bg-white text-warning rounded-circle shadow-sm" style="color: #f7971e;">
                        <i class="fas fa-map-marker-alt fa-2x"></i>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-top border-white-50 small text-white">
                    <i class="fas fa-walking mr-1"></i> <strong>{{ number_format($zoneMeetingStats->sum('attended')) }}</strong> washirika wamehudhuria Kanda
                </div>
            </div>
        </div>
    </div>

    <!-- ROW 2: IBADA MAALUM SUMMARY & EVENT SWITCHER -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 border-bottom">
            <div class="row align-items-center">
                <div class="col-md-7 mb-2 mb-md-0">
                    <h5 class="font-weight-bold text-dark m-0">
                        <i class="fas fa-pray text-success mr-2"></i> 
                        Muhtasari wa Ibada: 
                        <span class="text-primary">{{ $selectedEvent ? $selectedEvent->name : 'Chagua Ibada Hapa Chini' }}</span>
                    </h5>
                    @if($selectedEvent)
                        <small class="text-muted">
                            <i class="far fa-calendar-check mr-1"></i> Tarehe: <strong>{{ $selectedEvent->date ? \Carbon\Carbon::parse($selectedEvent->date)->format('d/m/Y') : '-' }}</strong> 
                            @if($selectedEvent->date)
                                ({{ \Carbon\Carbon::parse($selectedEvent->date)->translatedFormat('l') }})
                            @endif
                            @if($selectedEvent->start_time)
                                | <i class="far fa-clock mr-1"></i> Saa: <strong>{{ \Carbon\Carbon::parse($selectedEvent->start_time)->format('h:i A') }}</strong>
                            @endif
                            @if($selectedEvent->location)
                                | <i class="fas fa-map-pin mr-1"></i> Mahali: <strong>{{ $selectedEvent->location }}</strong>
                            @endif
                        </small>
                    @endif
                </div>
                
                <!-- Quick Service Dropdown Switcher -->
                <div class="col-md-5">
                    <form method="GET" action="{{ route('attendance.index') }}" class="d-flex align-items-center justify-content-md-end" style="gap: 8px;">
                        <input type="hidden" name="week_offset" value="{{ $weekOffset }}">
                        <label for="event_id" class="small font-weight-bold text-muted mb-0 mr-1 text-nowrap">Badili Ibada:</label>
                        <select name="event_id" id="event_id" class="form-control form-control-sm font-weight-bold border-primary shadow-sm" onchange="this.form.submit()">
                            @forelse($allRecentEvents as $ev)
                                <option value="{{ $ev->id }}" {{ ($selectedEvent && $selectedEvent->id == $ev->id) ? 'selected' : '' }}>
                                    {{ $ev->date ? \Carbon\Carbon::parse($ev->date)->format('d/m/y') : '-' }} - {{ Str::limit($ev->name, 28) }}
                                </option>
                            @empty
                                <option value="">Hakuna ibada zilizorekodiwa</option>
                            @endforelse
                        </select>
                    </form>
                </div>
            </div>
        </div>

        @if($selectedEvent)
            <div class="card-body">
                <!-- Visual Attendance Breakdown Bar -->
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="font-weight-bold small text-dark">Uwiano wa Mahudhurio ya Waumini ({{ $selectedEventStats['total_attended'] }} kati ya {{ $totalMembers }})</span>
                        <span class="badge badge-success font-weight-bold px-2 py-1">{{ $selectedEventStats['rate'] }}% Wamehudhuria</span>
                    </div>
                    <div class="progress" style="height: 14px; border-radius: 7px; overflow: hidden; background-color: #e9ecef;">
                        @php
                            $presentPct = $totalMembers > 0 ? ($selectedEventStats['present'] / $totalMembers) * 100 : 0;
                            $latePct = $totalMembers > 0 ? ($selectedEventStats['late'] / $totalMembers) * 100 : 0;
                            $absentPct = max(0, 100 - ($presentPct + $latePct));
                        @endphp
                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ $presentPct }}%" title="Waliofika: {{ $selectedEventStats['present'] }}"></div>
                        <div class="progress-bar bg-warning" role="progressbar" style="width: {{ $latePct }}%" title="Waliochelewa: {{ $selectedEventStats['late'] }}"></div>
                        <div class="progress-bar bg-light text-muted border-left" role="progressbar" style="width: {{ $absentPct }}%" title="Wasiofika: {{ max(0, $totalMembers - $selectedEventStats['total_attended']) }}"></div>
                    </div>
                    <div class="d-flex justify-content-between mt-2 small text-muted">
                        <span><i class="fas fa-circle text-success mr-1"></i> Waliofika kwa Wakati: <strong>{{ $selectedEventStats['present'] }}</strong></span>
                        <span><i class="fas fa-circle text-warning mr-1"></i> Waliochelewa: <strong>{{ $selectedEventStats['late'] }}</strong></span>
                        <span><i class="fas fa-circle text-danger mr-1"></i> Wasiohudhuria: <strong>{{ max(0, $totalMembers - $selectedEventStats['total_attended']) }}</strong></span>
                        <span><i class="fas fa-user-plus text-info mr-1"></i> Wageni: <strong>{{ $selectedEventStats['visitors'] }}</strong></span>
                    </div>
                </div>

                <!-- Demographic Badges -->
                <div class="row text-center mb-4">
                    <div class="col-6 col-md-3 mb-2">
                        <div class="p-3 bg-light rounded border">
                            <span class="text-muted small d-block">Wanaume (Me)</span>
                            <h4 class="font-weight-bold text-primary mb-0 mt-1"><i class="fas fa-male mr-1"></i> {{ $selectedEventStats['male'] }}</h4>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 mb-2">
                        <div class="p-3 bg-light rounded border">
                            <span class="text-muted small d-block">Wanawake (Ke)</span>
                            <h4 class="font-weight-bold text-danger mb-0 mt-1"><i class="fas fa-female mr-1"></i> {{ $selectedEventStats['female'] }}</h4>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 mb-2">
                        <div class="p-3 bg-light rounded border">
                            <span class="text-muted small d-block">Wageni Waliofika</span>
                            <h4 class="font-weight-bold text-info mb-0 mt-1"><i class="fas fa-user-plus mr-1"></i> {{ $selectedEventStats['visitors'] }}</h4>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 mb-2">
                        <div class="p-3 bg-light rounded border">
                            <span class="text-muted small d-block">Kiwango cha Ufikaji</span>
                            <h4 class="font-weight-bold text-success mb-0 mt-1"><i class="fas fa-percentage mr-1"></i> {{ $selectedEventStats['rate'] }}%</h4>
                        </div>
                    </div>
                </div>

                <!-- TABS: WALIOHUDHURIA vs WASIOHUDHURIA -->
                <ul class="nav nav-tabs font-weight-bold" id="eventAttendanceTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active text-success" id="present-tab" data-toggle="tab" href="#present-pane" role="tab">
                            <i class="fas fa-check-circle mr-1"></i> Waliohudhuria ({{ $selectedEventStats['total_attended'] }})
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-danger" id="absent-tab" data-toggle="tab" href="#absent-pane" role="tab">
                            <i class="fas fa-user-times mr-1"></i> Wasiohudhuria ({{ max(0, $totalMembers - $selectedEventStats['total_attended']) }})
                        </a>
                    </li>
                    @if(auth()->user()->hasAnyRole(['super_admin', 'admin', 'department_leader', 'secretary', 'deacon', 'shemasi']) && (!auth()->user()->hasRole('pastor') || auth()->user()->hasRole('super_admin')))
                        <li class="nav-item ml-auto">
                            <a href="{{ route('attendance.show', $selectedEvent) }}" class="btn btn-outline-primary btn-sm my-1">
                                <i class="fas fa-edit mr-1"></i> Rekodi / Badili Mahudhurio
                            </a>
                        </li>
                    @endif
                </ul>

                <div class="tab-content pt-3" id="eventAttendanceTabsContent">
                    
                    <!-- TAB 1: PRESENT MEMBERS -->
                    <div class="tab-pane fade show active" id="present-pane" role="tabpanel">
                        @if($selectedEventStats['present_attendances']->isNotEmpty())
                            <div class="table-responsive" style="max-height: 380px; overflow-y: auto;">
                                <table class="table table-sm table-hover align-middle">
                                    <thead class="thead-light sticky-top">
                                        <tr>
                                            <th>#</th>
                                            <th>Jina la Muumini</th>
                                            <th>Simu</th>
                                            <th>Kanda / Zone</th>
                                            <th>Hali</th>
                                            <th>Muda wa Kusajiliwa</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($selectedEventStats['present_attendances'] as $idx => $att)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar-circle bg-light text-primary border mr-2">
                                                            {{ strtoupper(substr($att->member->first_name ?? 'M', 0, 1)) }}
                                                        </div>
                                                        <div>
                                                            <strong class="text-dark">{{ $att->member->full_name ?? 'N/A' }}</strong>
                                                            @if($att->member->member_number)
                                                                <span class="badge badge-light border text-muted ml-1">{{ $att->member->member_number }}</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>{{ $att->member->phone ?? '-' }}</td>
                                                <td>
                                                    <span class="badge badge-light border">
                                                        {{ $att->member->smallGroups->first()->name ?? 'Bila Kanda' }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if($att->status === 'present')
                                                        <span class="badge badge-success px-2 py-1"><i class="fas fa-check mr-1"></i> Alihudhuria</span>
                                                    @elseif($att->status === 'late')
                                                        <span class="badge badge-warning px-2 py-1"><i class="fas fa-clock mr-1"></i> Alichelewa</span>
                                                    @endif
                                                </td>
                                                <td class="small text-muted">
                                                    {{ $att->scanned_at ? \Carbon\Carbon::parse($att->scanned_at)->format('H:i') : ($att->created_at ? $att->created_at->format('H:i') : '-') }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-4 text-muted">
                                <i class="fas fa-user-slash fa-3x mb-2 text-muted"></i>
                                <p class="mb-0">Bado hakuna mahudhurio yaliyorekodiwa kwa ibada hii.</p>
                            </div>
                        @endif
                    </div>

                    <!-- TAB 2: ABSENT MEMBERS -->
                    <div class="tab-pane fade" id="absent-pane" role="tabpanel">
                        @if($selectedEventStats['absent_members']->isNotEmpty())
                            <div class="alert alert-light border small text-muted mb-2">
                                <i class="fas fa-info-circle text-primary mr-1"></i> Orodha ya washirika walio hai ambao hawajarekodiwa kwenye ibada hii. Viongozi wanaweza kuwatembelea au kuwapigia simu kuwajulia hali.
                            </div>
                            <div class="table-responsive" style="max-height: 380px; overflow-y: auto;">
                                <table class="table table-sm table-hover align-middle">
                                    <thead class="thead-light sticky-top">
                                        <tr>
                                            <th>#</th>
                                            <th>Jina la Muumini</th>
                                            <th>Simu</th>
                                            <th>Kanda / Zone</th>
                                            <th>Hali</th>
                                            <th>Kitendo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($selectedEventStats['absent_members'] as $idx => $m)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar-circle bg-light text-danger border mr-2">
                                                            {{ strtoupper(substr($m->first_name ?? 'M', 0, 1)) }}
                                                        </div>
                                                        <div>
                                                            <strong class="text-dark">{{ $m->full_name }}</strong>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    @if($m->phone)
                                                        <a href="tel:{{ $m->phone }}" class="text-primary font-weight-bold">
                                                            <i class="fas fa-phone-alt mr-1"></i> {{ $m->phone }}
                                                        </a>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge badge-light border">
                                                        {{ $m->smallGroups->first()->name ?? 'Bila Kanda' }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-danger px-2 py-1"><i class="fas fa-times mr-1"></i> Hakuhudhuria</span>
                                                </td>
                                                <td>
                                                    @if($m->phone)
                                                        <a href="sms:{{ $m->phone }}?body=Bwana Yesu Asifiwe {{ $m->first_name }}, tulikukosa kwenye ibada ya leo. Tunakuombea na kukutakia baraka za Mungu." 
                                                           class="btn btn-outline-info btn-xs font-weight-bold">
                                                            <i class="fas fa-comment-dots mr-1"></i> Tuma SMS
                                                        </a>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-4 text-success">
                                <i class="fas fa-check-circle fa-3x mb-2 text-success"></i>
                                <p class="mb-0 font-weight-bold">Hongera! Waumini wote waliosajiliwa wamehudhuria ibada hii.</p>
                            </div>
                        @endif
                    </div>

                </div>
            </div>
        @else
            <div class="card-body text-center py-5">
                <i class="fas fa-calendar-times fa-3x text-muted mb-2"></i>
                <h5 class="text-muted">Hakuna ibada iliyopatikana kwenye kipindi hiki.</h5>
                <p class="small text-muted">Tumia kitufe cha "Wiki Iliyopita" au chagua ibada nyingine kutoka kwenye orodha hapo juu.</p>
            </div>
        @endif
    </div>

    <!-- ROW 3: ZONE / KANDA ATTENDANCE SUMMARY & TREND GRAPH -->
    <div class="row">
        <!-- ZONE ROLL CALL SUMMARY -->
        <div class="col-lg-7 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="font-weight-bold text-dark m-0">
                            <i class="fas fa-map-marked-alt text-warning mr-2"></i> 
                            Muhtasari wa Mahudhurio ya Kanda (Zone) Wiki Hii
                        </h5>
                        <small class="text-muted">Hali ya ibada za kanda na idadi ya washirika waliohudhuria</small>
                    </div>
                    <span class="badge badge-warning font-weight-bold px-2 py-1">
                        {{ $zoneMeetingStats->where('status', 'recorded')->count() }} / {{ $zoneMeetingStats->count() }} Zimeripoti
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Kanda / Zone</th>
                                    <th>Kiongozi</th>
                                    <th>Washirika</th>
                                    <th>Waliohudhuria</th>
                                    <th>Kiwango (%)</th>
                                    <th>Hali</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($zoneMeetingStats as $zs)
                                    <tr>
                                        <td>
                                            <strong class="text-dark">{{ $zs['group']->name }}</strong>
                                        </td>
                                        <td class="small text-muted">
                                            <i class="fas fa-user-tie text-secondary mr-1"></i> {{ $zs['leader_name'] }}
                                        </td>
                                        <td class="font-weight-bold text-muted">{{ $zs['total_members'] }}</td>
                                        <td class="font-weight-bold text-success">{{ $zs['attended'] }}</td>
                                        <td style="width: 130px;">
                                            <div class="d-flex align-items-center">
                                                <div class="progress flex-grow-1 mr-2" style="height: 6px;">
                                                    <div class="progress-bar bg-success" style="width: {{ $zs['rate'] }}%;"></div>
                                                </div>
                                                <span class="small font-weight-bold">{{ $zs['rate'] }}%</span>
                                            </div>
                                        </td>
                                        <td>
                                            @if($zs['status'] === 'recorded')
                                                <span class="badge badge-success px-2 py-1"><i class="fas fa-check mr-1"></i> Imerekodiwa</span>
                                            @else
                                                <span class="badge badge-light border text-warning px-2 py-1"><i class="fas fa-clock mr-1"></i> Inasubiriwa</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">Hakuna kanda zilizosajiliwa.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- TREND GRAPH (RECENT SERVICES) -->
        <div class="col-lg-5 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="font-weight-bold text-dark m-0">
                        <i class="fas fa-chart-bar text-primary mr-2"></i> Mwenendo wa Mahudhurio
                    </h5>
                    <small class="text-muted">Idadi ya waliohudhuria kwenye ibada 8 za mwisho</small>
                </div>
                <div class="card-body">
                    <div style="height: 280px; position: relative;">
                        <canvas id="attendanceTrendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ROW 4: RECENT EVENTS LIST TABLE -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <div>
                <h5 class="font-weight-bold text-dark m-0">
                    <i class="fas fa-list-alt text-secondary mr-2"></i> Orodha ya Ibada na Matukio Yaliyopita
                </h5>
                <small class="text-muted">Bofya kitufe cha 'Tazama Muhtasari' kuona takwimu za ibada husika</small>
            </div>
        </div>
        <div class="card-body p-0">
            @if($allRecentEvents->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Jina la Ibada / Tukio</th>
                                <th>Tarehe</th>
                                <th>Saa</th>
                                <th>Mahudhurio</th>
                                <th>Kiwango (%)</th>
                                <th>Vitendo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($allRecentEvents->take(12) as $ev)
                                @php
                                    $attCount = $ev->attendances->whereIn('status', ['present', 'late'])->count();
                                    $evRate = $totalMembers > 0 ? round(($attCount / $totalMembers) * 100, 1) : 0;
                                    $isSelected = ($selectedEvent && $selectedEvent->id == $ev->id);
                                @endphp
                                <tr class="{{ $isSelected ? 'table-primary font-weight-bold' : '' }}">
                                    <td>
                                        <strong>{{ $ev->name }}</strong>
                                        @if($ev->date && \Carbon\Carbon::parse($ev->date)->isToday())
                                            <span class="badge badge-success ml-1">Leo</span>
                                        @endif
                                    </td>
                                    <td>{{ $ev->date ? \Carbon\Carbon::parse($ev->date)->format('d/m/Y') : '-' }}</td>
                                    <td>{{ $ev->start_time ? \Carbon\Carbon::parse($ev->start_time)->format('h:i A') : 'N/A' }}</td>
                                    <td>
                                        <span class="badge badge-primary px-2 py-1">{{ $attCount }} Waliofika</span>
                                        <span class="badge badge-light border">{{ $ev->attendances->count() }} Jumla</span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center" style="width: 100px;">
                                            <span class="small font-weight-bold mr-2">{{ $evRate }}%</span>
                                            <div class="progress flex-grow-1" style="height: 6px;">
                                                <div class="progress-bar bg-primary" style="width: {{ $evRate }}%;"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="{{ route('attendance.index', ['event_id' => $ev->id, 'week_offset' => $weekOffset]) }}" 
                                           class="btn btn-xs {{ $isSelected ? 'btn-primary' : 'btn-outline-primary' }} font-weight-bold">
                                            <i class="fas fa-chart-pie mr-1"></i> Tazama Muhtasari
                                        </a>
                                        @if(auth()->user()->hasAnyRole(['super_admin', 'admin', 'department_leader', 'secretary', 'deacon', 'shemasi']) && (!auth()->user()->hasRole('pastor') || auth()->user()->hasRole('super_admin')))
                                            <a href="{{ route('attendance.show', $ev) }}" class="btn btn-xs btn-outline-secondary ml-1">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-calendar-times fa-3x mb-3"></i>
                    <p>Hakuna ibada zilizorekodiwa.</p>
                </div>
            @endif
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const ctx = document.getElementById('attendanceTrendChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: {!! json_encode($trendLabels) !!},
                    datasets: [{
                        label: 'Waliohudhuria',
                        data: {!! json_encode($trendData) !!},
                        backgroundColor: 'rgba(30, 60, 114, 0.75)',
                        borderColor: '#1e3c72',
                        borderWidth: 1.5,
                        borderRadius: 6,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Waliohudhuria: ' + context.parsed.y + ' washirika';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0 }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });
        }
    });
</script>
@endpush
