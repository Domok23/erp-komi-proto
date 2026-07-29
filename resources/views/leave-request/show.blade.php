@extends('layouts.leave-request')

@section('content')
    <!-- HEADER KARYAWAN -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8 pb-6 border-b border-slate-200 dark:border-slate-800">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-amber-500 to-amber-400 text-slate-950 font-black text-xl flex items-center justify-center shadow-lg shadow-amber-500/20 shrink-0">
                {{ strtoupper(substr($employee->name, 0, 1)) }}
            </div>
            <div>
                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">{{ $employee->name }}</h1>
                <div class="flex items-center gap-2 mt-1">
                    <span class="text-xs font-mono font-bold text-amber-600 dark:text-amber-400 bg-amber-500/10 px-2.5 py-0.5 rounded-full border border-amber-500/20">NIK: {{ $employee->employee_number }}</span>
                    <span class="text-slate-400 dark:text-slate-600">·</span>
                    <span class="text-xs text-emerald-600 dark:text-emerald-400 font-bold flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 dark:bg-emerald-400 animate-pulse"></span>
                        Karyawan Aktif
                    </span>
                </div>
            </div>
        </div>
        <a href="{{ route('leave-request.lookup', $company->code) }}" class="self-start sm:self-auto inline-flex items-center gap-2 text-xs font-bold text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white bg-slate-100 dark:bg-slate-800/80 hover:bg-slate-200 dark:hover:bg-slate-800 border border-slate-300 dark:border-slate-700 px-4 py-2.5 rounded-xl transition cursor-pointer">
            <x-heroicon-o-arrow-left class="w-4 h-4 text-slate-500 dark:text-slate-400 shrink-0" />
            <span>Kembali</span>
        </a>
    </div>

    <!-- TOAST ALERT NOTIFICATION -->
    @if (session('status'))
        <div id="toast-success" class="flex items-center justify-between p-4 mb-6 text-sm text-emerald-800 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-300 dark:border-emerald-500/30 rounded-2xl backdrop-blur-md" role="alert">
            <div class="flex items-center gap-3">
                <x-heroicon-o-check-circle class="w-6 h-6 text-emerald-600 dark:text-emerald-400 shrink-0" />
                <span class="font-bold text-sm text-emerald-900 dark:text-emerald-200">{{ session('status') }}</span>
            </div>
            <button type="button" onclick="document.getElementById('toast-success').remove()" class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-900 dark:hover:text-emerald-200 p-1.5 rounded-lg hover:bg-emerald-100 dark:hover:bg-emerald-900/50 transition cursor-pointer flex items-center justify-center">
                <x-heroicon-o-x-mark class="w-5 h-5" />
            </button>
        </div>
    @endif

    @if ($errors->any())
        <div id="toast-error" class="flex items-center justify-between p-4 mb-6 text-sm text-rose-800 dark:text-rose-300 bg-rose-50 dark:bg-rose-950/60 border border-rose-300 dark:border-rose-500/30 rounded-2xl backdrop-blur-md" role="alert">
            <div class="flex items-center gap-3">
                <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-rose-600 dark:text-rose-400 shrink-0" />
                <div>
                    <span class="font-bold text-sm block text-rose-900 dark:text-rose-200">Gagal Mengirim Pengajuan Cuti</span>
                    <span class="text-xs text-rose-700 dark:text-rose-400 font-medium">{{ $errors->first() }}</span>
                </div>
            </div>
            <button type="button" onclick="document.getElementById('toast-error').remove()" class="text-rose-600 dark:text-rose-400 hover:text-rose-900 dark:hover:text-rose-200 p-1.5 rounded-lg hover:bg-rose-100 dark:hover:bg-rose-900/50 transition cursor-pointer flex items-center justify-center">
                <x-heroicon-o-x-mark class="w-5 h-5" />
            </button>
        </div>
    @endif

    <!-- FORM AJUKAN CUTI BARU -->
    <div class="mb-10 pb-10 border-b border-slate-200 dark:border-slate-800">
        <div class="mb-6">
            <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-amber-500/10 border border-amber-500/20 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                    <x-heroicon-o-plus class="w-4 h-4 shrink-0" />
                </div>
                Ajukan Cuti Baru
            </h2>
            <p class="text-xs text-slate-600 dark:text-slate-400 mt-1">Isi formulir di bawah ini untuk mengajukan izin atau cuti baru.</p>
        </div>

        <form method="POST" action="{{ route('leave-request.submit', $company->code) }}" enctype="multipart/form-data" class="space-y-5">
            @csrf
            <input type="hidden" name="employee_number" value="{{ $employee->employee_number }}">

            <div>
                <label class="block text-sm font-bold text-slate-800 dark:text-slate-200 mb-1.5">Pilih Jenis Cuti</label>
                <select id="leave_type_id" name="leave_type_id" onchange="updateAttachmentField()" class="w-full border border-slate-300 dark:border-slate-700 rounded-2xl px-4 py-3.5 text-base bg-slate-50 dark:bg-slate-950/80 text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500 focus:outline-none transition shadow-2xs font-medium min-h-[52px]" required>
                    @foreach ($leaveTypes as $type)
                        @php
                            $b = $balances[$type->id] ?? null;
                            $quotaText = $b ? " (Sisa Kuota: {$b['remaining']}/{$b['quota']} Hari)" : " (Tanpa Batas Kuota)";
                        @endphp
                        <option value="{{ $type->id }}" data-sick="{{ ($type->is_sick_type || str_contains(strtolower($type->name), 'sakit')) ? '1' : '0' }}">
                            {{ $type->name }}{{ $quotaText }}
                        </option>
                    @endforeach
                </select>
                @error('leave_type_id')
                    <p class="text-xs font-semibold text-rose-600 dark:text-rose-400 mt-1.5 flex items-center gap-1">
                        <x-heroicon-o-exclamation-circle class="w-4 h-4 shrink-0" />
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-bold text-slate-800 dark:text-slate-200 mb-1.5">Rentang Tanggal Cuti</label>
                <div class="relative">
                    <input type="text" id="date_range" name="date_range" value="{{ old('date_range') }}" placeholder="Pilih tanggal mulai s/d selesai cuti..." class="w-full border border-slate-300 dark:border-slate-700 rounded-2xl pl-4 pr-11 py-3.5 text-base bg-slate-50 dark:bg-slate-950/80 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 focus:outline-none transition shadow-2xs font-medium min-h-[52px] cursor-pointer" required readonly>
                    <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-slate-400 dark:text-slate-500">
                        <x-heroicon-o-calendar class="w-5 h-5 text-slate-500 dark:text-slate-400" />
                    </div>
                </div>
                @error('date_range')
                    <p class="text-xs font-semibold text-rose-600 dark:text-rose-400 mt-1.5 flex items-center gap-1">
                        <x-heroicon-o-exclamation-circle class="w-4 h-4 shrink-0" />
                        <span>{{ $message }}</span>
                    </p>
                @enderror
                @error('start_date')
                    <p class="text-xs font-semibold text-rose-600 dark:text-rose-400 mt-1.5 flex items-center gap-1">
                        <x-heroicon-o-exclamation-circle class="w-4 h-4 shrink-0" />
                        <span>{{ $message }}</span>
                    </p>
                @enderror
                @error('end_date')
                    <p class="text-xs font-semibold text-rose-600 dark:text-rose-400 mt-1.5 flex items-center gap-1">
                        <x-heroicon-o-exclamation-circle class="w-4 h-4 shrink-0" />
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-bold text-slate-800 dark:text-slate-200 mb-1.5">Alasan Pengajuan Cuti</label>
                <textarea name="reason" rows="3" placeholder="Tuliskan keperluan cuti Anda secara singkat dan jelas..." class="w-full border border-slate-300 dark:border-slate-700 rounded-2xl px-4 py-3.5 text-base bg-slate-50 dark:bg-slate-950/80 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 focus:outline-none transition shadow-2xs font-medium"></textarea>
            </div>

            <div>
                <label class="block text-sm font-bold text-slate-800 dark:text-slate-200 mb-1.5">
                    <span id="attachment-label">Lampiran</span>
                    <span id="attachment-badge" class="text-xs font-normal text-slate-500 dark:text-slate-400 ml-1">(Opsional)</span>
                </label>
                <input type="file" id="attachment-input" name="attachment" accept=".pdf,.jpg,.jpeg,.png" class="w-full border border-slate-300 dark:border-slate-700 rounded-2xl px-4 py-3 text-sm bg-slate-50 dark:bg-slate-950/80 text-slate-700 dark:text-slate-300 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 focus:outline-none transition shadow-2xs font-medium cursor-pointer file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-amber-500/10 file:text-amber-600 dark:file:text-amber-400 hover:file:bg-amber-500/20">
                @error('attachment')
                    <p class="text-xs font-semibold text-rose-600 dark:text-rose-400 mt-1.5">{{ $message }}</p>
                @enderror
                <p id="attachment-helper" class="text-xs text-slate-500 mt-1.5">Upload dokumen pendukung jika ada (Format PDF, JPG, PNG - Maks 5MB)</p>
            </div>

            <button type="submit" class="w-full bg-amber-500 hover:bg-amber-400 active:bg-amber-600 text-slate-950 font-bold py-3.5 px-5 rounded-2xl text-base transition shadow-lg shadow-amber-500/20 cursor-pointer min-h-[52px] flex items-center justify-center gap-2">
                <span>Kirim Pengajuan Cuti</span>
                <x-heroicon-o-arrow-right class="w-5 h-5 text-slate-950" />
            </button>
        </form>
    </div>

    <!-- RIWAYAT PENGAJUAN -->
    <div>
        <div class="flex items-center justify-between mb-5">
            <div>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">Riwayat Pengajuan Cuti Anda</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Daftar pengajuan cuti sebelumnya (terbaru di atas)</p>
            </div>
            <span class="text-xs font-bold bg-slate-100 dark:bg-slate-800 text-amber-600 dark:text-amber-400 px-3 py-1 rounded-full border border-slate-200 dark:border-slate-700 font-mono">
                {{ $requests->count() }} Total
            </span>
        </div>

        <div class="space-y-3">
            @forelse ($requests as $req)
                @php
                    $duration = $req->start_date && $req->end_date ? $req->start_date->diffInDays($req->end_date) + 1 : 1;
                @endphp
                <details class="group border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden bg-slate-50 dark:bg-slate-950/60 shadow-xs transition" {{ $loop->first ? 'open' : '' }}>
                    <summary class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-4 cursor-pointer select-none bg-slate-100/80 dark:bg-slate-900/90 group-open:bg-slate-100 dark:group-open:bg-slate-900 hover:bg-slate-200/60 dark:hover:bg-slate-800/80 transition">
                        <div class="flex items-center gap-3">
                            <span class="w-7 h-7 rounded-xl bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 flex items-center justify-center shrink-0">
                                <x-heroicon-o-chevron-down class="w-4 h-4 text-slate-500 dark:text-slate-400 group-open:rotate-180 transition-transform duration-200" />
                            </span>
                            <div>
                                <div class="font-bold text-base text-slate-900 dark:text-white">{{ $req->leaveType->name }}</div>
                                <div class="text-xs text-slate-500 dark:text-slate-400 font-medium mt-0.5">
                                    {{ $req->start_date->format('d M Y') }} – {{ $req->end_date->format('d M Y') }} · <span class="font-bold text-amber-600 dark:text-amber-400">{{ $duration }} Hari</span>
                                </div>
                            </div>
                        </div>
                        <div class="shrink-0 self-start sm:self-auto">
                            @if ($req->status === 'pending')
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-bold bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/30">
                                    <x-heroicon-o-clock class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400 shrink-0" />
                                    <span>Menunggu Approval</span>
                                </span>
                            @elseif ($req->status === 'approved')
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30">
                                    <x-heroicon-o-check-circle class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400 shrink-0" />
                                    <span>Disetujui</span>
                                </span>
                            @elseif ($req->status === 'rejected')
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-bold bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/30">
                                    <x-heroicon-o-x-circle class="w-3.5 h-3.5 text-rose-600 dark:text-rose-400 shrink-0" />
                                    <span>Ditolak</span>
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-xl text-xs font-bold bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                    {{ ucfirst($req->status) }}
                                </span>
                            @endif
                        </div>
                    </summary>

                    <div class="p-4 border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950/80 text-sm space-y-3">
                        <div class="bg-slate-50 dark:bg-slate-900/60 p-3 rounded-xl border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300">
                            <span class="font-bold text-slate-500 block text-xs uppercase mb-0.5">TANGGAL PENGAJUAN</span>
                            <span class="font-semibold text-slate-900 dark:text-white">{{ $req->created_at ? $req->created_at->format('d M Y, H:i') : '-' }} WIB</span>
                        </div>

                        <div>
                            <span class="font-bold text-slate-500 block text-xs uppercase mb-1">ALASAN PENGAJUAN CUTI</span>
                            <div class="bg-slate-50 dark:bg-slate-900/60 p-3 rounded-xl border border-slate-200 dark:border-slate-800 text-slate-800 dark:text-slate-200 font-medium">
                                {{ trim($req->reason) ?: 'Tidak ada alasan dicantumkan.' }}
                            </div>
                        </div>

                        @if ($req->file_path)
                            @php
                                $isSickReq = $req->leaveType && ($req->leaveType->is_sick_type || str_contains(strtolower($req->leaveType->name), 'sakit'));
                            @endphp
                            <div>
                                <span class="font-bold text-slate-500 block text-xs uppercase mb-1">
                                    {{ $isSickReq ? 'SURAT DOKTER' : 'LAMPIRAN' }}
                                </span>
                                <a href="{{ route('leave-request.attachment', $req->id) }}" target="_blank" class="inline-flex items-center gap-2 text-xs font-bold text-amber-600 dark:text-amber-400 bg-amber-500/10 hover:bg-amber-500/20 border border-amber-500/30 px-4 py-2.5 rounded-xl transition">
                                    <x-heroicon-o-paper-clip class="w-4 h-4 text-amber-600 dark:text-amber-400 shrink-0" />
                                    <span>Lihat {{ $isSickReq ? 'Surat Dokter' : 'Lampiran' }}</span>
                                </a>
                            </div>
                        @endif

                        @if ($req->status === 'rejected')
                            <div class="bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-500/30 rounded-xl p-4 text-rose-900 dark:text-rose-200 space-y-1">
                                <div class="flex items-center gap-2 font-bold text-sm text-rose-700 dark:text-rose-300">
                                    <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-rose-600 dark:text-rose-400 shrink-0" />
                                    <span>Alasan Penolakan HRD:</span>
                                </div>
                                <p class="text-sm text-rose-800 dark:text-rose-300 pl-7 font-medium">{{ trim($req->rejected_reason) ?: 'Tidak ada catatan alasan khusus.' }}</p>
                            </div>
                        @elseif ($req->status === 'approved' && $req->approved_at)
                            <div class="bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-500/30 rounded-xl p-3.5 text-emerald-900 dark:text-emerald-200 flex items-center gap-2 font-semibold text-sm">
                                <x-heroicon-o-check class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0" />
                                <span>Disetujui pada: <strong class="text-slate-900 dark:text-white">{{ $req->approved_at->format('d M Y, H:i') }} WIB</strong></span>
                            </div>
                        @endif
                    </div>
                </details>
            @empty
                <div class="text-center py-10 bg-slate-50 dark:bg-slate-950/40 border border-dashed border-slate-300 dark:border-slate-800 rounded-2xl text-slate-500 text-sm font-medium">
                    Belum ada riwayat pengajuan cuti.
                </div>
            @endforelse
        </div>
    </div>


    <script>
        document.addEventListener('DOMContentLoaded', function () {
            updateAttachmentField();

            if (typeof flatpickr !== 'undefined') {
                flatpickr("#date_range", {
                    mode: "range",
                    dateFormat: "Y-m-d",
                    altInput: true,
                    altFormat: "j F Y",
                    conjunction: " to ",
                    locale: "id",
                    minDate: "today",
                });
            }
        });

        function updateAttachmentField() {
            const select = document.getElementById('leave_type_id');
            if (!select) return;
            const selectedOption = select.options[select.selectedIndex];
            const isSick = selectedOption ? (selectedOption.getAttribute('data-sick') === '1') : false;

            const label = document.getElementById('attachment-label');
            const badge = document.getElementById('attachment-badge');
            const helper = document.getElementById('attachment-helper');
            const input = document.getElementById('attachment-input');

            if (isSick) {
                if (label) label.textContent = 'Surat Dokter';
                if (badge) {
                    badge.textContent = '(Wajib)';
                    badge.className = 'text-xs font-bold text-rose-400 ml-1';
                }
                if (helper) helper.textContent = 'Wajib mengunggah Surat Dokter / Surat Keterangan Medis (Format PDF, JPG, PNG - Maks 5MB)';
                if (input) input.required = true;
            } else {
                if (label) label.textContent = 'Lampiran';
                if (badge) {
                    badge.textContent = '(Opsional)';
                    badge.className = 'text-xs font-normal text-slate-400 ml-1';
                }
                if (helper) helper.textContent = 'Upload dokumen pendukung jika ada (Format PDF, JPG, PNG - Maks 5MB)';
                if (input) input.required = false;
            }
        }
    </script>
@endsection

