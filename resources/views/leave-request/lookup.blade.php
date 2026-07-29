@extends('layouts.leave-request')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Form Pengajuan Cuti</h1>
        <p class="text-sm text-gray-600 mt-1">{{ $company->name }}</p>
    </div>

    @if (session('status'))
        <div id="toast-success" class="flex items-center justify-between p-4 mb-6 text-sm text-emerald-900 bg-emerald-50 border border-emerald-300 rounded-xl" role="alert">
            <div class="flex items-center gap-3">
                <x-heroicon-o-check-circle class="w-6 h-6 text-emerald-600 shrink-0" />
                <span class="font-bold text-base text-emerald-950">{{ session('status') }}</span>
            </div>
            <button type="button" onclick="document.getElementById('toast-success').remove()" class="text-emerald-600 hover:text-emerald-900 p-1.5 rounded-lg hover:bg-emerald-100/50 transition cursor-pointer flex items-center justify-center">
                <x-heroicon-o-x-mark class="w-5 h-5" />
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
                    <x-heroicon-o-exclamation-circle class="w-4 h-4 shrink-0" />
                    <span>{{ $message }}</span>
                </p>
            @enderror
        </div>

        <button type="submit" class="w-full bg-amber-500 hover:bg-amber-600 active:bg-amber-700 text-white font-bold py-3.5 px-5 rounded-xl text-base transition shadow-xs cursor-pointer min-h-[48px] flex items-center justify-center gap-2">
            <span>Cari NIK & Lanjut</span>
            <x-heroicon-o-arrow-right class="w-5 h-5 text-white" />
        </button>
    </form>
@endsection
