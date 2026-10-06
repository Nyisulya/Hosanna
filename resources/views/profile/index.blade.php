@extends('layouts.admin')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark">{{ __('My Profile') }}</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
                    <li class="breadcrumb-item active">{{ __('Profile') }}</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="content">
    <div class="container-fluid">
        <!-- Success/Warning Messages -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show shadow-sm">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
            </div>
        @endif

        @if(session('warning'))
            <div class="alert alert-warning alert-dismissible fade show shadow-sm">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <i class="fas fa-exclamation-circle mr-1"></i> {{ session('warning') }}
            </div>
        @endif

        <div class="row">
            <!-- Left Column: Profile Card & QR Code -->
            <div class="col-lg-4 col-md-5">
                <!-- Basic Profile Card -->
                <div class="card card-primary card-outline shadow-sm text-center">
                    <div class="card-body box-profile">
                        <div class="text-center mb-3">
                            @if($member && $member->profile_photo)
                                <img src="{{ asset('storage/' . $member->profile_photo) }}" 
                                     alt="Profile Photo" 
                                     class="profile-user-img img-fluid img-circle shadow"
                                     style="width: 140px; height: 140px; object-fit: cover; border: 3px solid #adb5bd;">
                            @else
                                <div class="profile-user-img img-fluid img-circle bg-primary d-inline-flex align-items-center justify-content-center shadow" 
                                     style="width: 140px; height: 140px; border: 3px solid #adb5bd;">
                                    <i class="fas fa-user fa-4x text-white"></i>
                                </div>
                            @endif
                        </div>
                        <h3 class="profile-username text-center font-weight-bold mb-1">{{ $user->name }}</h3>
                        
                        @if($member)
                            <p class="text-muted text-center mb-2">
                                <i class="fas fa-id-card mr-1"></i> {{ $member->member_number }}
                            </p>
                            <div class="text-center mb-4">
                                <span class="badge badge-{{ $member->status === 'active' ? 'success' : 'secondary' }} px-3 py-2 text-sm text-uppercase">
                                    {{ $member->status }}
                                </span>
                            </div>
                        @endif

                        <a href="{{ route('profile.edit') }}" class="btn btn-primary btn-block font-weight-bold">
                            <i class="fas fa-edit mr-1"></i> {{ __('Edit Profile') }}
                        </a>
                    </div>
                </div>

            </div>

            <!-- Right Column: Detailed Information -->
            <div class="col-lg-8 col-md-7 mt-3 mt-md-0">
                @if($member)
                    <!-- Contact & Personal Information Card -->
                    <div class="card card-primary card-outline shadow-sm">
                        <div class="card-header bg-light">
                            <h3 class="card-title font-weight-bold text-primary">
                                <i class="fas fa-user-circle mr-1"></i> {{ __('Contact & Personal Information') }}
                            </h3>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-striped table-hover mb-0">
                                <tbody>
                                    <tr>
                                        <td width="35%"><strong><i class="fas fa-envelope mr-2 text-muted"></i> {{ __('Email') }}</strong></td>
                                        <td>{{ $member->email }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong><i class="fas fa-phone mr-2 text-muted"></i> {{ __('Phone') }}</strong></td>
                                        <td>{{ $member->phone ?? __('Not provided') }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong><i class="fas fa-map-marker-alt mr-2 text-muted"></i> {{ __('Address') }}</strong></td>
                                        <td>{{ $member->address ?? __('Not provided') }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong><i class="fas fa-venus-mars mr-2 text-muted"></i> {{ __('Gender') }}</strong></td>
                                        <td class="text-capitalize">{{ $member->gender }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong><i class="fas fa-birthday-cake mr-2 text-muted"></i> {{ __('Date of Birth') }}</strong></td>
                                        <td>
                                            {{ $member->date_of_birth ? $member->date_of_birth->format('F d, Y') : __('Not provided') }}
                                            @if($member->date_of_birth)
                                                <span class="badge badge-info ml-2">{{ $member->age }} {{ __('years old') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong><i class="fas fa-heart mr-2 text-muted"></i> {{ __('Marital Status') }}</strong></td>
                                        <td class="text-capitalize">{{ $member->marital_status }}</td>
                                    </tr>
                                    @if($member->wedding_date)
                                        <tr>
                                            <td><strong><i class="fas fa-ring mr-2 text-muted"></i> {{ __('Wedding Date') }}</strong></td>
                                            <td>
                                                {{ $member->wedding_date->format('F d, Y') }}
                                                <span class="badge badge-success ml-2">{{ $member->years_married }} {{ __('years married') }}</span>
                                            </td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Departments & Ministries Card -->
                    @if($member->departments && $member->departments->count() > 0)
                        <div class="card card-primary card-outline shadow-sm mt-3">
                            <div class="card-header bg-light">
                                <h3 class="card-title font-weight-bold text-primary">
                                    <i class="fas fa-users mr-1"></i> {{ __('Departments & Ministries') }}
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    @foreach($member->departments as $department)
                                        <div class="col-md-6 col-sm-12 mb-3">
                                            <div class="info-box bg-light border shadow-sm mb-0">
                                                <span class="info-box-icon bg-info elevation-1"><i class="fas fa-church"></i></span>
                                                <div class="info-box-content">
                                                    <span class="info-box-text font-weight-bold text-md mb-1">{{ $department->name }}</span>
                                                    <span class="info-box-number text-muted text-sm font-weight-normal">
                                                        Role: <span class="text-capitalize">{{ $department->pivot->role ?? 'member' }}</span>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                @else
                    <!-- Warning Card if Profile incomplete -->
                    <div class="alert alert-warning p-4 shadow-sm border-0">
                        <h5 class="font-weight-bold"><i class="icon fas fa-exclamation-triangle mr-1"></i> {{ __('Complete Your Profile') }}</h5>
                        <p class="mb-3">{{ __('Your account is not linked to a member profile yet. Please complete your profile to access all features and get your QR code for attendance.') }}</p>
                        <a href="{{ route('profile.edit') }}" class="btn btn-primary font-weight-bold text-white shadow">
                            <i class="fas fa-user-plus mr-1"></i> {{ __('Complete Your Profile Now') }}
                        </a>
                    </div>
                @endif
            </div>
        </div>
        
        <!-- Footer Card -->
        <div class="card shadow-sm mt-3">
            <div class="card-footer bg-white">
                <div class="row">
                    <div class="col-sm-6 text-center text-sm-left">
                        <small class="text-muted">
                            <i class="fas fa-clock mr-1"></i>
                            Member since: {{ $user->created_at->format('F Y') }}
                        </small>
                    </div>
                    <div class="col-sm-6 text-center text-sm-right mt-2 mt-sm-0">
                        @if(auth()->user()->hasAnyRole(['super_admin', 'admin', 'pastor']))
                            <a href="{{ route('dashboard') }}" class="btn btn-secondary btn-sm shadow-sm">
                                <i class="fas fa-arrow-left mr-1"></i> Back to Dashboard
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
