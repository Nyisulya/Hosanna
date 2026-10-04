@extends('installer.layout')

@section('content')
<div class="space-y-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-900 flex items-center">
            <i class="fas fa-database text-blue-600 mr-3"></i> Hatua 2: Mipangilio ya Database (MySQL)
        </h2>
        <p class="text-sm text-gray-600 mt-1">
            Weka taarifa za MySQL Database uliyoiunda kwenye aaPanel au seva yako.
        </p>
    </div>

    <form method="POST" action="{{ route('install.database.save') }}" class="space-y-4">
        @csrf

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="sm:col-span-2">
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Database Host</label>
                <div class="relative rounded-lg shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                        <i class="fas fa-server"></i>
                    </div>
                    <input type="text" name="db_host" value="{{ old('db_host', env('DB_HOST', '127.0.0.1')) }}" required class="block w-full pl-10 pr-3 py-2.5 sm:text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 bg-gray-50 border">
                </div>
                <p class="text-xs text-gray-500 mt-1">Mara nyingi huwa ni <code>127.0.0.1</code> au <code>localhost</code></p>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Port</label>
                <input type="number" name="db_port" value="{{ old('db_port', env('DB_PORT', '3306')) }}" required class="block w-full px-3 py-2.5 sm:text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 bg-gray-50 border">
            </div>
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Jina la Database (Database Name)</label>
            <div class="relative rounded-lg shadow-sm">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                    <i class="fas fa-database"></i>
                </div>
                <input type="text" name="db_database" value="{{ old('db_database', env('DB_DATABASE', 'hossana_user')) }}" required placeholder="mfano: hossana_user" class="block w-full pl-10 pr-3 py-2.5 sm:text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 bg-gray-50 border">
            </div>
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Database Username</label>
            <div class="relative rounded-lg shadow-sm">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                    <i class="fas fa-user"></i>
                </div>
                <input type="text" name="db_username" value="{{ old('db_username', env('DB_USERNAME', 'hossana_user')) }}" required placeholder="mfano: hossana_user" class="block w-full pl-10 pr-3 py-2.5 sm:text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 bg-gray-50 border">
            </div>
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Database Password</label>
            <div class="relative rounded-lg shadow-sm">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                    <i class="fas fa-key"></i>
                </div>
                <input type="password" name="db_password" value="{{ old('db_password', env('DB_PASSWORD', '')) }}" placeholder="Weka nenosiri la database" class="block w-full pl-10 pr-3 py-2.5 sm:text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 bg-gray-50 border">
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="pt-6 flex items-center justify-between border-t border-gray-200">
            <a href="{{ route('install.index') }}" class="inline-flex items-center text-sm font-medium text-gray-600 hover:text-gray-900 transition">
                <i class="fas fa-arrow-left mr-2 text-xs"></i> Rudi Nyuma
            </a>

            <button type="submit" class="inline-flex items-center px-6 py-3 border border-transparent text-sm font-bold rounded-xl shadow-lg text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 transform hover:scale-105 transition duration-200">
                Pima Muunganisho & Endelea <i class="fas fa-arrow-right ml-2"></i>
            </button>
        </div>
    </form>
</div>
@endsection
