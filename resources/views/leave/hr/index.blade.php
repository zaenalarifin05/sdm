@extends('layouts.app')
@section('title','Final Approval Cuti')
@section('content')
<h1>Final Approval HR</h1>
<div class="card">
<table><thead><tr><th>Pegawai</th><th>Departemen</th><th>Jenis</th><th>Periode</th><th>Hari</th><th>Approval Atasan</th><th>Keputusan</th></tr></thead><tbody>
@forelse($requests as $leave)
@php($managerApproval=$leave->approvals->firstWhere('stage','manager'))
<tr>
<td>{{ $leave->employee->name }}<br><span class="muted">{{ $leave->employee->nip }}</span></td>
<td>{{ $leave->employee->department->name }}</td>
<td>{{ $leave->leaveType->name }}</td>
<td>{{ $leave->start_date->format('d-m-Y') }} s/d {{ $leave->end_date->format('d-m-Y') }}</td>
<td>{{ $leave->requested_days }}</td>
<td>{{ $managerApproval?->approver?->name ?? '-' }}<br><span class="muted">{{ $managerApproval?->note }}</span></td>
<td>
<form method="POST" action="{{ route('hr.leave.approve',$leave) }}">@csrf<input name="note" placeholder="Catatan opsional"><button>Approve Final</button></form>
<form method="POST" action="{{ route('hr.leave.reject',$leave) }}" style="margin-top:6px">@csrf<input name="note" placeholder="Alasan penolakan" required><button class="btn danger">Reject</button></form>
</td>
</tr>
@empty<tr><td colspan="7">Tidak ada pengajuan menunggu HR.</td></tr>@endforelse
</tbody></table>
</div>
@endsection
