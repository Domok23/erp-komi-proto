@extends('layouts.leave-request')

@section('content')
    <h1 class="text-xl font-semibold mb-4">Cek / Ajukan Cuti — {{ $company->name }}</h1>

    <form method="POST" action="{{ route('leave-request.lookup.submit', $company->code) }}" class="space-y-4">
        @csrf
        <label class="block text-sm font-medium">Nomor Karyawan</label>
        <input type="text" name="employee_number" class="border rounded-sm px-3 py-2 w-full" required>
        @error('employee_number')
            <p class="text-sm text-red-600">{{ $message }}</p>
        @enderror
        <button type="submit" class="bg-black text-white px-4 py-2 rounded-sm">Lanjut</button>
    </form>
@endsection
