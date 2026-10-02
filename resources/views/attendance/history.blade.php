@extends('layouts.app')
@section('title','Riwayat Presensi')
@section('content')
<h1>Riwayat Presensi</h1>
<div class="card">
    <p><strong>{{ $employee->name }}</strong> · {{ $employee->nip }}</p>
    <table>
        <thead><tr><th>Tanggal Kerja</th><th>Shift</th><th>Check-In</th><th>Check-Out</th><th>Terlambat</th><th>Izin Keluar</th><th>Status</th></tr></thead>
        <tbody>
        @forelse($attendances as $attendance)
            <tr>
                <td>{{ $attendance->work_date->format('d-m-Y') }}</td>
                <td>{{ $attendance->shiftSchedule->shift->name }}</td>
                <td>{{ $attendance->check_in_at?->timezone(config('app.timezone'))->format('d-m-Y H:i:s') ?? '-' }}</td>
                <td>{{ $attendance->check_out_at?->timezone(config('app.timezone'))->format('d-m-Y H:i:s') ?? '-' }}</td>
                <td>{{ $attendance->late_minutes !== null ? $attendance->late_minutes.' menit' : '-' }}</td>
                <td>{{ $attendance->temporary_permission_minutes }} menit</td>
                <td>{{ $attendance->attendance_status ?? strtoupper($attendance->state) }}</td>
            </tr>
        @empty
            <tr><td colspan="7">Belum ada data presensi.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div style="margin-top:14px">{{ $attendances->links() }}</div>
</div>
@endsection
