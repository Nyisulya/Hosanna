@extends('installer.layout')

@section('content')
<div class="space-y-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-900 flex items-center">
            <i class="fas fa-church text-blue-600 mr-3"></i> Hatua 3: Taarifa za Kanisa & Msimamizi Mkuu
        </h2>
        <p class="text-sm text-gray-600 mt-1">
            Weka jina la kanisa na taarifa za akaunti ya kwanza ya Super Admin utakayotumia kuingia kwenye mfumo.
        </p>
    </div>

    <form method="POST" action="{{ route('install.finish') }}" class="space-y-5" id="installForm">
        @csrf

        <!-- Church Info -->
        <div class="bg-blue-50/50 p-4 rounded-xl border border-blue-100 space-y-4">
            <h3 class="text-xs font-bold text-blue-900 uppercase tracking-wider flex items-center">
                <i class="fas fa-place-of-worship mr-2 text-blue-600"></i> Taarifa za Kanisa
            </h3>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Jina Kamili la Kanisa (Church Name)</label>
                <div class="relative rounded-lg shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                        <i class="fas fa-monument"></i>
                    </div>
                    <input type="text" name="church_name" value="{{ old('church_name', 'Hosanna International Church') }}" required placeholder="mfano: Hosanna International Church" class="block w-full pl-10 pr-3 py-2.5 sm:text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 bg-white border">
                </div>
            </div>
        </div>

        <!-- Super Admin Info -->
        <div class="bg-indigo-50/50 p-4 rounded-xl border border-indigo-100 space-y-4">
            <h3 class="text-xs font-bold text-indigo-900 uppercase tracking-wider flex items-center">
                <i class="fas fa-user-shield mr-2 text-indigo-600"></i> Akaunti ya Super Admin
            </h3>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Jina Kamili la Msimamizi</label>
                <div class="relative rounded-lg shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                        <i class="fas fa-user"></i>
                    </div>
                    <input type="text" name="admin_name" value="{{ old('admin_name', 'Super Admin') }}" required placeholder="mfano: Super Admin" class="block w-full pl-10 pr-3 py-2.5 sm:text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 bg-white border">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Barua Pepe ya Kuingilia (Email)</label>
                    <div class="relative rounded-lg shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <input type="email" name="admin_email" value="{{ old('admin_email', 'admin@hosannachurch.org') }}" required placeholder="admin@kanisa.com" class="block w-full pl-10 pr-3 py-2.5 sm:text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 bg-white border">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Nambari ya Simu (Si Lazima)</label>
                    <div class="relative rounded-lg shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                            <i class="fas fa-phone"></i>
                        </div>
                        <input type="text" name="admin_phone" value="{{ old('admin_phone', '0700000000') }}" placeholder="07XXXXXXXX" class="block w-full pl-10 pr-3 py-2.5 sm:text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 bg-white border">
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Nenosiri (Password)</label>
                <div class="relative rounded-lg shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                        <i class="fas fa-lock"></i>
                    </div>
                    <input type="text" name="admin_password" value="{{ old('admin_password', 'Admin@2025!') }}" required minlength="6" class="block w-full pl-10 pr-3 py-2.5 sm:text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 bg-white border font-mono">
                </div>
                <p class="text-xs text-gray-500 mt-1">Hili litakuwa nenosiri lako la kuingia kwenye mfumo.</p>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="pt-6 flex items-center justify-between border-t border-gray-200">
            <a href="{{ route('install.database') }}" class="inline-flex items-center text-sm font-medium text-gray-600 hover:text-gray-900 transition">
                <i class="fas fa-arrow-left mr-2 text-xs"></i> Rudi Nyuma
            </a>

            <button type="submit" id="submitBtn" class="inline-flex items-center px-8 py-3.5 border border-transparent text-sm font-extrabold rounded-xl shadow-xl text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 transform hover:scale-105 transition duration-200">
                <i class="fas fa-rocket mr-2"></i> Kamilisha Usakinishaji
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    document.getElementById('installForm').addEventListener('submit', function() {
        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Inaunda Meza & Mfumo...';
    });
</script>
@endpush
@endsection
