@extends('layouts.app')
@section('title','Approval Cuti Departemen')
@section('content')
<h1>Approval Cuti / Izin Departemen</h1>
<div class="card">
<table><thead><tr><th>Pegawai</th><th>Jenis</th><th>Periode</th><th>Hari</th><th>Alasan</th><th>Keputusan</th></tr></thead><tbody>
@forelse($requests as $leave)
<tr>
<td>{{ $leave->employee->name }}<br><span class="muted">{{ $leave->employee->nip }}</span></td>
<td>{{ $leave->leaveType->name }}</td>
<td>{{ $leave->start_date->format('d-m-Y') }} s/d {{ $leave->end_date->format('d-m-Y') }}</td>
<td>{{ $leave->requested_days }}</td>
<td>{{ $leave->reason }}</td>
<td>
<form method="POST" action="{{ route('manager.leave.approve',$leave) }}">@csrf<input name="note" placeholder="Catatan opsional"><button>Approve</button></form>
<form method="POST" action="{{ route('manager.leave.reject',$leave) }}" style="margin-top:6px">@csrf<input name="note" placeholder="Alasan penolakan" required><button class="btn danger">Reject</button></form>
</td>
</tr>
@empty<tr><td colspan="6">Tidak ada pengajuan yang menunggu approval.</td></tr>@endforelse
</tbody></table>
</div>
@endsection
