@extends('layouts.leave-request')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Form Pengajuan Cuti</h1>
        <p class="text-sm text-gray-600 mt-1">{{ $company->name }}</p>
    </div>

    @if (session('status'))
        <div id="toast-success" class="flex items-start justify-between p-4 mb-6 text-sm text-emerald-900 bg-emerald-50 border border-emerald-200 rounded-xl" role="alert">
            <div class="flex items-center gap-3">
                <svg class="w-6 h-6 text-emerald-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="font-medium text-base">{{ session('status') }}</span>
            </div>
            <button type="button" onclick="document.getElementById('toast-success').remove()" class="text-emerald-700 hover:text-emerald-900 p-1 text-base font-bold">
                ✕
            </button>
        </div>
    @endif

    <form method="POST" action="{{ route('leave-request.lookup.submit', $company->code) }}" class="space-y-6">
        @csrf
        <div>
            <label class="block text-sm font-bold text-gray-800 mb-1.5">
                Nomor Induk Karyawan (NIK)
            </label>
            <p class="text-xs text-gray-500 mb-2">Masukkan NIK Anda untuk melihat riwayat dan mengisi pengajuan cuti.</p>

            <input type="text" name="employee_number" placeholder="Contoh: EMP-101" class="w-full border border-gray-300 rounded-xl px-4 py-3 text-base bg-white text-gray-900 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 focus:outline-none transition shadow-2xs font-medium min-h-[48px]" required>

            @error('employee_number')
                <p class="text-sm font-semibold text-rose-600 mt-2 flex items-center gap-1.5">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>{{ $message }}</span>
                </p>
            @enderror
        </div>

        <button type="submit" class="w-full bg-amber-500 hover:bg-amber-600 active:bg-amber-700 text-white font-bold py-3.5 px-5 rounded-xl text-base transition shadow-xs cursor-pointer min-h-[48px] flex items-center justify-center gap-2">
            <span>Cari NIK & Lanjut</span>
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
            </svg>
        </button>
    </form>
@endsection
