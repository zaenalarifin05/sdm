@extends('layouts.app')
@section('title','Cuti & Izin Saya')
@section('content')
<div class="row"><h1 style="flex:1">Cuti & Izin Saya</h1><a class="btn" href="{{ route('my.leave.create') }}">Ajukan</a></div>

<div class="card">
<h3>Saldo Tahun {{ now()->year }}</h3>
<table><thead><tr><th>Jenis</th><th>Alokasi</th><th>Terpakai</th><th>Sisa</th></tr></thead><tbody>
@forelse($balances as $balance)
<tr><td>{{ $balance->leaveType->name }}</td><td>{{ $balance->allocated_days }}</td><td>{{ $balance->used_days }}</td><td>{{ $balance->available_days }}</td></tr>
@empty<tr><td colspan="4">Belum ada saldo cuti yang ditetapkan.</td></tr>@endforelse
</tbody></table>
</div>

<div class="card">
<table><thead><tr><th>Jenis</th><th>Periode</th><th>Hari Kerja</th><th>Status</th><th>Alasan</th></tr></thead><tbody>
@forelse($requests as $leave)
<tr><td>{{ $leave->leaveType->name }}</td><td>{{ $leave->start_date->format('d-m-Y') }} s/d {{ $leave->end_date->format('d-m-Y') }}</td><td>{{ $leave->requested_days }}</td><td>{{ $leave->status }}</td><td>{{ $leave->reason }}</td></tr>
@empty<tr><td colspan="5">Belum ada pengajuan.</td></tr>@endforelse
</tbody></table>
<div style="margin-top:14px">{{ $requests->links() }}</div>
</div>
@endsection
