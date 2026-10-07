@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-sm-6">
            <h1 class="m-0"><i class="fas fa-user-plus text-primary mr-2"></i> {{ __('Sajili Mshiriki Mpya') }}</h1>
        </div>
        <div class="col-sm-6 text-right">
            <a href="{{ route('members.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-1"></i> {{ __('Rudi Kwenye Orodha') }}
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <h5><i class="icon fas fa-ban mr-1"></i> Hitilafu kwenye usajili!</h5>
            <ul class="mb-0 pl-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="row">
        <div class="col-md-12">
            <div class="card card-primary card-outline shadow-sm">
                <div class="card-header">
                    <h3 class="card-title font-weight-bold">Taarifa za Mshiriki</h3>
                </div>
                <form action="{{ route('members.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="card-body">
                        <div class="row">
                            <!-- Full Name -->
                            <div class="col-md-6 form-group">
                                <label class="font-weight-bold">{{ __('Jina Kamili') }} <span class="text-danger">*</span></label>
                                <input type="text" name="full_name" class="form-control @error('full_name') is-invalid @enderror" 
                                       value="{{ old('full_name') }}" placeholder="Mf. Emmanuel John Kileo" required>
                                @error('full_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Phone Number -->
                            <div class="col-md-6 form-group">
                                <label class="font-weight-bold">{{ __('Namba ya Simu') }}</label>
                                <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" 
                                       value="{{ old('phone') }}" placeholder="Mf. 0712345678 au +255...">
                                <small class="text-muted">Itatumika kutuma SMS za mwaliko wa ibada, ahadi na risiti.</small>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Email -->
                            <div class="col-md-6 form-group">
                                <label class="font-weight-bold">{{ __('Barua Pepe (Email)') }}</label>
                                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" 
                                       value="{{ old('email') }}" placeholder="Mf. jina@gmail.com (Hiari)">
                                <small class="text-muted">Ukiacha wazi, mfumo utamtengenezea email ya kiotomatiki kwa ajili ya akaunti.</small>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Password -->
                            <div class="col-md-6 form-group">
                                <label class="font-weight-bold">{{ __('Nenosiri la Kuingilia (Password)') }}</label>
                                <div class="input-group">
                                    <input type="password" name="password" id="create_password" 
                                           class="form-control @error('password') is-invalid @enderror" 
                                           placeholder="Chaguo-msingi ni password123">
                                    <div class="input-group-append">
                                        <span class="input-group-text cursor-pointer" id="toggleCreatePassword" style="cursor: pointer;">
                                            <i class="fas fa-eye text-muted"></i>
                                        </span>
                                    </div>
                                </div>
                                <small class="text-muted">Ukiacha wazi, nenosiri litakuwa: <code>password123</code></small>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Member Role / Type -->
                            <div class="col-md-4 form-group">
                                <label class="font-weight-bold">{{ __('Nafasi / Wadhifa') }} <span class="text-danger">*</span></label>
                                <select name="member_type" class="form-control" required>
                                    <option value="member" {{ old('member_type') == 'member' ? 'selected' : '' }}>Mshiriki wa Kawaida (Member)</option>
                                    <option value="pastor" {{ old('member_type') == 'pastor' ? 'selected' : '' }}>Mchungaji (Pastor)</option>
                                    <option value="secretary" {{ old('member_type') == 'secretary' ? 'selected' : '' }}>Katibu wa Kanisa (Secretary)</option>
                                    <option value="deacon" {{ old('member_type') == 'deacon' ? 'selected' : '' }}>Shemasi (Deacon)</option>
                                    <option value="department_leader" {{ old('member_type') == 'department_leader' ? 'selected' : '' }}>Kiongozi wa Idara (Leader)</option>
                                    <option value="treasurer" {{ old('member_type') == 'treasurer' ? 'selected' : '' }}>Mweka Hazina (Treasurer)</option>
                                    <option value="accountant" {{ old('member_type') == 'accountant' ? 'selected' : '' }}>Mhasibu (Accountant)</option>
                                </select>
                            </div>

                            <!-- Registration Type -->
                            <div class="col-md-4 form-group">
                                <label class="font-weight-bold">{{ __('Aina ya Uanachama') }}</label>
                                <select name="registration_type" class="form-control">
                                    <option value="Mshiriki Rasmi" {{ old('registration_type') == 'Mshiriki Rasmi' ? 'selected' : '' }}>Mshiriki Rasmi (Official Member)</option>
                                    <option value="Muumini wa Kawaida" {{ old('registration_type') == 'Muumini wa Kawaida' ? 'selected' : '' }}>Muumini / Mhudhuriaji (Congregant)</option>
                                </select>
                            </div>

                            <!-- Gender -->
                            <div class="col-md-4 form-group">
                                <label class="font-weight-bold">{{ __('Jinsia (Gender)') }}</label>
                                <select name="gender" class="form-control">
                                    <option value="">-- Chagua Jinsia --</option>
                                    <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Mwanaume (Male)</option>
                                    <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Mwanamke (Female)</option>
                                    <option value="other" {{ old('gender') == 'other' ? 'selected' : '' }}>Nyingine (Other)</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Date of Birth -->
                            <div class="col-md-4 form-group">
                                <label class="font-weight-bold">{{ __('Tarehe ya Kuzaliwa') }}</label>
                                <input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth') }}">
                            </div>

                            <!-- Marital Status -->
                            <div class="col-md-4 form-group">
                                <label class="font-weight-bold">{{ __('Hali ya Ndoa') }}</label>
                                <select name="marital_status" class="form-control">
                                    <option value="">-- Chagua Hali ya Ndoa --</option>
                                    <option value="single" {{ old('marital_status') == 'single' ? 'selected' : '' }}>Hajaoa/Hajaolewa (Single)</option>
                                    <option value="married" {{ old('marital_status') == 'married' ? 'selected' : '' }}>Ameoa/Ameolewa (Married)</option>
                                    <option value="widowed" {{ old('marital_status') == 'widowed' ? 'selected' : '' }}>Mjane (Widowed)</option>
                                    <option value="divorced" {{ old('marital_status') == 'divorced' ? 'selected' : '' }}>Ameachika (Divorced)</option>
                                </select>
                            </div>

                            <!-- Status -->
                            <div class="col-md-4 form-group">
                                <label class="font-weight-bold">{{ __('Hali ya Ushiriki') }}</label>
                                <select name="status" class="form-control">
                                    <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Hai (Active)</option>
                                    <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Si Hai (Inactive)</option>
                                    <option value="pending" {{ old('status') == 'pending' ? 'selected' : '' }}>Inasubiri (Pending)</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Address -->
                            <div class="col-md-12 form-group">
                                <label class="font-weight-bold">{{ __('Mahali Anapoishi / Anuani (Address)') }}</label>
                                <textarea name="address" class="form-control" rows="2" placeholder="Mf. Manzese Makorora, Nyumba Na. 45">{{ old('address') }}</textarea>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Salvation Date -->
                            <div class="col-md-4 form-group">
                                <label class="font-weight-bold">{{ __('Tarehe ya Kuokoka (Salvation Date)') }}</label>
                                <input type="date" name="salvation_date" class="form-control" value="{{ old('salvation_date') }}">
                            </div>

                            <!-- Baptism Date -->
                            <div class="col-md-4 form-group">
                                <label class="font-weight-bold">{{ __('Tarehe ya Ubatizo (Baptism Date)') }}</label>
                                <input type="date" name="baptism_date" class="form-control" value="{{ old('baptism_date') }}">
                            </div>

                            <!-- Emergency Contact -->
                            <div class="col-md-4 form-group">
                                <label class="font-weight-bold">{{ __('Simu ya Mtu wa Karibu (Dharura)') }}</label>
                                <input type="text" name="emergency_contact_phone" class="form-control" 
                                       value="{{ old('emergency_contact_phone') }}" placeholder="Mf. 07XXXXXXXX">
                            </div>
                        </div>

                        <div class="row">
                            <!-- Profile Photo -->
                            <div class="col-md-6 form-group">
                                <label class="font-weight-bold">{{ __('Picha ya Mshiriki') }}</label>
                                <div class="custom-file">
                                    <input type="file" name="profile_photo" class="custom-file-input" id="profilePhotoInput" accept="image/*">
                                    <label class="custom-file-label" for="profilePhotoInput">Chagua picha...</label>
                                </div>
                                <small class="text-muted">PNG, JPG au JPEG isiyozidi 2MB.</small>
                            </div>

                            <!-- Departments -->
                            <div class="col-md-6 form-group">
                                <label class="font-weight-bold mb-2">{{ __('Idara / Huduma za Kanisa') }}</label>
                                <div class="p-2 border rounded bg-light" style="max-height: 150px; overflow-y: auto;">
                                    @php $oldDepts = old('departments', []); @endphp
                                    @foreach($departments as $department)
                                        <div class="custom-control custom-checkbox mb-1">
                                            <input type="checkbox" name="departments[]" value="{{ $department->id }}" 
                                                   id="dept_{{ $department->id }}" class="custom-control-input"
                                                   {{ in_array($department->id, $oldDepts) ? 'checked' : '' }}>
                                            <label class="custom-control-label font-weight-normal" for="dept_{{ $department->id }}">
                                                {{ $department->name }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="card-footer bg-light d-flex justify-content-between">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fas fa-save mr-1"></i> Hifadhi Mshiriki
                        </button>
                        <a href="{{ route('members.index') }}" class="btn btn-default">
                            Ghairi
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.getElementById('toggleCreatePassword').addEventListener('click', function (e) {
        const password = document.getElementById('create_password');
        const icon = this.querySelector('i');
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
        icon.classList.toggle('fa-eye');
        icon.classList.toggle('fa-eye-slash');
    });

    // Display filename on file input change
    $('#profilePhotoInput').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').html(fileName || 'Chagua picha...');
    });
</script>
@endpush
