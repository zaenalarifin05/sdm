@extends('layouts.app')
@section('title','Monitor Presensi')
@section('content')
<div class="row"><h1 style="flex:1">Monitor Presensi</h1></div>
<div class="card"><form method="GET" class="row"><div class="field"><label>Tanggal Kerja</label><input type="date" name="work_date" value="{{ $workDate }}" required></div><button type="submit">Tampilkan</button></form></div>
@if($holiday)<div class="notice"><strong>Hari Libur: {{ $holiday->name }}</strong> ({{ $holiday->type }}) @if($holiday->reference) · {{ $holiday->reference }} @endif<br><span class="muted">Jadwal shift yang tetap ditetapkan pada tanggal ini masih wajib diproses sebagai hari kerja.</span></div>@endif
<div class="card"><form method="POST" action="{{ route('hr.attendance.process') }}">@csrf<input type="hidden" name="work_date" value="{{ $workDate }}"><button type="submit">Proses Status Presensi</button> <span class="muted">Toleransi terlambat: {{ config('attendance.late_tolerance_minutes') }} menit. Izin keluar maksimal: {{ config('temporary_permission.max_allowed_minutes') }} menit/hari kerja.</span></form></div>
<div class="card">
<table>
<thead><tr><th>NIP</th><th>Pegawai</th><th>Departemen</th><th>Shift</th><th>Check-In</th><th>Check-Out</th><th>Terlambat</th><th>Izin Keluar</th><th>Status</th></tr></thead>
<tbody>
@forelse($schedules as $schedule)
@php($attendance = $schedule->attendance)
<tr>
<td>{{ $schedule->employee->nip }}</td><td>{{ $schedule->employee->name }}</td><td>{{ $schedule->employee->department->name }}</td><td>{{ $schedule->shift->name }}</td>
<td>{{ $attendance?->check_in_at?->timezone(config('app.timezone'))->format('H:i:s') ?? '-' }}</td>
<td>{{ $attendance?->check_out_at?->timezone(config('app.timezone'))->format('H:i:s') ?? '-' }}</td>
<td>{{ $attendance?->late_minutes !== null ? $attendance->late_minutes.' menit' : '-' }}</td>
<td>{{ $attendance ? $attendance->temporary_permission_minutes.' menit' : '-' }}</td>
<td>{{ $attendance?->attendance_status ?? 'PENDING' }}</td>
</tr>
@empty<tr><td colspan="9">{{ $holiday ? 'Hari libur tanpa jadwal kerja aktif.' : 'Tidak ada jadwal aktif pada tanggal ini.' }}</td></tr>@endforelse
</tbody></table>
</div>
@endsection
