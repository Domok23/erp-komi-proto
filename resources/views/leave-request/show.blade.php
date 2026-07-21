@extends('layouts.leave-request')

@section('content')
    <h1 class="text-xl font-semibold mb-2">{{ $employee->name }}</h1>
    <p class="text-sm text-gray-600 mb-6">Nomor Karyawan: {{ $employee->employee_number }}</p>

    @if (session('status'))
        <div class="bg-green-50 text-green-700 px-4 py-2 rounded-sm mb-4">{{ session('status') }}</div>
    @endif

    <h2 class="font-medium mb-2">Riwayat Pengajuan</h2>
    @forelse ($requests as $req)
        <div class="border rounded-sm p-3 mb-2 {{ $req->status === 'pending' ? 'border-amber-400' : '' }}">
            <div class="flex justify-between text-sm">
                <span>{{ $req->leaveType->name }} · {{ $req->start_date->format('d M Y') }} - {{ $req->end_date->format('d M Y') }}</span>
                <span class="font-medium">{{ ucfirst($req->status) }}</span>
            </div>
        </div>
    @empty
        <p class="text-sm text-gray-500 mb-4">Belum ada riwayat.</p>
    @endforelse

    <h2 class="font-medium mt-6 mb-2">Ajukan Baru</h2>
    <form method="POST" action="{{ route('leave-request.submit', $company->code) }}" class="space-y-4">
        @csrf
        <input type="hidden" name="employee_number" value="{{ $employee->employee_number }}">

        <label class="block text-sm font-medium">Jenis</label>
        <select name="leave_type_id" class="border rounded-sm px-3 py-2 w-full" required>
            @foreach ($leaveTypes as $type)
                <option value="{{ $type->id }}">{{ $type->name }}</option>
            @endforeach
        </select>

        <label class="block text-sm font-medium">Tanggal Mulai</label>
        <input type="date" name="start_date" class="border rounded-sm px-3 py-2 w-full" required>

        <label class="block text-sm font-medium">Tanggal Selesai</label>
        <input type="date" name="end_date" class="border rounded-sm px-3 py-2 w-full" required>

        <label class="block text-sm font-medium">Alasan</label>
        <textarea name="reason" class="border rounded-sm px-3 py-2 w-full"></textarea>

        <button type="submit" class="bg-black text-white px-4 py-2 rounded-sm">Kirim</button>
    </form>
@endsection
