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
            <x-heroicon-o-arrow-left class="w-4 h-4 text-gray-600 shrink-0" />
            <span>Kembali</span>
        </a>
    </div>

    <!-- TOAST ALERT NOTIFICATION -->
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

    @if ($errors->any())
        <div id="toast-error" class="flex items-center justify-between p-4 mb-6 text-sm text-rose-900 bg-rose-50 border border-rose-300 rounded-xl" role="alert">
            <div class="flex items-center gap-3">
                <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-rose-600 shrink-0" />
                <div>
                    <span class="font-bold text-base block text-rose-950">Gagal Mengirim Pengajuan Cuti</span>
                    <span class="text-xs text-rose-800 font-medium">{{ $errors->first() }}</span>
                </div>
            </div>
            <button type="button" onclick="document.getElementById('toast-error').remove()" class="text-rose-600 hover:text-rose-900 p-1.5 rounded-lg hover:bg-rose-100/50 transition cursor-pointer flex items-center justify-center">
                <x-heroicon-o-x-mark class="w-5 h-5" />
            </button>
        </div>
    @endif

    <!-- FORM AJUKAN CUTI BARU -->
    <div class="mb-8 pb-8 border-b border-gray-200">
        <div class="mb-5">
            <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                <x-heroicon-o-plus class="w-5 h-5 text-amber-500 shrink-0" />
                Ajukan Cuti Baru
            </h2>
            <p class="text-xs text-gray-500 mt-1">Isi formulir di bawah ini untuk mengajukan izin atau cuti baru.</p>
        </div>

        <form method="POST" action="{{ route('leave-request.submit', $company->code) }}" enctype="multipart/form-data" class="space-y-5">
            @csrf
            <input type="hidden" name="employee_number" value="{{ $employee->employee_number }}">

            <div>
                <label class="block text-sm font-bold text-gray-800 mb-1">Pilih Jenis Cuti</label>
                <select id="leave_type_id" name="leave_type_id" onchange="updateAttachmentField()" class="w-full border border-gray-300 rounded-xl px-4 py-3 text-base bg-white text-gray-900 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 focus:outline-none transition shadow-2xs font-medium min-h-[48px]" required>
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
                    <p class="text-xs font-semibold text-rose-600 mt-1.5 flex items-center gap-1">
                        <x-heroicon-o-exclamation-circle class="w-4 h-4 shrink-0" />
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-bold text-gray-800 mb-1">Tanggal Mulai Cuti</label>
                    <input type="date" name="start_date" class="w-full border border-gray-300 rounded-xl px-4 py-3 text-base bg-white text-gray-900 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 focus:outline-none transition shadow-2xs font-medium min-h-[48px]" required>
                    @error('start_date')
                        <p class="text-xs font-semibold text-rose-600 mt-1.5 flex items-center gap-1">
                            <x-heroicon-o-exclamation-circle class="w-4 h-4 shrink-0" />
                            <span>{{ $message }}</span>
                        </p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-800 mb-1">Tanggal Selesai Cuti</label>
                    <input type="date" name="end_date" class="w-full border border-gray-300 rounded-xl px-4 py-3 text-base bg-white text-gray-900 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 focus:outline-none transition shadow-2xs font-medium min-h-[48px]" required>
                    @error('end_date')
                        <p class="text-xs font-semibold text-rose-600 mt-1.5 flex items-center gap-1">
                            <x-heroicon-o-exclamation-circle class="w-4 h-4 shrink-0" />
                            <span>{{ $message }}</span>
                        </p>
                    @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-800 mb-1">Alasan Pengajuan Cuti</label>
                <textarea name="reason" rows="3" placeholder="Tuliskan keperluan cuti Anda secara singkat dan jelas..." class="w-full border border-gray-300 rounded-xl px-4 py-3 text-base bg-white text-gray-900 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 focus:outline-none transition shadow-2xs font-medium"></textarea>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-800 mb-1">
                    <span id="attachment-label">Lampiran</span>
                    <span id="attachment-badge" class="text-xs font-normal text-gray-500 ml-1">(Opsional)</span>
                </label>
                <input type="file" id="attachment-input" name="attachment" accept=".pdf,.jpg,.jpeg,.png" class="w-full border border-gray-300 rounded-xl px-3.5 py-2.5 text-sm bg-white text-gray-900 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 focus:outline-none transition shadow-2xs font-medium cursor-pointer">
                @error('attachment')
                    <p class="text-xs font-semibold text-rose-600 mt-1.5">{{ $message }}</p>
                @enderror
                <p id="attachment-helper" class="text-xs text-gray-500 mt-1">Upload dokumen pendukung jika ada (Format PDF, JPG, PNG - Maks 5MB)</p>
            </div>

            <button type="submit" class="w-full bg-amber-500 hover:bg-amber-600 active:bg-amber-700 text-white font-bold py-3.5 px-5 rounded-xl text-base transition shadow-xs cursor-pointer min-h-[50px] flex items-center justify-center gap-2">
                <span>Kirim Pengajuan Cuti</span>
                <x-heroicon-o-arrow-right class="w-5 h-5 text-white" />
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
                                <x-heroicon-o-chevron-down class="w-4 h-4 text-gray-600 group-open:rotate-180 transition-transform duration-200" />
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
                                    <x-heroicon-o-clock class="w-3.5 h-3.5 text-amber-700 shrink-0" />
                                    <span>Menunggu Approval</span>
                                </span>
                            @elseif ($req->status === 'approved')
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-bold bg-emerald-100 text-emerald-900 border border-emerald-300">
                                    <x-heroicon-o-check-circle class="w-3.5 h-3.5 text-emerald-700 shrink-0" />
                                    <span>Disetujui</span>
                                </span>
                            @elseif ($req->status === 'rejected')
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-bold bg-rose-100 text-rose-900 border border-rose-300">
                                    <x-heroicon-o-x-circle class="w-3.5 h-3.5 text-rose-700 shrink-0" />
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

                        @if ($req->file_path)
                            @php
                                $isSickReq = $req->leaveType && ($req->leaveType->is_sick_type || str_contains(strtolower($req->leaveType->name), 'sakit'));
                            @endphp
                            <div>
                                <span class="font-bold text-gray-500 block text-xs uppercase mb-1">
                                    {{ $isSickReq ? 'SURAT DOKTER' : 'LAMPIRAN' }}
                                </span>
                                <a href="{{ route('leave-request.attachment', $req->id) }}" target="_blank" class="inline-flex items-center gap-1.5 text-xs font-bold text-amber-900 bg-amber-50 hover:bg-amber-100 border border-amber-300 px-3.5 py-2 rounded-lg transition">
                                    <x-heroicon-o-paper-clip class="w-4 h-4 text-amber-700 shrink-0" />
                                    <span>Lihat {{ $isSickReq ? 'Surat Dokter' : 'Lampiran' }}</span>
                                </a>
                            </div>
                        @endif

                        @if ($req->status === 'rejected')
                            <div class="bg-rose-50 border border-rose-300 rounded-lg p-3.5 text-rose-900 space-y-1">
                                <div class="flex items-center gap-2 font-bold text-sm text-rose-900">
                                    <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-rose-600 shrink-0" />
                                    <span>Alasan Penolakan HRD:</span>
                                </div>
                                <p class="text-sm text-rose-800 pl-7 font-medium">{{ trim($req->rejected_reason) ?: 'Tidak ada catatan alasan khusus.' }}</p>
                            </div>
                        @elseif ($req->status === 'approved' && $req->approved_at)
                            <div class="bg-emerald-50 border border-emerald-300 rounded-lg p-3 text-emerald-900 flex items-center gap-2 font-semibold text-sm">
                                <x-heroicon-o-check class="w-5 h-5 text-emerald-600 shrink-0" />
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

    <script>
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
                    badge.className = 'text-xs font-bold text-rose-600 ml-1';
                }
                if (helper) helper.textContent = 'Wajib mengunggah Surat Dokter / Surat Keterangan Medis (Format PDF, JPG, PNG - Maks 5MB)';
                if (input) input.required = true;
            } else {
                if (label) label.textContent = 'Lampiran';
                if (badge) {
                    badge.textContent = '(Opsional)';
                    badge.className = 'text-xs font-normal text-gray-500 ml-1';
                }
                if (helper) helper.textContent = 'Upload dokumen pendukung jika ada (Format PDF, JPG, PNG - Maks 5MB)';
                if (input) input.required = false;
            }
        }

        document.addEventListener('DOMContentLoaded', updateAttachmentField);
    </script>
@endsection
