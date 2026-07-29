@extends('layouts.leave-request')

@section('content')
    <div class="mb-8">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-500/10 border border-amber-500/20 text-amber-600 dark:text-amber-400 text-xs font-semibold mb-3">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            <span>{{ $company->name }}</span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">Form Pengajuan Cuti</h1>
        <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Masukkan Nomor Induk Karyawan (NIK) untuk memeriksa kuota dan mengisi pengajuan cuti.</p>
    </div>

    @if (session('status'))
        <div id="toast-success" class="flex items-center justify-between p-4 mb-6 text-sm text-emerald-800 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-300 dark:border-emerald-500/30 rounded-2xl backdrop-blur-md" role="alert">
            <div class="flex items-center gap-3">
                <x-heroicon-o-check-circle class="w-6 h-6 text-emerald-600 dark:text-emerald-400 shrink-0" />
                <span class="font-semibold text-sm text-emerald-900 dark:text-emerald-200">{{ session('status') }}</span>
            </div>
            <button type="button" onclick="document.getElementById('toast-success').remove()" class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-900 dark:hover:text-emerald-200 p-1.5 rounded-lg hover:bg-emerald-100 dark:hover:bg-emerald-900/50 transition cursor-pointer flex items-center justify-center">
                <x-heroicon-o-x-mark class="w-5 h-5" />
            </button>
        </div>
    @endif

    <form method="POST" action="{{ route('leave-request.lookup.submit', $company->code) }}" class="space-y-6">
        @csrf
        <div>
            <label class="block text-sm font-bold text-slate-800 dark:text-slate-200 mb-1.5">
                Nomor Induk Karyawan (NIK)
            </label>
            <div class="relative">
                <input type="text" name="employee_number" placeholder="Contoh: EMP-101" class="w-full border border-slate-300 dark:border-slate-700 rounded-2xl px-4 py-3.5 pl-11 text-base bg-slate-50 dark:bg-slate-950/80 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 focus:outline-none transition shadow-2xs font-mono font-medium min-h-[52px]" required>
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 dark:text-slate-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 012-2h2a2 2 0 012 2v1m-6 0h6"/></svg>
                </div>
            </div>

            @error('employee_number')
                <p class="text-sm font-semibold text-rose-600 dark:text-rose-400 mt-2 flex items-center gap-1.5">
                    <x-heroicon-o-exclamation-circle class="w-4 h-4 shrink-0" />
                    <span>{{ $message }}</span>
                </p>
            @enderror
        </div>

        <button type="submit" class="w-full bg-amber-500 hover:bg-amber-400 active:bg-amber-600 text-slate-950 font-bold py-3.5 px-5 rounded-2xl text-base transition shadow-lg shadow-amber-500/20 cursor-pointer min-h-[52px] flex items-center justify-center gap-2">
            <span>Cari NIK &amp; Lanjut</span>
            <x-heroicon-o-arrow-right class="w-5 h-5 text-slate-950" />
        </button>
    </form>
@endsection


