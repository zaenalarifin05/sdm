@extends('layouts.app')
@section('title','Laporan HR')
@section('content')
<div class="row"><h1 style="flex:1">Laporan HR</h1></div>

<div class="card">
<form method="GET" class="row">
<div class="field"><label>Dari</label><input type="date" name="from" value="{{ $from }}" required></div>
<div class="field"><label>Sampai</label><input type="date" name="to" value="{{ $to }}" required></div>
<div class="field"><label>Departemen</label><select name="department_id"><option value="">Semua Departemen</option>@foreach($departments as $department)<option value="{{ $department->id }}" @selected($departmentId===$department->id)>{{ $department->name }}</option>@endforeach</select></div>
<button>Tampilkan</button>
</form>
</div>

<div class="card">
<h3>Ringkasan</h3>
<table>
<tr><th>Total Attendance</th><td>{{ $summary['attendance_total'] }}</td><th>Present</th><td>{{ $summary['present'] }}</td><th>Late</th><td>{{ $summary['late'] }}</td></tr>
<tr><th>Absent</th><td>{{ $summary['absent'] }}</td><th>Leave</th><td>{{ $summary['leave'] }}</td><th>Permit</th><td>{{ $summary['permit'] }}</td></tr>
<tr><th>Incomplete</th><td>{{ $summary['incomplete'] }}</td><th>Pengajuan Cuti/Izin</th><td>{{ $summary['leave_requests'] }}</td><th>Approved Overtime</th><td>{{ $summary['approved_overtime_minutes'] }} menit</td></tr>
<tr><th>Izin Keluar Sementara</th><td>{{ $summary['temporary_permission_minutes'] }} menit</td><td colspan="4"></td></tr>
</table>
</div>

@php($query=['from'=>$from,'to'=>$to,'department_id'=>$departmentId])
<div class="card">
<h3>Export CSV</h3>
<div class="row">
<a class="btn" href="{{ route('hr.reports.attendance.csv',$query) }}">Presensi</a>
<a class="btn" href="{{ route('hr.reports.leave.csv',$query) }}">Cuti & Izin</a>
<a class="btn" href="{{ route('hr.reports.temporary-permissions.csv',$query) }}">Izin Keluar</a>
<a class="btn" href="{{ route('hr.reports.overtime.csv',$query) }}">Lembur</a>
</div>
<p class="muted">CSV menggunakan UTF-8 BOM agar mudah dibuka di Excel. Filter periode dan departemen mengikuti pilihan di atas.</p>
</div>
@endsection
