<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usajili wa Mshiriki - {{ config('app.name', 'Hosanna Church') }}</title>
    <meta name="theme-color" content="#1e3a8a">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/images/icons/icon.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 via-white to-indigo-50 min-h-screen">

    <div class="max-w-xl mx-auto px-4 py-8">

        <!-- Header -->
        <div class="text-center mb-6">
            <img src="{{ asset('images/church-logo.png') }}" alt="Logo"
                 class="mx-auto mb-3 h-20 w-20 object-contain rounded-full bg-white p-1 shadow">
            <h1 class="text-2xl font-bold text-blue-900">{{ config('app.name', 'Hosanna Church') }}</h1>
            <p class="text-gray-600 mt-1">Karibu! Jaza taarifa zako za msingi hapa chini.</p>
        </div>

        <!-- Card -->
        <div class="bg-white rounded-2xl shadow-lg p-6 sm:p-8">

            @if ($errors->any())
                <div class="mb-5 rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('public.register.store') }}" method="POST" class="space-y-5">
                @csrf

                <!-- Honeypot (hidden from real users) -->
                <div class="hidden" aria-hidden="true">
                    <label>Website</label>
                    <input type="text" name="website" tabindex="-1" autocomplete="off">
                </div>

                <!-- Full name -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">
                        Jina Kamili <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="full_name" value="{{ old('full_name') }}" required
                           placeholder="Mfano: Juma John Mwakyusa"
                           class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <!-- Phone -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">
                        Namba ya Simu <span class="text-red-500">*</span>
                    </label>
                    <input type="tel" name="phone" value="{{ old('phone') }}" required
                           placeholder="Mfano: 0712 345 678"
                           class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Barua Pepe (si lazima)</label>
                    <input type="email" name="email" value="{{ old('email') }}"
                           placeholder="Mfano: juma@email.com"
                           class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <!-- Gender + DOB -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Jinsia</label>
                        <select name="gender"
                                class="w-full rounded-lg border border-gray-300 px-4 py-3 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">-- Chagua --</option>
                            <option value="male"   @selected(old('gender') === 'male')>Me</option>
                            <option value="female" @selected(old('gender') === 'female')>Ke</option>
                            <option value="other"  @selected(old('gender') === 'other')>Nyingine</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Tarehe ya Kuzaliwa</label>
                        <input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}"
                               class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>

                <!-- Marital status -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Hali ya Ndoa</label>
                    <select name="marital_status"
                            class="w-full rounded-lg border border-gray-300 px-4 py-3 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">-- Chagua --</option>
                        <option value="single"   @selected(old('marital_status') === 'single')>Sijaoa / Sijaolewa</option>
                        <option value="married"  @selected(old('marital_status') === 'married')>Nimeoa / Nimeolewa</option>
                        <option value="widowed"  @selected(old('marital_status') === 'widowed')>Mjane</option>
                        <option value="divorced" @selected(old('marital_status') === 'divorced')>Talaka</option>
                    </select>
                </div>

                <!-- Address -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Mahali Unapoishi</label>
                    <input type="text" name="address" value="{{ old('address') }}"
                           placeholder="Mfano: Mwanza, Nyamagana"
                           class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <button type="submit"
                        class="w-full bg-blue-700 hover:bg-blue-800 text-white font-semibold py-3 rounded-lg transition shadow-md">
                    Tuma Taarifa
                </button>

                <p class="text-xs text-gray-500 text-center">
                    Taarifa zako zitahifadhiwa kwa usalama na kukaguliwa na uongozi wa kanisa.
                </p>
            </form>
        </div>

        <p class="text-center text-xs text-gray-400 mt-6">
            &copy; {{ date('Y') }} {{ config('app.name', 'Hosanna Church') }}
        </p>
    </div>

</body>
</html>
