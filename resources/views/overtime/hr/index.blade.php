@extends('layouts.app')
@section('title','Final Approval Lembur')
@section('content')
<h1>Final Approval Lembur HR</h1>
<div class="card">
<table><thead><tr><th>Pegawai</th><th>Departemen</th><th>Tanggal/Shift</th><th>Waktu</th><th>Durasi</th><th>Attendance</th><th>Approval Atasan</th><th>Keputusan</th></tr></thead><tbody>
@forelse($requests as $overtime)
@php($managerApproval=$overtime->approvals->firstWhere('stage','manager'))
<tr>
<td>{{ $overtime->employee->name }}<br><span class="muted">{{ $overtime->employee->nip }}</span></td>
<td>{{ $overtime->employee->department->name }}</td>
<td>{{ $overtime->work_date->format('d-m-Y') }}<br>{{ $overtime->shiftSchedule->shift->name }}</td>
<td>{{ $overtime->start_at->timezone(config('app.timezone'))->format('d-m-Y H:i') }}<br>s/d {{ $overtime->end_at->timezone(config('app.timezone'))->format('d-m-Y H:i') }}</td>
<td>{{ $overtime->duration_minutes }} menit</td>
<td>{{ $overtime->shiftSchedule->attendance?->attendance_status ?? 'BELUM DIPROSES' }}</td>
<td>{{ $managerApproval?->approver?->name ?? '-' }}<br><span class="muted">{{ $managerApproval?->note }}</span></td>
<td>
<form method="POST" action="{{ route('hr.overtime.approve',$overtime) }}">@csrf<input name="note" placeholder="Catatan opsional"><button>Approve Final</button></form>
<form method="POST" action="{{ route('hr.overtime.reject',$overtime) }}" style="margin-top:6px">@csrf<input name="note" placeholder="Alasan penolakan" required><button class="btn danger">Reject</button></form>
</td>
</tr>
@empty<tr><td colspan="8">Tidak ada pengajuan lembur menunggu HR.</td></tr>@endforelse
</tbody></table>
</div>
@endsection
