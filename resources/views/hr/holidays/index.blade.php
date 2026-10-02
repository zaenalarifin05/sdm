@extends('layouts.app')
@section('title','Kalender Hari Libur')
@section('content')
<div class="row">
    <h1 style="flex:1">Kalender Hari Libur</h1>
    <a class="btn" href="{{ route('hr.holidays.create') }}">Tambah Hari Libur</a>
</div>
<div class="card">
<table>
    <thead>
        <tr><th>Tanggal</th><th>Nama</th><th>Tipe</th><th>Referensi</th><th>Status</th><th>Aksi</th></tr>
    </thead>
    <tbody>
    @forelse($holidays as $holiday)
        <tr>
            <td>{{ $holiday->holiday_date->format('d-m-Y') }}</td>
            <td>{{ $holiday->name }}</td>
            <td>{{ $holiday->type }}</td>
            <td>{{ $holiday->reference ?: '-' }}</td>
            <td>{{ $holiday->is_active ? 'Aktif' : 'Nonaktif' }}</td>
            <td class="actions">
                <a class="btn secondary" href="{{ route('hr.holidays.edit',$holiday) }}">Edit</a>
                @if($holiday->is_active)
                <form method="POST" action="{{ route('hr.holidays.destroy',$holiday) }}" onsubmit="return confirm('Nonaktifkan hari libur ini?')">
                    @csrf @method('DELETE')
                    <button class="btn danger">Nonaktifkan</button>
                </form>
                @endif
            </td>
        </tr>
    @empty
        <tr><td colspan="6">Belum ada hari libur.</td></tr>
    @endforelse
    </tbody>
</table>
<div style="margin-top:14px">{{ $holidays->links() }}</div>
</div>
@endsection
