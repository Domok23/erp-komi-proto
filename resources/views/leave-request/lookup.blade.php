@extends('layouts.leave-request')

@section('content')
    <div class="mb-6">
        <h1 class="text-xl font-bold text-gray-900 tracking-tight">Cek & Ajukan Cuti</h1>
        <p class="text-xs text-gray-500 mt-1 font-medium">{{ $company->name }}</p>
    </div>

    @if (session('status'))
        <div id="toast-success" class="flex items-center justify-between p-4 mb-6 text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-lg" role="alert">
            <div class="flex items-center gap-2.5">
                <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="font-medium">{{ session('status') }}</span>
            </div>
            <button type="button" onclick="document.getElementById('toast-success').remove()" class="text-emerald-600 hover:text-emerald-800 p-1 text-sm">
                ✕
            </button>
        </div>
    @endif

    <form method="POST" action="{{ route('leave-request.lookup.submit', $company->code) }}" class="space-y-5">
        @csrf
        <div>
            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">Nomor Induk Karyawan (NIK)</label>
            <input type="text" name="employee_number" placeholder="Contoh: EMP-101" class="w-full border border-gray-300 rounded-lg px-3.5 py-2.5 text-sm bg-white text-gray-900 focus:ring-1 focus:ring-amber-500 focus:border-amber-500 focus:outline-none transition shadow-2xs font-medium" required>
            @error('employee_number')
                <p class="text-xs font-medium text-rose-600 mt-1.5">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="w-full bg-amber-500 hover:bg-amber-600 active:bg-amber-700 text-white font-semibold py-2.5 px-4 rounded-lg text-sm transition shadow-xs cursor-pointer min-h-[42px] flex items-center justify-center gap-2">
            <span>Lanjut</span>
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
            </svg>
        </button>
    </form>
@endsection
