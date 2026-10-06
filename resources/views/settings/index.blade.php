@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1 class="m-0">⚙️ System Settings</h1>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle mr-2"></i> {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="row">
        <div class="col-md-12">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-church mr-2"></i> General Configuration</h3>
                </div>
                <form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Church Name</label>
                                    <input type="text" name="church_name" class="form-control" value="{{ $settings['church_name'] ?? 'Hosanna International Church' }}" required>
                                </div>
                                <div class="form-group">
                                    <label>Church Slogan / Motto</label>
                                    <input type="text" name="church_slogan" class="form-control" value="{{ $settings['church_slogan'] ?? '' }}">
                                </div>
                                <div class="form-group">
                                    <label>Address</label>
                                    <textarea name="church_address" class="form-control" rows="3">{{ $settings['church_address'] ?? '' }}</textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Phone Number</label>
                                    <input type="text" name="church_phone" class="form-control" value="{{ $settings['church_phone'] ?? '' }}">
                                </div>
                                <div class="form-group">
                                    <label>Email Address</label>
                                    <input type="email" name="church_email" class="form-control" value="{{ $settings['church_email'] ?? '' }}">
                                </div>
                                <div class="form-group">
                                    <label>Currency Symbol</label>
                                    <input type="text" name="currency_symbol" class="form-control" value="{{ $settings['currency_symbol'] ?? 'TZS' }}">
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Current Logo</label><br>
                                    @if(isset($settings['church_logo']))
                                        @php
                                            $currLogo = str_starts_with($settings['church_logo'], 'images/') ? asset($settings['church_logo']) : asset('storage/' . $settings['church_logo']);
                                        @endphp
                                        <img src="{{ $currLogo }}" alt="Church Logo" style="max-height: 100px; border: 1px solid #ddd; padding: 5px; border-radius: 5px; background: #fff;">
                                    @else
                                        <img src="{{ asset('images/church-logo.png') }}" alt="Church Logo" style="max-height: 100px; border: 1px solid #ddd; padding: 5px; border-radius: 5px; background: #fff;">
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Upload New Logo</label>
                                    <div class="custom-file">
                                        <input type="file" name="church_logo" class="custom-file-input" id="customFile">
                                        <label class="custom-file-label" for="customFile">Choose file</label>
                                    </div>
                                    <small class="form-text text-muted">Recommended size: 200x200px (PNG/JPG)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i> Hifadhi Taarifa za Kanisa
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- SMS Gateway Configuration -->
    <div class="row mt-3">
        <div class="col-md-12">
            <div class="card card-success card-outline">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-sms mr-2"></i> Mfumo wa SMS (SMS Gate Android Gateway)
                    </h3>
                    <span class="badge badge-success px-3 py-2">
                        <i class="fas fa-signal mr-1"></i> Cloud API Active
                    </span>
                </div>
                <form action="{{ route('settings.update') }}" method="POST">
                    @csrf
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-2"></i> 
                            Taarifa hizi zimeunganishwa moja kwa moja na simu yako kupitia App ya <strong>SMS Gate</strong> (GitHub: capcom6/android-sms-gateway). SMS zote za kanisa (michango, ahadi, wageni, na ukumbusho wa Ibada ya Jumapili) zitatumwa kupitia simu hii.
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>SMS Gate Server Address</label>
                                    <input type="text" name="sms_gate_server_url" class="form-control" 
                                           value="{{ $settings['sms_gate_server_url'] ?? config('services.sms_gate.server_url', 'https://api.sms-gate.app') }}" required>
                                    <small class="text-muted">Kwa mfano: <code>https://api.sms-gate.app</code></small>
                                </div>
                                <div class="form-group">
                                    <label>Username (Jina la Mtumiaji)</label>
                                    <input type="text" name="sms_gate_username" class="form-control" 
                                           value="{{ $settings['sms_gate_username'] ?? config('services.sms_gate.username', '') }}" required>
                                </div>
                                <div class="form-group">
                                    <label>Password (Nenosiri)</label>
                                    <input type="text" name="sms_gate_password" class="form-control" 
                                           value="{{ $settings['sms_gate_password'] ?? config('services.sms_gate.password', '') }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Device ID (Kitambulisho cha Simu)</label>
                                    <input type="text" name="sms_gate_device_id" class="form-control" 
                                           value="{{ $settings['sms_gate_device_id'] ?? config('services.sms_gate.device_id', '') }}" required>
                                    <small class="text-muted">Kinaonyeshwa kwenye App ya simu ya SMS Gate</small>
                                </div>
                                <div class="form-group">
                                    <label>Chagua SIM Kadi ya Kutuma (Dual SIM)</label>
                                    <select name="sms_gate_sim_number" class="form-control">
                                        @php $currentSim = $settings['sms_gate_sim_number'] ?? config('services.sms_gate.sim_number', 1); @endphp
                                        <option value="1" {{ $currentSim == 1 ? 'selected' : '' }}>SIM 1 (Airtel TZ)</option>
                                        <option value="2" {{ $currentSim == 2 ? 'selected' : '' }}>SIM 2</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h5 class="text-primary"><i class="fas fa-calendar-day mr-2"></i> Mipangilio ya SMS za Mwaliko wa Ibada ya Jumapili (Hutumwa Jumamosi)</h5>
                        <p class="text-muted">Mfumo unatuma ujumbe huu moja kwa moja kila Jumamosi asubuhi (07:00 AM) kuwakaribisha waumini wote kwenye ibada ya Jumapili inayofuata.</p>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        @php $sabatoEnabled = ($settings['saturday_reminder_enabled'] ?? '1') != '0'; @endphp
                                        <input type="hidden" name="saturday_reminder_enabled" value="0">
                                        <input type="checkbox" name="saturday_reminder_enabled" value="1" 
                                               class="custom-control-input" id="sabatoSwitch" {{ $sabatoEnabled ? 'checked' : '' }}>
                                        <label class="custom-control-label font-weight-bold" for="sabatoSwitch">
                                            Wezesha Mfumo Kutuma SMS Kiotomatiki Kila Jumamosi Asubuhi (07:00 AM)
                                        </label>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Ujumbe Utakaotumwa (Template)</label>
                                    <textarea name="saturday_reminder_message" class="form-control" rows="3">{{ $settings['saturday_reminder_message'] ?? 'Bwana asifiwe {name}! Tunakukaribisha kwa furaha wewe na familia yako katika Ibada ya Jumapili kesho hapa {church_name}. Karibu tujumuike pamoja kumwabudu Mungu na kupokea baraka zake. Ibada inaanza saa 3:00 asubuhi.' }}</textarea>
                                    <small class="text-muted">
                                        Unaweza kutumia vitambulisho: <code>{name}</code> kwa ajili ya jina la muumini, na <code>{church_name}</code> kwa ajili ya jina la kanisa.
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer d-flex justify-content-between align-items-center">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save mr-1"></i> Hifadhi Mipangilio ya SMS
                        </button>
                    </div>
                </form>
                
                <!-- SMS Gateway Testing Box -->
                <div class="card-footer bg-light border-top">
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <form action="{{ route('settings.test-sms') }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-outline-info">
                                    <i class="fas fa-network-wired mr-1"></i> Kagua Muunganisho wa SMS Gate (Test Connection)
                                </button>
                            </form>
                        </div>
                        <div class="col-md-6">
                            <form action="{{ route('settings.test-sms') }}" method="POST" class="form-inline justify-content-md-end">
                                @csrf
                                <div class="input-group">
                                    <input type="text" name="test_phone" class="form-control" placeholder="07XXXXXXXX" required>
                                    <div class="input-group-append">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-paper-plane mr-1"></i> Tuma SMS ya Jaribio
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection
