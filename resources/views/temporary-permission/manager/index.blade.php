@extends('layouts.app')
@section('title','Approval Izin Keluar')
@section('content')
<h1>Approval Izin Keluar Sementara</h1>
<div class="card">
<table>
<thead><tr><th>Pegawai</th><th>Tanggal/Shift</th><th>Alasan</th><th>Keputusan</th></tr></thead>
<tbody>
@forelse($permissions as $permission)
<tr>
<td>{{ $permission->employee->name }}<br><span class="muted">{{ $permission->employee->nip }}</span></td>
<td>{{ $permission->shiftSchedule->work_date->format('d-m-Y') }}<br>{{ $permission->shiftSchedule->shift->name }}</td>
<td>{{ $permission->reason }}</td>
<td>
<form method="POST" action="{{ route('manager.temporary-permissions.approve',$permission) }}">@csrf<input name="note" placeholder="Catatan opsional"><button>Approve</button></form>
<form method="POST" action="{{ route('manager.temporary-permissions.reject',$permission) }}" style="margin-top:6px">@csrf<input name="note" placeholder="Alasan penolakan" required><button class="btn danger">Reject</button></form>
</td>
</tr>
@empty<tr><td colspan="4">Tidak ada izin keluar menunggu approval.</td></tr>@endforelse
</tbody></table>
</div>
@endsection
