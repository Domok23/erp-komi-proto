@extends('layouts.leave-request')

@section('content')
    <!-- HEADER KARYAWAN -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6 pb-4 border-b border-gray-200">
        <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-amber-500 text-white font-bold text-lg flex items-center justify-center shadow-xs shrink-0">
                {{ strtoupper(substr($employee->name, 0, 1)) }}
            </div>
            <div>
                <h1 class="text-xl font-bold text-gray-900 tracking-tight">{{ $employee->name }}</h1>
                <div class="flex items-center gap-2 mt-0.5">
                    <span class="text-xs font-mono font-bold text-gray-600 bg-gray-100 px-2 py-0.5 rounded border border-gray-200">NIK: {{ $employee->employee_number }}</span>
                    <span class="text-gray-300">·</span>
                    <span class="text-xs text-emerald-700 font-bold">Karyawan Aktif</span>
                </div>
            </div>
        </div>
        <a href="{{ route('leave-request.lookup', $company->code) }}" class="self-start sm:self-auto inline-flex items-center gap-1.5 text-xs font-bold text-gray-700 hover:text-gray-900 bg-white border border-gray-300 hover:bg-gray-50 px-3.5 py-2 rounded-xl shadow-2xs transition">
            ← Kembali
        </a>
    </div>

    <!-- TOAST ALERT NOTIFICATION -->
    @if (session('status'))
        <div id="toast-success" class="flex items-start justify-between p-4 mb-6 text-sm text-emerald-900 bg-emerald-50 border border-emerald-300 rounded-xl" role="alert">
            <div class="flex items-center gap-3">
                <svg class="w-6 h-6 text-emerald-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="font-bold text-base">{{ session('status') }}</span>
            </div>
            <button type="button" onclick="document.getElementById('toast-success').remove()" class="text-emerald-700 hover:text-emerald-900 p-1 text-base font-bold">
                ✕
            </button>
        </div>
    @endif

    <!-- FORM AJUKAN CUTI BARU -->
    <div class="mb-8 pb-8 border-b border-gray-200">
        <div class="mb-5">
            <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                <svg class="w-5 h-5 text-amber-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Ajukan Cuti Baru
            </h2>
            <p class="text-xs text-gray-500 mt-1">Isi formulir di bawah ini untuk mengajukan izin atau cuti baru.</p>
        </div>

        <form method="POST" action="{{ route('leave-request.submit', $company->code) }}" class="space-y-5">
            @csrf
            <input type="hidden" name="employee_number" value="{{ $employee->employee_number }}">

            <div>
                <label class="block text-sm font-bold text-gray-800 mb-1">Pilih Jenis Cuti</label>
                <select name="leave_type_id" class="w-full border border-gray-300 rounded-xl px-4 py-3 text-base bg-white text-gray-900 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 focus:outline-none transition shadow-2xs font-medium min-h-[48px]" required>
                    @foreach ($leaveTypes as $type)
                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-bold text-gray-800 mb-1">Tanggal Mulai Cuti</label>
                    <input type="date" name="start_date" class="w-full border border-gray-300 rounded-xl px-4 py-3 text-base bg-white text-gray-900 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 focus:outline-none transition shadow-2xs font-medium min-h-[48px]" required>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-800 mb-1">Tanggal Selesai Cuti</label>
                    <input type="date" name="end_date" class="w-full border border-gray-300 rounded-xl px-4 py-3 text-base bg-white text-gray-900 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 focus:outline-none transition shadow-2xs font-medium min-h-[48px]" required>
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-800 mb-1">Alasan Pengajuan Cuti</label>
                <textarea name="reason" rows="3" placeholder="Tuliskan keperluan cuti Anda secara singkat dan jelas..." class="w-full border border-gray-300 rounded-xl px-4 py-3 text-base bg-white text-gray-900 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 focus:outline-none transition shadow-2xs font-medium"></textarea>
            </div>

            <button type="submit" class="w-full bg-amber-500 hover:bg-amber-600 active:bg-amber-700 text-white font-bold py-3.5 px-5 rounded-xl text-base transition shadow-xs cursor-pointer min-h-[50px] flex items-center justify-center gap-2">
                <span>Kirim Pengajuan Cuti</span>
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
            </button>
        </form>
    </div>

    <!-- RIWAYAT PENGAJUAN -->
    <div>
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-lg font-bold text-gray-900">Riwayat Pengajuan Cuti Anda</h2>
                <p class="text-xs text-gray-500 mt-0.5">Daftar pengajuan cuti sebelumnya (terbaru di atas)</p>
            </div>
            <span class="text-xs font-bold bg-gray-100 text-gray-700 px-3 py-1 rounded-full border border-gray-200">
                {{ $requests->count() }} Total
            </span>
        </div>

        <div class="space-y-3">
            @forelse ($requests as $req)
                @php
                    $duration = $req->start_date && $req->end_date ? $req->start_date->diffInDays($req->end_date) + 1 : 1;
                @endphp
                <details class="group border border-gray-200 rounded-xl overflow-hidden bg-white shadow-2xs transition" {{ $loop->first ? 'open' : '' }}>
                    <summary class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-4 cursor-pointer select-none bg-gray-50/80 group-open:bg-gray-100/90 hover:bg-gray-100 transition">
                        <div class="flex items-center gap-3">
                            <span class="w-7 h-7 rounded-lg bg-white border border-gray-300 flex items-center justify-center shrink-0 shadow-2xs">
                                <svg class="w-4 h-4 text-gray-600 group-open:rotate-180 transition-transform duration-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </span>
                            <div>
                                <div class="font-bold text-base text-gray-900">{{ $req->leaveType->name }}</div>
                                <div class="text-xs text-gray-600 font-medium mt-0.5">
                                    {{ $req->start_date->format('d M Y') }} – {{ $req->end_date->format('d M Y') }} · <span class="font-bold text-gray-800">{{ $duration }} Hari</span>
                                </div>
                            </div>
                        </div>
                        <div class="shrink-0 self-start sm:self-auto">
                            @if ($req->status === 'pending')
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-bold bg-amber-100 text-amber-900 border border-amber-300">
                                    <svg class="w-3.5 h-3.5 text-amber-700 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span>Menunggu Approval</span>
                                </span>
                            @elseif ($req->status === 'approved')
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-bold bg-emerald-100 text-emerald-900 border border-emerald-300">
                                    <svg class="w-3.5 h-3.5 text-emerald-700 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span>Disetujui</span>
                                </span>
                            @elseif ($req->status === 'rejected')
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-bold bg-rose-100 text-rose-900 border border-rose-300">
                                    <svg class="w-3.5 h-3.5 text-rose-700 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span>Ditolak</span>
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-bold bg-gray-100 text-gray-800">
                                    {{ ucfirst($req->status) }}
                                </span>
                            @endif
                        </div>
                    </summary>

                    <div class="p-4 border-t border-gray-200 bg-white text-sm space-y-3">
                        <div class="bg-gray-50 p-3 rounded-lg border border-gray-200 text-gray-700">
                            <span class="font-bold text-gray-500 block text-xs uppercase mb-0.5">TANGGAL PENGAJUAN</span>
                            <span class="font-semibold text-gray-900">{{ $req->created_at ? $req->created_at->format('d M Y, H:i') : '-' }} WIB</span>
                        </div>

                        <div>
                            <span class="font-bold text-gray-500 block text-xs uppercase mb-1">ALASAN PENGAJUAN CUTI</span>
                            <div class="bg-gray-50 p-3 rounded-lg border border-gray-200 text-gray-900 font-medium">
                                {{ trim($req->reason) ?: 'Tidak ada alasan dicantumkan.' }}
                            </div>
                        </div>

                        @if ($req->status === 'rejected')
                            <div class="bg-rose-50 border border-rose-300 rounded-lg p-3.5 text-rose-900 space-y-1">
                                <div class="flex items-center gap-2 font-bold text-sm text-rose-900">
                                    <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span>Alasan Penolakan HRD:</span>
                                </div>
                                <p class="text-sm text-rose-800 pl-7 font-medium">{{ trim($req->rejected_reason) ?: 'Tidak ada catatan alasan khusus.' }}</p>
                            </div>
                        @elseif ($req->status === 'approved' && $req->approved_at)
                            <div class="bg-emerald-50 border border-emerald-300 rounded-lg p-3 text-emerald-900 flex items-center gap-2 font-semibold text-sm">
                                <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Disetujui pada: <strong>{{ $req->approved_at->format('d M Y, H:i') }} WIB</strong></span>
                            </div>
                        @endif
                    </div>
                </details>
            @empty
                <div class="text-center py-10 bg-gray-50 border border-dashed border-gray-300 rounded-xl text-gray-500 text-sm font-medium">
                    Belum ada riwayat pengajuan cuti.
                </div>
            @endforelse
        </div>
    </div>
@endsection
