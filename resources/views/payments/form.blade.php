@extends('layouts.admin')

@section('title', 'Lipa Sadaka Mtandaoni (M-Pesa, Tigo Pesa, Airtel Money, HaloPesa)')

@push('styles')
<style>
    .payment-pills .nav-link {
        background-color: #ffffff !important;
        color: #495057 !important;
        border: 2px solid #dee2e6 !important;
        border-radius: 12px !important;
        font-size: 1.15rem !important;
        font-weight: 700 !important;
        padding: 16px 20px !important;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05) !important;
        transition: all 0.3s ease !important;
        text-align: center;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .payment-pills .nav-link.active {
        background: linear-gradient(135deg, #007bff, #0056b3) !important;
        color: #ffffff !important;
        border-color: #0056b3 !important;
        box-shadow: 0 6px 12px rgba(0,123,255,0.25) !important;
    }
    .payment-pills .nav-link:hover:not(.active) {
        background-color: #f8f9fa !important;
        color: #007bff !important;
        border-color: #007bff !important;
        transform: translateY(-2px);
    }
    .network-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 700;
        box-shadow: 0 2px 4px rgba(0,0,0,0.08);
    }
    .net-mpesa { background: #e60000; color: #fff; }
    .net-tigo { background: #00377b; color: #fff; }
    .net-airtel { background: #ff0000; color: #fff; }
    .net-halopesa { background: #ff6600; color: #fff; }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center mt-4">
        <div class="col-md-8">
            <div class="card card-primary card-outline shadow-sm">
                <div class="card-header p-0 border-bottom-0">
                    <div class="px-4 pt-4">
                        <div class="d-flex flex-wrap justify-content-between align-items-center">
                            <div>
                                <h4 class="m-0 font-weight-bold text-dark">
                                    <i class="fas fa-hand-holding-usd text-success mr-2"></i> Sadaka Mtandaoni (Mobile Money)
                                </h4>
                                <p class="text-muted small mt-1 mb-2">Wasilisha Zaka, Sadaka au Ahadi moja kwa moja kwenye simu yako kupitia HarakaPay</p>
                            </div>
                            <div class="mb-2">
                                <span class="badge badge-success px-3 py-2" style="font-size: 0.85rem;">
                                    <i class="fas fa-bolt mr-1"></i> USSD Push Papo Hapo
                                </span>
                            </div>
                        </div>

                        <!-- Network Logos -->
                        <div class="d-flex flex-wrap mb-3" style="gap: 8px;">
                            <span class="network-pill net-mpesa"><i class="fas fa-mobile-alt"></i> M-Pesa</span>
                            <span class="network-pill net-tigo"><i class="fas fa-mobile-alt"></i> Tigo Pesa</span>
                            <span class="network-pill net-airtel"><i class="fas fa-mobile-alt"></i> Airtel Money</span>
                            <span class="network-pill net-halopesa"><i class="fas fa-mobile-alt"></i> HaloPesa</span>
                        </div>
                    </div>
                    
                    <!-- Redesigned Two Choice Buttons -->
                    <ul class="nav nav-pills nav-fill payment-pills px-4 pb-3" id="paymentTabs" role="tablist" style="gap: 15px;">
                        <li class="nav-item">
                            <a class="nav-link active" id="general-tab" data-toggle="pill" href="#general-pane" role="tab" aria-selected="true">
                                <i class="fas fa-church mr-2"></i> Sadaka & Zaka
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="pledge-tab" data-toggle="pill" href="#pledge-pane" role="tab" aria-selected="false">
                                <i class="fas fa-award mr-2"></i> Ahadi Zangu (Pledges)
                            </a>
                        </li>
                    </ul>
                </div>
                
                <form action="{{ route('give.process') }}" method="POST" id="payment-form">
                    @csrf
                    
                    <!-- Hidden field to pass payment type -->
                    <input type="hidden" name="payment_type" id="payment_type" value="general">
                    
                    <div class="card-body mt-2">
                        @if (session('error'))
                            <div class="alert alert-danger shadow-sm">
                                <i class="fas fa-exclamation-triangle mr-1"></i> {{ session('error') }}
                            </div>
                        @endif

                        @if (session('success'))
                            <div class="alert alert-success shadow-sm">
                                <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
                            </div>
                        @endif

                        <div class="tab-content" id="paymentTabsContent">
                            
                            <!-- GENERAL GIVING PANE -->
                            <div class="tab-pane fade show active" id="general-pane" role="tabpanel">
                                <div class="form-group">
                                    <label for="category" class="font-weight-bold">Aina ya Sadaka / Mchango <span class="text-danger">*</span></label>
                                    <select name="category" id="category" class="form-control form-control-lg" required>
                                        <option value="">-- Chagua Aina ya Sadaka --</option>
                                        @forelse($categories as $cat)
                                            <option value="{{ $cat->name }}">{{ $cat->name }}</option>
                                        @empty
                                            <option value="Sadaka ya Jumapili">Sadaka ya Jumapili (Sunday Offering)</option>
                                            <option value="Zaka">Zaka (Tithe)</option>
                                            <option value="Sadaka ya Sunday School">Sadaka ya Sunday School</option>
                                            <option value="Mfuko wa Ujenzi">Mfuko wa Ujenzi (Building Fund)</option>
                                            <option value="Sadaka ya Uinjilisti & Misheni">Sadaka ya Uinjilisti & Misheni</option>
                                            <option value="Sadaka ya Semina & Mikutano">Sadaka ya Semina & Mikutano</option>
                                            <option value="Shukrani">Sadaka ya Shukrani (Thanksgiving)</option>
                                        @endforelse
                                    </select>
                                    <span class="text-muted small">Chagua mfuko au kundi ambalo mchango wako utawekwa.</span>
                                </div>
                            </div>
                            
                            <!-- PLEDGE PANE -->
                            <div class="tab-pane fade" id="pledge-pane" role="tabpanel">
                                <div class="form-group">
                                    <label for="pledge_id" class="font-weight-bold">Chagua Ahadi Yako <span class="text-danger">*</span></label>
                                    <select name="pledge_id" id="pledge_id" class="form-control form-control-lg" onchange="updatePledgeDetails(this)">
                                        <option value="">-- Chagua Ahadi ya Kulipia --</option>
                                        @forelse($pledges as $pledge)
                                            <option value="{{ $pledge->id }}" data-balance="{{ $pledge->remaining_balance }}">
                                                {{ $pledge->purpose }} (Bao: TSh {{ number_format($pledge->remaining_balance) }})
                                            </option>
                                        @empty
                                            <option value="" disabled>Huna ahadi zilizo hai kwa sasa.</option>
                                        @endforelse
                                    </select>
                                    <div id="pledge-alert" class="alert alert-warning mt-2 d-none">
                                        <small><i class="fas fa-info-circle mr-1"></i> Kiasi kilichobaki kulipwa kwenye ahadi hii: <strong>TSh <span id="pledge-balance-text">0</span></strong></small>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- COMMON FIELDS -->
                        <div class="row mt-4 pt-3 border-top">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="amount" class="font-weight-bold">Kiasi cha Fedha (TZS) <span class="text-danger">*</span></label>
                                    <div class="input-group input-group-lg shadow-sm">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text font-weight-bold bg-light">TSh</span>
                                        </div>
                                        <input type="number" name="amount" id="amount" class="form-control font-weight-bold text-success" 
                                               step="100" min="100" required placeholder="Mfano: 5000" oninput="updateButtonAmount()">
                                    </div>
                                    <span class="text-muted small">Kiwango cha chini ni TSh 100</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="phone_number" class="font-weight-bold">Namba ya Simu ya Malipo <span class="text-danger">*</span></label>
                                    <div class="input-group input-group-lg shadow-sm">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text bg-light"><i class="fas fa-phone"></i></span>
                                        </div>
                                        <input type="text" name="phone_number" id="phone_number" class="form-control form-control-lg font-weight-bold" 
                                               placeholder="07XXXXXXXX au 06XXXXXXXX" required
                                               value="{{ Auth::user()->member->phone ?? '' }}">
                                    </div>
                                    <span class="text-muted small">Namba ya Vodacom (M-Pesa), Tigo, Airtel, au Halotel itakayopokea USSD Push ya PIN.</span>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="description" class="font-weight-bold">Maelezo ya Ziada (Hiari)</label>
                                    <textarea name="description" id="description" class="form-control" rows="2" placeholder="Andika maelezo mafupi kama yapo..."></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-light border shadow-sm mb-0 mt-3 p-3">
                            <div class="d-flex align-items-center">
                                <div class="mr-3 text-success">
                                    <i class="fas fa-shield-alt fa-2x"></i>
                                </div>
                                <div class="small text-muted">
                                    <strong class="text-dark d-block">Jinsi Malipo Yanavyofanya Kazi (HarakaPay USSD Push):</strong>
                                    Ukibonyeza kitufe hapa chini, simu yako itawaka papo hapo na kukuuliza PIN ya mtandao wako. Ukishaweka PIN, sadaka yako itarekodiwa kiotomatiki na utatumiwa ujumbe wa SMS wa shukrani.
                                </div>
                            </div>
                        </div>

                    </div>
                    
                    <div class="card-footer bg-white border-top">
                        <button type="submit" class="btn btn-success btn-lg btn-block py-3 font-weight-bold shadow-sm" id="btn-submit-give">
                            <i class="fas fa-mobile-alt mr-2"></i> LIPA PAPO HAPO KWA SIMU (USSD PUSH) <span id="display-amount"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function updateButtonAmount() {
        var val = $('#amount').val();
        if (val && parseFloat(val) > 0) {
            $('#display-amount').text('- TSh ' + new Intl.NumberFormat().format(val));
        } else {
            $('#display-amount').text('');
        }
    }

    $(document).ready(function() {
        // Tab click event to change hidden input payment_type
        $('a[data-toggle="pill"]').on('shown.bs.tab', function (e) {
            var targetId = $(e.target).attr('id');
            var paymentType = 'general';
            
            if (targetId === 'pledge-tab') {
                paymentType = 'pledge';
                $('#category').prop('required', false);
                $('#pledge_id').prop('required', true);
            } else {
                paymentType = 'general';
                $('#category').prop('required', true);
                $('#pledge_id').prop('required', false);
            }
            
            $('#payment_type').val(paymentType);
        });

        // Pre-select tab if preselectedPledge exists
        @if(isset($preselectedPledge))
            $('#pledge-tab').tab('show');
            $('#pledge_id').val('{{ $preselectedPledge->id }}').trigger('change');
            $('#amount').val('{{ (int)$preselectedPledge->remaining_balance }}');
        @endif

        updateButtonAmount();

        // Form submission loading state
        $('#payment-form').on('submit', function() {
            var btn = $('#btn-submit-give');
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> INATUMA OMBI KWENYE SIMU YAKO...');
        });
    });

    function updatePledgeDetails(select) {
        var selected = $(select).find('option:selected');
        var balance = selected.data('balance');
        
        if (balance) {
            $('#pledge-balance-text').text(new Intl.NumberFormat().format(balance));
            $('#pledge-alert').removeClass('d-none');
            $('#amount').val(balance);
            updateButtonAmount();
        } else {
            $('#pledge-alert').addClass('d-none');
        }
    }
</script>
@endpush
@endsection
