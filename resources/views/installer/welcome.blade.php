@extends('installer.layout')

@section('content')
<div class="space-y-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-900 flex items-center">
            <i class="fas fa-clipboard-check text-blue-600 mr-3"></i> Hatua 1: Ukaguzi wa Seva & Ruhusa
        </h2>
        <p class="text-sm text-gray-600 mt-1">
            Kabla ya kuanza, mfumo unakagua kama seva yako (VPS) inakidhi vigezo na ruhusa zinazohitajika.
        </p>
    </div>

    <!-- PHP Extensions Checklist -->
    <div class="bg-gray-50 rounded-xl p-5 border border-gray-200">
        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-3 flex items-center">
            <i class="fab fa-php text-indigo-600 mr-2"></i> Mahitaji ya PHP & Extensions
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            @foreach($requirements as $req)
                <div class="flex items-center justify-between p-2.5 bg-white rounded-lg border {{ $req['passed'] ? 'border-green-100' : 'border-red-200 bg-red-50' }}">
                    <span class="text-xs font-medium text-gray-700">{{ $req['name'] }}</span>
                    @if($req['passed'])
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                            <i class="fas fa-check-circle mr-1"></i> Ipo
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                            <i class="fas fa-times-circle mr-1"></i> Haipo
                        </span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <!-- Permissions Checklist -->
    <div class="bg-gray-50 rounded-xl p-5 border border-gray-200">
        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-3 flex items-center">
            <i class="fas fa-folder-open text-amber-500 mr-2"></i> Ruhusa za Folda (Permissions)
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            @foreach($permissions as $perm)
                <div class="flex items-center justify-between p-2.5 bg-white rounded-lg border {{ $perm['passed'] ? 'border-green-100' : 'border-red-200 bg-red-50' }}">
                    <span class="text-xs font-medium font-mono text-gray-700">{{ $perm['name'] }}</span>
                    @if($perm['passed'])
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                            <i class="fas fa-check-circle mr-1"></i> Inaandikika
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                            <i class="fas fa-times-circle mr-1"></i> Haina Ruhusa
                        </span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="pt-4 flex items-center justify-between border-t border-gray-200">
        <a href="{{ route('install.index') }}" class="inline-flex items-center text-sm font-medium text-gray-600 hover:text-gray-900 transition">
            <i class="fas fa-redo mr-2 text-xs"></i> Kagua Upya
        </a>

        @if($allRequirementsPassed && $allPermissionsPassed)
            <a href="{{ route('install.database') }}" class="inline-flex items-center px-6 py-3 border border-transparent text-sm font-bold rounded-xl shadow-lg text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 transform hover:scale-105 transition duration-200">
                Endelea kwenye Database <i class="fas fa-arrow-right ml-2"></i>
            </a>
        @else
            <button disabled class="inline-flex items-center px-6 py-3 border border-gray-300 text-sm font-bold rounded-xl text-gray-400 bg-gray-200 cursor-not-allowed">
                Rekebisha Mahitaji Kwanza
            </button>
        @endif
    </div>
</div>
@endsection
