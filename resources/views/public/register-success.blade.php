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

        <h1 class="text-2xl font-bold text-blue-900 mb-2">Asante Sana!</h1>
        <p class="text-gray-600 mb-6">
            Taarifa zako zimepokelewa kwa mafanikio. Uongozi wa kanisa utazikagua na
            kuwasiliana nawe hivi karibuni.
        </p>

        <a href="{{ route('public.register') }}"
           class="inline-block w-full bg-blue-700 hover:bg-blue-800 text-white font-semibold py-3 rounded-lg transition shadow-md">
            Sajili Mtu Mwingine
        </a>

        <p class="text-center text-xs text-gray-400 mt-6">
            &copy; {{ date('Y') }} {{ config('app.name', 'Hosanna Church') }}
        </p>
    </div>

</body>
</html>
