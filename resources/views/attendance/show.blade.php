@extends('layouts.admin')

@section('title', 'Rekodi Mahudhurio ya Ibada')

@push('styles')
<style>
    .counter-badge-card {
        border-radius: 10px;
        padding: 10px 16px;
        text-align: center;
        background: #fff;
        box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        border: 1px solid #e9ecef;
    }
    .table-hover tbody tr:hover {
        background-color: rgba(0, 123, 255, 0.04);
    }
    .avatar-sm-circle {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.85rem;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    
    <!-- HEADER -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 mt-2 pb-2 border-bottom">
        <div>
            <h2 class="h3 font-weight-bold text-dark mb-1">
                <i class="fas fa-clipboard-check text-success mr-2"></i> Rekodi Mahudhurio (Manual Roll Call)
            </h2>
            <p class="text-muted mb-0 small">
                Ukurasa wa mashemasi na viongozi kuweka mahudhurio ya washirika kwenye ibada.
            </p>
        </div>
        <div class="d-flex align-items-center mt-2 mt-md-0" style="gap: 8px;">
            <a href="{{ route('attendance.index') }}" class="btn btn-outline-secondary btn-sm shadow-sm font-weight-bold">
                <i class="fas fa-chart-line mr-1"></i> Dashibodi ya Mahudhurio
            </a>
        </div>
    </div>

    @if(session('status'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm">
            <i class="fas fa-check-circle mr-1"></i> {{ session('status') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm">
            <i class="fas fa-exclamation-triangle mr-1"></i> {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    <!-- EVENT SELECTOR & INFO CARD -->
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body p-3 bg-light rounded">
            <div class="row align-items-center">
                <div class="col-md-6 mb-2 mb-md-0">
                    <label for="switch_event_id" class="small font-weight-bold text-dark mb-1">
                        <i class="fas fa-church text-primary mr-1"></i> Ibada Inayorekodiwa:
                    </label>
                    <select id="switch_event_id" class="form-control font-weight-bold border-primary shadow-sm" 
                            onchange="window.location.href = '{{ route('attendance.record') }}?event_id=' + this.value">
                        @if(isset($allEvents))
                            @foreach($allEvents as $ev)
                                <option value="{{ $ev->id }}" {{ $event->id == $ev->id ? 'selected' : '' }}>
                                    {{ $ev->date->format('d/m/Y') }} - {{ $ev->name }} {{ $ev->date->isToday() ? '(Leo)' : '' }}
                                </option>
                            @endforeach
                        @else
                            <option value="{{ $event->id }}" selected>{{ $event->name }} ({{ $event->date->format('d/m/Y') }})</option>
                        @endif
                    </select>
                </div>

                <div class="col-md-6">
                    <div class="row text-center" style="gap: 5px;">
                        <div class="col counter-badge-card">
                            <span class="text-muted small d-block">Waliofika</span>
                            <strong class="text-success h5 mb-0" id="stat-present">{{ $attendances->where('status', 'present')->count() }}</strong>
                        </div>
                        <div class="col counter-badge-card">
                            <span class="text-muted small d-block">Waliochelewa</span>
                            <strong class="text-warning h5 mb-0" id="stat-late">{{ $attendances->where('status', 'late')->count() }}</strong>
                        </div>
                        <div class="col counter-badge-card">
                            <span class="text-muted small d-block">Wasiofika</span>
                            <strong class="text-danger h5 mb-0" id="stat-absent">{{ $attendances->where('status', 'absent')->count() }}</strong>
                        </div>
                        <div class="col counter-badge-card">
                            <span class="text-muted small d-block">Jumla Washirika</span>
                            <strong class="text-dark h5 mb-0" id="stat-total">{{ $members->count() }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MAIN RECORDING CARD -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 border-bottom">
            <div class="row align-items-center">
                <!-- Search Box -->
                <div class="col-md-4 mb-2 mb-md-0">
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-light border-right-0"><i class="fas fa-search text-muted"></i></span>
                        </div>
                        <input type="text" id="memberSearchInput" class="form-control border-left-0" 
                               placeholder="Tafuta mshirika kwa jina au simu..." onkeyup="filterMemberTable()">
                    </div>
                </div>

                <!-- Zone Filter -->
                <div class="col-md-3 mb-2 mb-md-0">
                    <select id="zoneFilterSelect" class="form-control form-control-sm" onchange="filterMemberTable()">
                        <option value="">-- Kanda Zote (Zone) --</option>
                        @if(isset($smallGroups))
                            @foreach($smallGroups as $sg)
                                <option value="{{ strtolower($sg->name) }}">{{ $sg->name }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <!-- Bulk Action Buttons -->
                <div class="col-md-5 text-md-right">
                    <button type="button" class="btn btn-sm btn-success shadow-sm font-weight-bold mr-1" onclick="markAllPresent()">
                        <i class="fas fa-check-double mr-1"></i> Weka Wote Waliohudhuria
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger shadow-sm font-weight-bold" onclick="markUnmarkedAbsent()">
                        <i class="fas fa-user-times mr-1"></i> Wasiofika Wote
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="membersTable">
                    <thead class="thead-light">
                        <tr>
                            <th width="4%">#</th>
                            <th>Mshirika</th>
                            <th>Kanda / Zone</th>
                            <th>Namba ya Simu</th>
                            <th width="14%">Hali ya Sasa</th>
                            <th width="28%" class="text-center">Chagua Mahudhurio</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($members as $index => $member)
                            @php
                                $att = $attendances->get($member->id);
                                $status = $att ? $att->status : null;
                                $zoneName = $member->smallGroups->first()->name ?? 'Bila Kanda';
                            @endphp
                            <tr id="member-row-{{ $member->id }}" 
                                class="member-row 
                                    @if($status === 'present') table-success
                                    @elseif($status === 'absent') table-danger
                                    @elseif($status === 'late') table-warning
                                    @endif"
                                data-name="{{ strtolower($member->full_name) }}"
                                data-phone="{{ strtolower($member->phone ?? '') }}"
                                data-zone="{{ strtolower($zoneName) }}">
                                
                                <td class="text-muted font-weight-bold row-index">{{ $index + 1 }}</td>
                                
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm-circle bg-light border mr-2 
                                            @if($status === 'present') text-success border-success
                                            @elseif($status === 'absent') text-danger border-danger
                                            @else text-primary @endif">
                                            {{ strtoupper(substr($member->first_name ?? 'M', 0, 1)) }}
                                        </div>
                                        <div>
                                            <strong class="text-dark">{{ $member->full_name }}</strong>
                                            @if($member->member_number)
                                                <span class="badge badge-light border text-muted ml-1">{{ $member->member_number }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <span class="badge badge-light border font-weight-normal">
                                        <i class="fas fa-map-marker-alt text-muted mr-1"></i> {{ $zoneName }}
                                    </span>
                                </td>

                                <td>
                                    @if($member->phone)
                                        <span class="text-muted small"><i class="fas fa-phone mr-1"></i> {{ $member->phone }}</span>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>

                                <td id="status-badge-{{ $member->id }}">
                                    @if($status === 'present')
                                        <span class="badge badge-success px-2 py-1"><i class="fas fa-check mr-1"></i> Alihudhuria</span>
                                    @elseif($status === 'late')
                                        <span class="badge badge-warning px-2 py-1"><i class="fas fa-clock mr-1"></i> Alichelewa</span>
                                    @elseif($status === 'absent')
                                        <span class="badge badge-danger px-2 py-1"><i class="fas fa-times mr-1"></i> Hakuhudhuria</span>
                                    @else
                                        <span class="badge badge-secondary px-2 py-1">Bado Kurekodiwa</span>
                                    @endif
                                </td>

                                <td class="text-center">
                                    <div class="btn-group btn-group-sm shadow-sm" role="group">
                                        <button type="button" 
                                                class="btn btn-outline-success font-weight-bold {{ $status === 'present' ? 'active' : '' }}" 
                                                onclick="markStatus({{ $member->id }}, 'present')"
                                                id="btn-present-{{ $member->id }}">
                                            <i class="fas fa-check"></i> Alifika
                                        </button>
                                        <button type="button" 
                                                class="btn btn-outline-warning font-weight-bold {{ $status === 'late' ? 'active' : '' }}" 
                                                onclick="markStatus({{ $member->id }}, 'late')"
                                                id="btn-late-{{ $member->id }}">
                                            <i class="fas fa-clock"></i> Chelewa
                                        </button>
                                        <button type="button" 
                                                class="btn btn-outline-danger font-weight-bold {{ $status === 'absent' ? 'active' : '' }}" 
                                                onclick="markStatus({{ $member->id }}, 'absent')"
                                                id="btn-absent-{{ $member->id }}">
                                            <i class="fas fa-times"></i> Hakufika
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    // Live Client-Side Table Filter (Search by name/phone + filter by zone)
    function filterMemberTable() {
        const query = document.getElementById('memberSearchInput').value.toLowerCase().trim();
        const zoneFilter = document.getElementById('zoneFilterSelect').value.toLowerCase().trim();
        const rows = document.querySelectorAll('.member-row');

        rows.forEach(row => {
            const name = row.getAttribute('data-name') || '';
            const phone = row.getAttribute('data-phone') || '';
            const zone = row.getAttribute('data-zone') || '';

            const matchesQuery = !query || name.includes(query) || phone.includes(query);
            const matchesZone = !zoneFilter || zone.includes(zoneFilter);

            if (matchesQuery && matchesZone) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    // Update row visual style and status badge
    function updateRowStyle(memberId, status) {
        const row = document.getElementById(`member-row-${memberId}`);
        const badgeCell = document.getElementById(`status-badge-${memberId}`);
        if (!row || !badgeCell) return;

        // Reset classes
        row.classList.remove('table-success', 'table-danger', 'table-warning');

        // Reset button active states
        const btnP = document.getElementById(`btn-present-${memberId}`);
        const btnL = document.getElementById(`btn-late-${memberId}`);
        const btnA = document.getElementById(`btn-absent-${memberId}`);
        if (btnP) btnP.classList.remove('active');
        if (btnL) btnL.classList.remove('active');
        if (btnA) btnA.classList.remove('active');

        if (status === 'present') {
            row.classList.add('table-success');
            badgeCell.innerHTML = '<span class="badge badge-success px-2 py-1"><i class="fas fa-check mr-1"></i> Alihudhuria</span>';
            if (btnP) btnP.classList.add('active');
        } else if (status === 'late') {
            row.classList.add('table-warning');
            badgeCell.innerHTML = '<span class="badge badge-warning px-2 py-1"><i class="fas fa-clock mr-1"></i> Alichelewa</span>';
            if (btnL) btnL.classList.add('active');
        } else if (status === 'absent') {
            row.classList.add('table-danger');
            badgeCell.innerHTML = '<span class="badge badge-danger px-2 py-1"><i class="fas fa-times mr-1"></i> Hakuhudhuria</span>';
            if (btnA) btnA.classList.add('active');
        }
    }

    // Update top counter widgets
    function updateSummaryCounts(counts) {
        if (counts) {
            if (counts.present !== undefined) $('#stat-present').text(counts.present);
            if (counts.late !== undefined) $('#stat-late').text(counts.late);
            if (counts.absent !== undefined) $('#stat-absent').text(counts.absent);
        }
    }

    // Toast alert notification
    function showNotification(message, type = 'success') {
        const toast = $(`
            <div class="alert alert-${type} shadow-sm position-fixed" style="top: 75px; right: 20px; z-index: 9999; min-width: 250px;">
                <i class="fas fa-check-circle mr-1"></i> ${message}
            </div>
        `);
        $('body').append(toast);
        setTimeout(() => toast.fadeOut(300, function(){ $(this).remove(); }), 2200);
    }

    // Mark single member status
    function markStatus(memberId, status) {
        fetch("{{ route('attendance.mark', $event) }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                member_id: memberId,
                status: status
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateRowStyle(memberId, status);
                updateSummaryCounts(data.counts);
                showNotification('Mahudhurio yamehifadhiwa');
            } else {
                alert(data.message || 'Hitilafu wakati wa kusajili mahudhurio.');
            }
        })
        .catch(error => {
            console.error('Error marking status:', error);
        });
    }

    // Mark all members as present
    function markAllPresent() {
        if (confirm('Je, una uhakika unataka kuweka washirika wote kuwa wamehudhuria?')) {
            const memberIds = @json($members->pluck('id'));

            fetch("{{ route('attendance.bulk-mark', $event) }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    present: memberIds
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    memberIds.forEach(id => updateRowStyle(id, 'present'));
                    updateSummaryCounts(data.counts);
                    showNotification('Washirika wote wamewekwa kuwa wamehudhuria');
                }
            })
            .catch(err => console.error(err));
        }
    }

    // Mark unmarked members as absent
    function markUnmarkedAbsent() {
        if (confirm('Je, una uhakika unataka kuweka washirika ambao hawajarekodiwa kuwa hawajahudhuria?')) {
            const memberIds = [];
            document.querySelectorAll('.member-row').forEach(row => {
                if (!row.classList.contains('table-success') && !row.classList.contains('table-warning')) {
                    const id = row.id.replace('member-row-', '');
                    memberIds.push(parseInt(id));
                }
            });

            if (memberIds.length === 0) {
                alert('Washirika wote tayari wamerekodiwa.');
                return;
            }

            fetch("{{ route('attendance.bulk-mark', $event) }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    absent: memberIds
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    memberIds.forEach(id => updateRowStyle(id, 'absent'));
                    updateSummaryCounts(data.counts);
                    showNotification('Washirika ambao hawakuwepo wamewekwa hawajahudhuria');
                }
            })
            .catch(err => console.error(err));
        }
    }
</script>
@endpush
