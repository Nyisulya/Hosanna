@extends('layouts.admin')

@section('content')
<div class="container-fluid py-3">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-md-10">
            <div class="card shadow-sm border-0" style="border-radius: 12px; overflow: hidden;">
                <div class="card-header bg-gradient-primary py-3 text-white" style="background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);">
                    <h5 class="card-title font-weight-bold mb-0 text-white">
                        <i class="fas fa-plus-circle mr-2"></i> {{ __('Add New Announcement (Tangazo Jipya)') }}
                    </h5>
                </div>
                <form action="{{ route('announcements.store') }}" method="POST">
                    @csrf
                    <div class="card-body p-4">
                        <div class="form-group mb-3">
                            <label class="font-weight-bold text-dark">{{ __('Title / Kichwa cha Tangazo') }} <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control form-control-lg" style="border-radius: 8px;" value="{{ old('title') }}" required placeholder="Mfano: Mkutano wa Vijana / Semina ya Ndoa">
                            @error('title')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label class="font-weight-bold text-dark">{{ __('Content (Maudhui ya Tangazo)') }} <span class="text-danger">*</span></label>
                            <textarea name="body" class="form-control" rows="6" style="border-radius: 8px;" required placeholder="Andika maelezo kamili ya tangazo hapa...">{{ old('body') }}</textarea>
                            @error('body')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-group">
                                    <label class="font-weight-bold text-dark">{{ __('Announcement Date / Tarehe') }} <span class="text-danger">*</span></label>
                                    <input type="date" name="announcement_date" class="form-control" style="border-radius: 8px;" value="{{ old('announcement_date', date('Y-m-d')) }}" required>
                                    @error('announcement_date')
                                        <span class="text-danger small">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-group">
                                    <label class="font-weight-bold text-dark">{{ __('Priority / Kipaumbele (0-10)') }}</label>
                                    <input type="number" name="priority" class="form-control" style="border-radius: 8px;" value="{{ old('priority', 0) }}" min="0" max="10">
                                    <small class="text-muted">Kipaumbele cha juu kitaonekana mwanzo</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group p-3 bg-light rounded" style="border-radius: 8px;">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" checked>
                                <label class="custom-control-label font-weight-bold text-dark" for="is_active">{{ __('Active (Onyesha kwenye matangazo ya kanisa)') }}</label>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-light p-3 d-flex justify-content-between align-items-center">
                        <a href="{{ route('announcements.index') }}" class="btn btn-outline-secondary px-4 font-weight-bold" style="border-radius: 8px;">
                            <i class="fas fa-times mr-1"></i> {{ __('Cancel') }}
                        </a>
                        <button type="submit" class="btn btn-primary px-4 py-2 font-weight-bold shadow-sm" style="border-radius: 8px; background-color: #1e3a8a;">
                            <i class="fas fa-save mr-1"></i> {{ __('Save Announcement') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
