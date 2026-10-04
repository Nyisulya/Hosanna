<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usakinishaji wa Mfumo - Church Management System</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=fallback" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- TailwindCSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            900: '#1e3a8a',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #0f172a 100%);
            min-height: 100vh;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
        }
    </style>
</head>
<body class="text-gray-800 antialiased flex flex-col justify-between py-8 px-4 sm:px-6 lg:px-8">

    <div class="max-w-3xl w-full mx-auto">
        <!-- Logo & Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white shadow-xl shadow-blue-500/30 mb-4 transform hover:scale-105 transition-transform duration-300">
                <i class="fas fa-church text-3xl"></i>
            </div>
            <h1 class="text-3xl font-extrabold text-white tracking-tight">Church Management System</h1>
            <p class="mt-2 text-sm text-blue-200">Mwongozo wa Usakinishaji na Mipangilio ya Awali (Setup Wizard)</p>
        </div>

        <!-- Wizard Step Indicators -->
        <div class="mb-8">
            <div class="flex items-center justify-center space-x-2 sm:space-x-4">
                @php
                    $route = request()->route()->getName();
                @endphp

                <!-- Step 1 -->
                <div class="flex items-center">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs {{ $route == 'install.index' ? 'bg-blue-600 text-white ring-4 ring-blue-400/30' : 'bg-green-500 text-white' }}">
                        1
                    </div>
                    <span class="ml-2 text-xs font-semibold hidden sm:inline {{ $route == 'install.index' ? 'text-white' : 'text-gray-300' }}">Mahitaji</span>
                </div>

                <div class="w-8 h-0.5 bg-gray-600"></div>

                <!-- Step 2 -->
                <div class="flex items-center">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs {{ $route == 'install.database' ? 'bg-blue-600 text-white ring-4 ring-blue-400/30' : ($route == 'install.admin' || $route == 'install.complete' ? 'bg-green-500 text-white' : 'bg-gray-700 text-gray-400') }}">
                        2
                    </div>
                    <span class="ml-2 text-xs font-semibold hidden sm:inline {{ $route == 'install.database' ? 'text-white' : 'text-gray-400' }}">Database</span>
                </div>

                <div class="w-8 h-0.5 bg-gray-600"></div>

                <!-- Step 3 -->
                <div class="flex items-center">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs {{ $route == 'install.admin' ? 'bg-blue-600 text-white ring-4 ring-blue-400/30' : ($route == 'install.complete' ? 'bg-green-500 text-white' : 'bg-gray-700 text-gray-400') }}">
                        3
                    </div>
                    <span class="ml-2 text-xs font-semibold hidden sm:inline {{ $route == 'install.admin' ? 'text-white' : 'text-gray-400' }}">Admin</span>
                </div>

                <div class="w-8 h-0.5 bg-gray-600"></div>

                <!-- Step 4 -->
                <div class="flex items-center">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs {{ $route == 'install.complete' ? 'bg-green-500 text-white ring-4 ring-green-400/30' : 'bg-gray-700 text-gray-400' }}">
                        <i class="fas fa-check text-xs"></i>
                    </div>
                    <span class="ml-2 text-xs font-semibold hidden sm:inline {{ $route == 'install.complete' ? 'text-green-400' : 'text-gray-400' }}">Kukamilika</span>
                </div>
            </div>
        </div>

        <!-- Alerts -->
        @if(session('error'))
            <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 flex items-start space-x-3 shadow-md">
                <i class="fas fa-exclamation-circle text-red-500 text-xl mt-0.5"></i>
                <div class="text-sm font-medium">{{ session('error') }}</div>
            </div>
        @endif

        @if(session('success'))
            <div class="mb-6 p-4 rounded-xl bg-green-50 border border-green-200 text-green-700 flex items-start space-x-3 shadow-md">
                <i class="fas fa-check-circle text-green-500 text-xl mt-0.5"></i>
                <div class="text-sm font-medium">{{ session('success') }}</div>
            </div>
        @endif

        <!-- Card Body -->
        <div class="glass-card rounded-2xl p-6 sm:p-10">
            @yield('content')
        </div>
    </div>

    <!-- Footer -->
    <div class="text-center text-xs text-gray-400 mt-8">
        &copy; {{ date('Y') }} Church Management System. All rights reserved.
    </div>

    @stack('scripts')
</body>
</html>
