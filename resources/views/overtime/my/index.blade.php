@extends('layouts.app')
@section('title','Lembur Saya')
@section('content')
<div class="row"><h1 style="flex:1">Lembur Saya</h1><a class="btn" href="{{ route('my.overtime.create') }}">Ajukan Lembur</a></div>
<div class="card">
<table><thead><tr><th>Tanggal Kerja</th><th>Shift</th><th>Waktu Lembur</th><th>Durasi</th><th>Status Attendance</th><th>Status Approval</th><th>Alasan</th></tr></thead><tbody>
@forelse($requests as $overtime)
<tr>
<td>{{ $overtime->work_date->format('d-m-Y') }}</td>
<td>{{ $overtime->shiftSchedule->shift->name }}</td>
<td>{{ $overtime->start_at->timezone(config('app.timezone'))->format('d-m-Y H:i') }} s/d {{ $overtime->end_at->timezone(config('app.timezone'))->format('d-m-Y H:i') }}</td>
<td>{{ $overtime->duration_minutes }} menit</td>
<td>{{ $overtime->shiftSchedule->attendance?->attendance_status ?? 'BELUM DIPROSES' }}</td>
<td>{{ $overtime->status }}</td>
<td>{{ $overtime->reason }}</td>
</tr>
@empty<tr><td colspan="7">Belum ada pengajuan lembur.</td></tr>@endforelse
</tbody></table>
<div style="margin-top:14px">{{ $requests->links() }}</div>
</div>
@endsection
