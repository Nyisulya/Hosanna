@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="row mb-4 mt-4">
        <div class="col-12">
            <h1 class="m-0 text-dark">📚 {{ __('Small Groups') }}</h1>
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

    @if(Auth::user()->hasAnyRole(['super_admin', 'admin', 'pastor']))
    <div class="row mb-3">
        <div class="col-12">
            <a href="{{ route('small-groups.create') }}" class="btn btn-primary shadow-sm font-weight-bold">
                <i class="fas fa-plus mr-1"></i> {{ __('Create New Group') }}
            </a>
        </div>
    </div>
    @endif

    <div class="row">
        @foreach($groups as $group)
        <div class="col-md-4 mb-3">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-header bg-white">
                    <h3 class="card-title font-weight-bold text-dark">{{ $group->name }}</h3>
                    <div class="card-tools">
                        <span class="badge badge-{{ $group->isFull() ? 'danger' : 'success' }} px-2 py-1">
                            {{ $group->members->count() }}/{{ $group->max_members }}
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-2">{{ Str::limit($group->description, 100) }}</p>
                    <p class="mb-1"><strong>{{ __('Leader') }}:</strong> {{ $group->leader->full_name ?? 'N/A' }}</p>
                    @if($group->meeting_day)
                    <p class="mb-1"><i class="fas fa-calendar text-muted mr-1"></i> {{ __($group->meeting_day) }} @ {{ $group->meeting_time }}</p>
                    @endif
                    @if($group->location)
                    <p class="mb-0"><i class="fas fa-map-marker-alt text-muted mr-1"></i> {{ $group->location }}</p>
                    @endif
                </div>
                <div class="card-footer bg-white d-flex justify-content-between align-items-center flex-wrap" style="gap: 5px;">
                    <div class="d-flex flex-wrap" style="gap: 4px;">
                        <a href="{{ route('small-groups.show', $group) }}" class="btn btn-sm btn-primary">{{ __('View Details') }}</a>
                        <a href="{{ route('small-groups.attendance', ['group_id' => $group->id]) }}" class="btn btn-sm btn-outline-success">
                            <i class="fas fa-clipboard-check"></i> {{ __('Mahudhurio') }}
                        </a>
                    </div>
                    @if(Auth::user()->hasAnyRole(['super_admin', 'admin', 'pastor', 'Super Admin', 'Admin', 'Pastor']))
                    <div class="d-flex align-items-center" style="gap: 4px;">
                        <a href="{{ route('small-groups.edit', $group) }}" class="btn btn-sm btn-info">{{ __('Edit') }}</a>
                        <form action="{{ route('small-groups.destroy', $group) }}" method="POST" class="d-inline" onsubmit="return confirm('Je, una uhakika unataka kufuta zone ya {{ addslashes($group->name) }}?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="{{ __('Delete') }}">
                                <i class="fas fa-trash-alt"></i> {{ __('Delete') }}
                            </button>
                        </form>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection
