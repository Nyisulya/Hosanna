@extends('layouts.admin')

@section('content')
<div class="container-fluid py-2">
    {{-- Header Banner --}}
    <div class="card bg-gradient-primary border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); border-radius: 12px; color: white;">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                <div class="mb-3 mb-md-0">
                    <div class="d-flex align-items-center">
                        <div class="bg-white text-primary rounded-circle d-flex align-items-center justify-content-center mr-3 shadow-sm" style="width: 46px; height: 46px; min-width: 46px; font-size: 20px;">
                            📢
                        </div>
                        <div>
                            <h2 class="font-weight-bold mb-0 text-white" style="font-size: 1.45rem; letter-spacing: -0.02em;">
                                {{ __('Church Announcements') }}
                            </h2>
                            <p class="text-light mb-0 small opacity-90">
                                {{ __('Matangazo ya Kanisa na Taarifa Muhimu kwa Waumini') }}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('announcements.current') }}" class="btn btn-outline-light btn-sm mr-2 mb-2 mb-md-0 px-3 py-2 font-weight-bold shadow-sm" style="border-radius: 8px;" target="_blank">
                        <i class="fas fa-tv mr-1"></i> {{ __('Display View') }}
                    </a>
                    @can('announcement-create')
                    <a href="{{ route('announcements.create') }}" class="btn btn-warning btn-sm px-3 py-2 font-weight-bold shadow-sm text-dark" style="border-radius: 8px; background-color: #facc15; border-color: #facc15;">
                        <i class="fas fa-plus mr-1"></i> {{ __('Add Announcement') }}
                    </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    {{-- Announcements Content --}}
    @if($announcements->count() > 0)
        {{-- MOBILE CARD VIEW (Visible on small screens < 768px) --}}
        <div class="d-block d-md-none">
            @foreach($announcements as $announcement)
            <div class="card shadow-sm border-0 mb-3" style="border-radius: 12px; overflow: hidden; border-left: 4px solid {{ $announcement->is_active ? '#10b981' : '#9ca3af' }} !important;">
                <div class="card-body p-3">
                    {{-- Header Meta: Date & Status --}}
                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                        <span class="text-muted small font-weight-bold">
                            <i class="far fa-calendar-alt text-primary mr-1"></i>
                            {{ $announcement->announcement_date ? $announcement->announcement_date->format('d M, Y') : 'Tarehe haijapangwa' }}
                        </span>
                        <div class="d-flex align-items-center">
                            @if($announcement->priority > 0)
                                <span class="badge badge-warning mr-1 px-2 py-1" style="font-size: 0.7rem; border-radius: 6px;">
                                    <i class="fas fa-star text-dark"></i> {{ $announcement->priority }}
                                </span>
                            @endif
                            @if($announcement->is_active)
                                <span class="badge badge-success px-2 py-1" style="font-size: 0.72rem; border-radius: 6px; background-color: #10b981;">
                                    <i class="fas fa-check-circle mr-1"></i>{{ __('Active') }}
                                </span>
                            @else
                                <span class="badge badge-secondary px-2 py-1" style="font-size: 0.72rem; border-radius: 6px;">
                                    {{ __('Inactive') }}
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Title & Body --}}
                    <h5 class="font-weight-bold text-dark mb-2" style="font-size: 1.05rem; line-height: 1.35;">
                        {{ $announcement->title }}
                    </h5>
                    <p class="text-secondary small mb-3" style="line-height: 1.5; white-space: pre-line;">
                        {{ Str::limit($announcement->body, 140) }}
                    </p>

                    {{-- Card Actions --}}
                    @canany(['announcement-edit', 'announcement-delete'])
                    <div class="d-flex justify-content-end align-items-center pt-2 border-top">
                        @can('announcement-edit')
                        <a href="{{ route('announcements.edit', $announcement) }}" class="btn btn-sm btn-light text-primary mr-2 px-3 font-weight-bold" style="border-radius: 6px; border: 1px solid #e2e8f0;">
                            <i class="fas fa-edit mr-1"></i> {{ __('Edit') }}
                        </a>
                        @endcan
                        @can('announcement-delete')
                        <form action="{{ route('announcements.destroy', $announcement) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Are you sure you want to delete this announcement?') }}');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger px-3 font-weight-bold" style="border-radius: 6px;">
                                <i class="fas fa-trash-alt mr-1"></i> {{ __('Delete') }}
                            </button>
                        </form>
                        @endcan
                    </div>
                    @endcanany
                </div>
            </div>
            @endforeach
        </div>

        {{-- DESKTOP TABLE VIEW (Visible on screens >= 768px) --}}
        <div class="d-none d-md-block">
            <div class="card shadow-sm border-0" style="border-radius: 12px; overflow: hidden;">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="card-title font-weight-bold mb-0 text-dark">
                        <i class="fas fa-list text-primary mr-2"></i> {{ __('All Announcements') }}
                    </h5>
                    <span class="badge badge-pill badge-light border px-3 py-2 text-muted font-weight-normal">
                        Jumla: <strong>{{ $announcements->total() }}</strong>
                    </span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-muted text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                            <tr>
                                <th class="pl-4 py-3" style="width: 140px;">{{ __('Date') }}</th>
                                <th class="py-3" style="width: 25%;">{{ __('Title') }}</th>
                                <th class="py-3">{{ __('Content Preview') }}</th>
                                <th class="py-3 text-center" style="width: 100px;">{{ __('Status') }}</th>
                                <th class="py-3 text-center" style="width: 100px;">{{ __('Priority') }}</th>
                                <th class="pr-4 py-3 text-right" style="width: 130px;">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($announcements as $announcement)
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td class="pl-4 font-weight-semibold text-muted small align-middle">
                                    <i class="far fa-calendar text-primary mr-1"></i>
                                    {{ $announcement->announcement_date ? $announcement->announcement_date->format('M d, Y') : '-' }}
                                </td>
                                <td class="align-middle">
                                    <strong class="text-dark d-block" style="font-size: 0.95rem;">{{ $announcement->title }}</strong>
                                </td>
                                <td class="align-middle text-secondary small" style="max-width: 320px;">
                                    {{ Str::limit($announcement->body, 90) }}
                                </td>
                                <td class="align-middle text-center">
                                    @if($announcement->is_active)
                                        <span class="badge badge-success px-2 py-1" style="background-color: #10b981; font-weight: 600; border-radius: 6px;">
                                            <i class="fas fa-check-circle mr-1"></i>{{ __('Active') }}
                                        </span>
                                    @else
                                        <span class="badge badge-secondary px-2 py-1" style="border-radius: 6px;">
                                            {{ __('Inactive') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="align-middle text-center">
                                    @if($announcement->priority > 0)
                                        <span class="badge badge-warning px-2 py-1" style="font-weight: bold; border-radius: 6px;">
                                            <i class="fas fa-star text-dark"></i> {{ $announcement->priority }}
                                        </span>
                                    @else
                                        <span class="text-muted small">0</span>
                                    @endif
                                </td>
                                <td class="pr-4 align-middle text-right">
                                    @canany(['announcement-edit', 'announcement-delete'])
                                    <div class="btn-group">
                                        @can('announcement-edit')
                                        <a href="{{ route('announcements.edit', $announcement) }}" class="btn btn-sm btn-outline-primary" style="border-radius: 6px 0 0 6px;" title="{{ __('Edit') }}">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        @endcan
                                        @can('announcement-delete')
                                        <form action="{{ route('announcements.destroy', $announcement) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Are you sure?') }}');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" style="border-radius: 0 6px 6px 0; border-left: none;" title="{{ __('Delete') }}">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                        @endcan
                                    </div>
                                    @endcanany
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Pagination --}}
        <div class="mt-4 d-flex justify-content-center">
            {{ $announcements->links() }}
        </div>
    @else
        {{-- EMPTY STATE --}}
        <div class="card shadow-sm border-0 text-center py-5" style="border-radius: 12px;">
            <div class="card-body py-5">
                <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                    <i class="fas fa-bullhorn fa-2x text-muted"></i>
                </div>
                <h4 class="font-weight-bold text-dark">{{ __('Hakuna Matangazo Yoyote Bado') }}</h4>
                <p class="text-muted small mb-4" style="max-width: 400px; margin: 0 auto;">
                    {{ __('Bado hakuna matangazo yaliyowekwa kwenye mfumo. Bonyeza kitufe hapa chini ili kuongeza tangazo jipya.') }}
                </p>
                @can('announcement-create')
                <a href="{{ route('announcements.create') }}" class="btn btn-primary px-4 py-2 font-weight-bold shadow-sm" style="border-radius: 8px;">
                    <i class="fas fa-plus mr-1"></i> {{ __('Add Announcement') }}
                </a>
                @endcan
            </div>
        </div>
    @endif
</div>
@endsection
