<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asante - {{ config('app.name', 'Hosanna Church') }}</title>
    <meta name="theme-color" content="#1e3a8a">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 via-white to-indigo-50 min-h-screen flex items-center justify-center px-4">

    <div class="max-w-md w-full bg-white rounded-2xl shadow-lg p-8 text-center">
        <div class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-green-100">
            <svg class="h-10 w-10 text-green-600" fill="none" stroke="currentColor" stroke-width="2.5"
                 viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
        </div>

        <h1 class="text-2xl font-bold text-blue-900 mb-2">Usajili Umekamilika!</h1>
        <p class="text-gray-600 mb-4">
            Taarifa zako zimepokelewa kikamilifu. Tumekutumia ujumbe mfupi (SMS) kwenye namba yako ya simu wenye maelezo ya akaunti yako na nenosiri la kuingilia kwenye mfumo.
        </p>
        <p class="text-xs text-blue-700 bg-blue-50 border border-blue-200 rounded-lg p-3 mb-6">
            Kiungo cha mfumo: <strong>https://hossana.nyisu.com</strong>
        </p>

        <div class="space-y-3">
            <a href="{{ route('login') }}"
               class="inline-block w-full bg-blue-700 hover:bg-blue-800 text-white font-semibold py-3 rounded-lg transition shadow-md">
                Ingia Kwenye Mfumo (Login)
            </a>
            <a href="{{ route('public.register') }}"
               class="inline-block w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-3 rounded-lg transition">
                Sajili Mtu Mwingine
            </a>
        </div>

        <p class="text-center text-xs text-gray-400 mt-6">
            &copy; {{ date('Y') }} {{ config('app.name', 'Hosanna Church') }}
        </p>
    </div>

</body>
</html>
