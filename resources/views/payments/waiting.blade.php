@extends('layouts.admin')

@section('title', 'Kamilisha Malipo Kwenye Simu')

@push('styles')
<style>
    .pulse-container {
        position: relative;
        width: 110px;
        height: 110px;
        margin: 0 auto 20px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .pulse-ring {
        position: absolute;
        width: 100%;
        height: 100%;
        border-radius: 50%;
        border: 4px solid #28a745;
        animation: pulseAnimation 2s cubic-bezier(0.215, 0.61, 0.355, 1) infinite;
        opacity: 0;
    }
    .pulse-ring:nth-child(2) {
        animation-delay: 0.6s;
    }
    .pulse-ring:nth-child(3) {
        animation-delay: 1.2s;
    }
    .pulse-icon {
        width: 76px;
        height: 76px;
        background: linear-gradient(135deg, #28a745, #1e7e34);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        font-size: 34px;
        box-shadow: 0 8px 20px rgba(40, 167, 69, 0.35);
        z-index: 2;
    }
    @keyframes pulseAnimation {
        0% {
            transform: scale(0.6);
            opacity: 0.9;
        }
        50% {
            opacity: 0.5;
        }
        100% {
            transform: scale(1.4);
            opacity: 0;
        }
    }
    .operator-badge {
        display: inline-flex;
        align-items: center;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 700;
        margin: 2px 4px;
    }
    .badge-mpesa { background-color: #e60000; color: #fff; }
    .badge-tigo { background-color: #00377b; color: #fff; }
    .badge-airtel { background-color: #ff0000; color: #fff; }
    .badge-halopesa { background-color: #ff6600; color: #fff; }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center mt-4">
        <div class="col-md-7 col-lg-6">
            <div class="card card-outline card-success shadow-lg text-center">
                <div class="card-body py-5 px-4">
                    
                    <!-- Animated Pulse Radar -->
                    <div class="pulse-container">
                        <div class="pulse-ring"></div>
                        <div class="pulse-ring"></div>
                        <div class="pulse-ring"></div>
                        <div class="pulse-icon" id="status-icon-box">
                            <i class="fas fa-mobile-alt" id="status-icon"></i>
                        </div>
                    </div>

                    <h3 class="font-weight-bold text-dark mb-1">Angalia Simu Yako!</h3>
                    <p class="text-muted mb-3">Ombi la malipo (USSD Push) limetumwa kwa njia ya Mobile Money</p>

                    <!-- Supported Network Tags -->
                    <div class="mb-4">
                        <span class="operator-badge badge-mpesa">M-Pesa</span>
                        <span class="operator-badge badge-tigo">Tigo Pesa</span>
                        <span class="operator-badge badge-airtel">Airtel Money</span>
                        <span class="operator-badge badge-halopesa">HaloPesa</span>
                    </div>

                    <!-- Payment Summary Box -->
                    <div class="bg-light p-4 rounded text-left shadow-sm mb-4 mx-auto" style="max-width: 480px; border-left: 5px solid #28a745;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted">Aina ya Sadaka:</span>
                            <strong class="text-dark">{{ $payment->category }}</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted">Kiasi cha Kulipa:</span>
                            <span class="h4 font-weight-bold text-success mb-0">TSh {{ number_format($payment->amount) }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted">Namba ya Simu:</span>
                            <strong class="text-primary"><i class="fas fa-phone mr-1"></i> {{ $payment->phone_number }}</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Namba ya Kumbukumbu:</span>
                            <span class="badge badge-secondary font-weight-normal text-monospace">{{ $payment->reference }}</span>
                        </div>
                    </div>

                    <!-- Instructions Card -->
                    <div class="alert alert-warning text-left shadow-sm mx-auto mb-4" style="max-width: 480px;">
                        <h6 class="font-weight-bold mb-2 text-dark"><i class="fas fa-key text-warning mr-1"></i> Hatua za Kukamilisha:</h6>
                        <ol class="pl-3 mb-0 text-muted small" style="line-height: 1.6;">
                            <li>Simu yako itawaka na kuonyesha ombi la kutoa <strong>TSh {{ number_format($payment->amount) }}</strong> kwenda HarakaPay.</li>
                            <li>Weka <strong>Namba yako ya Siri (PIN)</strong> kwenye simu yako kukubali.</li>
                            <li>Baada ya kuweka PIN, mfumo utatambua malipo mara moja bila wewe kufanya kitu chochote!</li>
                        </ol>
                    </div>

                    <!-- Status Indicator & Timer -->
                    <div class="mb-4">
                        <div class="d-flex align-items-center justify-content-center text-primary font-weight-bold mb-2">
                            <div class="spinner-border spinner-border-sm mr-2" role="status" id="polling-spinner"></div>
                            <span id="status-message">Inasubiri uthibitisho wa PIN kwenye simu yako...</span>
                        </div>
                        <small class="text-muted">Muda uliobaki: <strong id="countdown-text" class="text-dark">90s</strong></small>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex justify-content-center" style="gap: 12px;">
                        <button type="button" class="btn btn-outline-success font-weight-bold px-4" id="btn-manual-check" onclick="pollPaymentStatus(true)">
                            <i class="fas fa-sync-alt mr-1"></i> Nimeshaweka PIN (Kagua Sasa)
                        </button>
                        <a href="{{ route('give.form') }}" class="btn btn-outline-secondary px-3">
                            <i class="fas fa-times mr-1"></i> Ghairi
                        </a>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let pollInterval = null;
    let countdownInterval = null;
    let timeLeft = 90;
    const paymentId = '{{ $payment->id }}';
    const statusUrl = '{{ route("give.status", $payment->id) }}';
    let isChecking = false;

    function pollPaymentStatus(isManual = false) {
        if (isChecking) return;
        isChecking = true;

        if (isManual) {
            $('#btn-manual-check').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Inakagua...');
        }

        fetch(statusUrl, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            isChecking = false;
            if (isManual) {
                $('#btn-manual-check').prop('disabled', false).html('<i class="fas fa-sync-alt mr-1"></i> Nimeshaweka PIN (Kagua Sasa)');
            }

            if (data.status === 'completed') {
                clearInterval(pollInterval);
                clearInterval(countdownInterval);

                $('#status-icon').removeClass('fa-mobile-alt').addClass('fa-check');
                $('#status-icon-box').css('background', 'linear-gradient(135deg, #28a745, #1e7e34)');
                $('#polling-spinner').hide();
                $('#status-message').removeClass('text-primary').addClass('text-success font-weight-bold')
                    .html('<i class="fas fa-check-circle mr-1"></i> ' + (data.message || 'Malipo yamekamilika! Inakuelekeza...'));

                setTimeout(function() {
                    window.location.href = data.redirect || '{{ route("give.success", ["reference" => $payment->reference]) }}';
                }, 1200);
            } else if (data.status === 'failed') {
                clearInterval(pollInterval);
                clearInterval(countdownInterval);

                $('#status-icon').removeClass('fa-mobile-alt').addClass('fa-times');
                $('#status-icon-box').css('background', 'linear-gradient(135deg, #dc3545, #bd2130)');
                $('#polling-spinner').hide();
                $('#status-message').removeClass('text-primary').addClass('text-danger font-weight-bold')
                    .text(data.message || 'Malipo yameshindikana au yamekataliwa.');

                $('#btn-manual-check').hide();
            } else {
                if (data.message) {
                    $('#status-message').text(data.message);
                }
            }
        })
        .catch(err => {
            isChecking = false;
            if (isManual) {
                $('#btn-manual-check').prop('disabled', false).html('<i class="fas fa-sync-alt mr-1"></i> Nimeshaweka PIN (Kagua Sasa)');
            }
            console.error('Polling error:', err);
        });
    }

    $(document).ready(function() {
        // Poll every 3 seconds
        pollInterval = setInterval(function() {
            pollPaymentStatus(false);
        }, 3000);

        // Initial poll after 1.5 seconds
        setTimeout(function() {
            pollPaymentStatus(false);
        }, 1500);

        // Countdown timer
        countdownInterval = setInterval(function() {
            timeLeft--;
            $('#countdown-text').text(timeLeft + 's');
            if (timeLeft <= 0) {
                clearInterval(countdownInterval);
                $('#status-message').text('Muda wa kusubiri umekwisha. Kama uliweka PIN, bonyeza Kagua Sasa.');
            }
        }, 1000);
    });
</script>
@endpush
