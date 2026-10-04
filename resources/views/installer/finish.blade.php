@extends('installer.layout')

@section('content')
<div class="text-center space-y-6 py-4">
    <!-- Success Icon -->
    <div class="inline-flex items-center justify-center w-24 h-24 rounded-full bg-emerald-100 text-emerald-600 shadow-xl shadow-emerald-500/20 mb-2 transform scale-110 animate-bounce">
        <i class="fas fa-check-circle text-5xl"></i>
    </div>

    <div>
        <h2 class="text-3xl font-extrabold text-gray-900">
            Hongera! Mfumo Umesakinishwa Kikamilifu! 🎉
        </h2>
        <p class="text-base text-gray-600 mt-2 max-w-lg mx-auto">
            Meza zote za database, majukumu, na akaunti ya Super Admin kwa ajili ya <strong>{{ session('church_name', config('app.name', 'Hosanna International Church')) }}</strong> zimetengenezwa kwa mafanikio.
        </p>
    </div>

    <!-- Login Credentials Box -->
    <div class="bg-gray-50 rounded-2xl p-6 border border-gray-200 max-w-md mx-auto text-left shadow-inner space-y-3">
        <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center border-b pb-2">
            <i class="fas fa-key text-blue-600 mr-2"></i> Taarifa za Kuingilia (Super Admin):
        </h3>
        <div class="flex justify-between items-center text-sm">
            <span class="text-gray-500">Barua Pepe:</span>
            <span class="font-bold text-gray-800 font-mono">{{ session('admin_email', 'admin@hosannachurch.org') }}</span>
        </div>
        <div class="flex justify-between items-center text-sm">
            <span class="text-gray-500">Nenosiri:</span>
            <span class="font-bold text-gray-800 font-mono">{{ session('admin_password', 'Admin@2025!') }}</span>
        </div>
    </div>

    <!-- Go to Login Button -->
    <div class="pt-4">
        <a href="{{ route('login') }}" class="inline-flex items-center px-10 py-4 border border-transparent text-base font-extrabold rounded-2xl shadow-xl text-white bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 transform hover:scale-105 transition duration-200">
            <i class="fas fa-sign-in-alt mr-2"></i> Ingia Kwenye Mfumo (Login) <i class="fas fa-arrow-right ml-3"></i>
        </a>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof confetti === 'function') {
            confetti({
                particleCount: 100,
                spread: 70,
                origin: { y: 0.6 }
            });
        }
    });
</script>
@endpush
@endsection
