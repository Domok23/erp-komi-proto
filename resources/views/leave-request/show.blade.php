@extends('layouts.leave-request')

@section('content')
    <!-- HEADER KARYAWAN (Filament Header Style) -->
    <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-200">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-amber-500 text-white font-bold text-base flex items-center justify-center shadow-xs shrink-0">
                {{ strtoupper(substr($employee->name, 0, 1)) }}
            </div>
            <div>
                <h1 class="text-xl font-bold text-gray-900 tracking-tight">{{ $employee->name }}</h1>
                <div class="flex items-center gap-2 mt-0.5">
                    <span class="text-xs font-mono font-medium text-gray-500">NIK: {{ $employee->employee_number }}</span>
                    <span class="text-gray-300">·</span>
                    <span class="text-xs text-gray-500 font-medium">Active</span>
                </div>
            </div>
        </div>
        <a href="{{ route('leave-request.lookup', $company->code) }}" class="inline-flex items-center gap-1 text-xs font-medium text-gray-700 hover:text-gray-900 bg-white border border-gray-300 hover:bg-gray-50 px-3 py-1.5 rounded-lg shadow-2xs transition">
            ← Back
        </a>
    </div>

    <!-- TOAST ALERT NOTIFICATION -->
    @if (session('status'))
        <div id="toast-success" class="flex items-center justify-between p-4 mb-6 text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-lg" role="alert">
            <div class="flex items-center gap-2.5">
                <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="font-medium">{{ session('status') }}</span>
            </div>
            <button type="button" onclick="document.getElementById('toast-success').remove()" class="text-emerald-600 hover:text-emerald-800 p-1 text-sm font-semibold">
                ✕
            </button>
        </div>
    @endif

    <!-- FORM AJUKAN CUTI BARU -->
    <div class="mb-8 pb-8 border-b border-gray-200">
        <h2 class="text-base font-bold text-gray-900 mb-4 flex items-center gap-2">
            <svg class="w-4 h-4 text-amber-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Ajukan Cuti Baru
        </h2>

        <form method="POST" action="{{ route('leave-request.submit', $company->code) }}" class="space-y-4">
            @csrf
            <input type="hidden" name="employee_number" value="{{ $employee->employee_number }}">

            <div>
                <label class="block text-xs font-medium text-gray-700 uppercase tracking-wider mb-1.5">Jenis Cuti</label>
                <select name="leave_type_id" class="w-full border border-gray-300 rounded-lg px-3.5 py-2.5 text-sm bg-white text-gray-900 focus:ring-1 focus:ring-amber-500 focus:border-amber-500 focus:outline-none transition shadow-2xs font-medium" required>
                    @foreach ($leaveTypes as $type)
                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 uppercase tracking-wider mb-1.5">Tanggal Mulai</label>
                    <input type="date" name="start_date" class="w-full border border-gray-300 rounded-lg px-3.5 py-2.5 text-sm bg-white text-gray-900 focus:ring-1 focus:ring-amber-500 focus:border-amber-500 focus:outline-none transition shadow-2xs font-medium" required>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 uppercase tracking-wider mb-1.5">Tanggal Selesai</label>
                    <input type="date" name="end_date" class="w-full border border-gray-300 rounded-lg px-3.5 py-2.5 text-sm bg-white text-gray-900 focus:ring-1 focus:ring-amber-500 focus:border-amber-500 focus:outline-none transition shadow-2xs font-medium" required>
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 uppercase tracking-wider mb-1.5">Alasan Cuti</label>
                <textarea name="reason" rows="3" placeholder="Jelaskan kebutuhan cuti Anda secara rinci..." class="w-full border border-gray-300 rounded-lg px-3.5 py-2.5 text-sm bg-white text-gray-900 focus:ring-1 focus:ring-amber-500 focus:border-amber-500 focus:outline-none transition shadow-2xs font-medium"></textarea>
            </div>

            <button type="submit" class="w-full bg-amber-500 hover:bg-amber-600 active:bg-amber-700 text-white font-semibold py-2.5 px-4 rounded-lg text-sm transition shadow-xs cursor-pointer min-h-[42px] flex items-center justify-center gap-2">
                <span>Kirim Pengajuan Cuti</span>
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
            </button>
        </form>
    </div>

    <!-- RIWAYAT PENGAJUAN (Filament Card List) -->
    <div>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-base font-bold text-gray-900 tracking-tight">Riwayat Pengajuan</h2>
            <span class="text-xs font-medium bg-gray-100 text-gray-600 px-2.5 py-1 rounded-md border border-gray-200">
                {{ $requests->count() }} Total
            </span>
        </div>

        <div class="space-y-3">
            @forelse ($requests as $req)
                @php
                    $duration = $req->start_date && $req->end_date ? $req->start_date->diffInDays($req->end_date) + 1 : 1;
                @endphp
                <details class="group border border-gray-200 rounded-xl overflow-hidden bg-white shadow-2xs transition" {{ $loop->first ? 'open' : '' }}>
                    <summary class="flex items-center justify-between p-4 cursor-pointer select-none bg-gray-50/60 group-open:bg-gray-100/70 hover:bg-gray-100/80 transition">
                        <div class="flex items-center gap-3">
                            <span class="w-6 h-6 rounded-md bg-white border border-gray-200 flex items-center justify-center shrink-0">
                                <svg class="w-3.5 h-3.5 text-gray-500 group-open:rotate-180 transition-transform duration-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </span>
                            <div>
                                <div class="font-semibold text-sm text-gray-900">{{ $req->leaveType->name }}</div>
                                <div class="text-xs text-gray-500 font-medium">
                                    {{ $req->start_date->format('d M Y') }} – {{ $req->end_date->format('d M Y') }} · <span class="text-gray-700">{{ $duration }} Hari</span>
                                </div>
                            </div>
                        </div>
                        <div class="shrink-0">
                            @if ($req->status === 'pending')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200">
                                    Pending
                                </span>
                            @elseif ($req->status === 'approved')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    Disetujui
                                </span>
                            @elseif ($req->status === 'rejected')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200">
                                    Ditolak
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-gray-100 text-gray-700">
                                    {{ ucfirst($req->status) }}
                                </span>
                            @endif
                        </div>
                    </summary>

                    <div class="p-4 border-t border-gray-200 bg-white text-xs space-y-3">
                        <div class="bg-gray-50 p-3 rounded-lg border border-gray-200 text-gray-600">
                            <span class="font-semibold text-gray-500 block text-[10px] uppercase">TGL PENGAJUAN</span>
                            <span class="font-medium text-gray-800">{{ $req->created_at ? $req->created_at->format('d M Y H:i') : '-' }}</span>
                        </div>

                        <div>
                            <span class="font-semibold text-gray-500 block text-[10px] uppercase mb-1">ALASAN PENGAJUAN KARYAWAN</span>
                            <div class="bg-gray-50 p-3 rounded-lg border border-gray-200 text-gray-800 font-medium">
                                {{ trim($req->reason) ?: 'Tidak ada alasan dicantumkan.' }}
                            </div>
                        </div>

                        @if ($req->status === 'rejected')
                            <div class="bg-rose-50 border border-rose-200 rounded-lg p-3.5 text-rose-900 space-y-1">
                                <div class="flex items-center gap-1.5 font-bold text-xs text-rose-800">
                                    <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span>Alasan Penolakan dari Admin / HRD:</span>
                                </div>
                                <p class="text-xs text-rose-700 pl-5.5 font-medium">{{ trim($req->rejected_reason) ?: 'Tidak ada catatan alasan khusus.' }}</p>
                            </div>
                        @elseif ($req->status === 'approved' && $req->approved_at)
                            <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-3 text-emerald-800 flex items-center gap-2 font-medium">
                                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Disetujui pada: <strong>{{ $req->approved_at->format('d M Y H:i') }}</strong></span>
                            </div>
                        @endif
                    </div>
                </details>
            @empty
                <div class="text-center py-8 bg-gray-50 border border-dashed border-gray-200 rounded-xl text-gray-500 text-sm font-medium">
                    Belum ada riwayat pengajuan cuti.
                </div>
            @endforelse
        </div>
    </div>
@endsection
