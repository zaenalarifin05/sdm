@extends('layouts.app')
@section('title','Payroll '.$period->name)
@section('content')
<div class="row"><h1 style="flex:1">{{ $period->name }}</h1><a class="btn secondary" href="{{ route('finance.payroll.index') }}">Kembali</a></div>
<div class="card">
<p><strong>Periode:</strong> {{ $period->period_start->format('d-m-Y') }} s/d {{ $period->period_end->format('d-m-Y') }} · <strong>Status:</strong> {{ $period->status }}</p>
<form method="POST" action="{{ route('finance.payroll.generate',$period) }}">@csrf
<button>Generate / Refresh Draft</button>
<span class="muted">Draft hanya dibuat bila compensation efektif tersedia dan seluruh jadwal periode sudah memiliki attendance status.</span>
</form>
</div>

<div class="card">
<table>
<thead><tr><th>NIP</th><th>Pegawai</th><th>Base Salary Snapshot</th><th>Jadwal</th><th>Present</th><th>Late</th><th>Late Min.</th><th>Absent</th><th>Leave</th><th>Permit</th><th>Incomplete</th><th>Approved OT</th><th>Status</th></tr></thead>
<tbody>
@forelse($payrolls as $payroll)
<tr>
<td>{{ $payroll->employee->nip }}</td>
<td>{{ $payroll->employee->name }}</td>
<td>Rp {{ number_format((float)$payroll->base_salary_snapshot,0,',','.') }}</td>
<td>{{ $payroll->scheduled_days }}</td>
<td>{{ $payroll->present_days }}</td>
<td>{{ $payroll->late_days }}</td>
<td>{{ $payroll->late_minutes }}</td>
<td>{{ $payroll->absent_days }}</td>
<td>{{ $payroll->leave_days }}</td>
<td>{{ $payroll->permit_days }}</td>
<td>{{ $payroll->incomplete_days }}</td>
<td>{{ $payroll->approved_overtime_minutes }} menit</td>
<td>{{ $payroll->status }}</td>
</tr>
@empty<tr><td colspan="13">Draft belum digenerate.</td></tr>@endforelse
</tbody>
</table>
<p class="muted">Nilai allowance, deduction, tarif overtime, gross pay, dan net pay belum dihitung pada tahap foundation.</p>
</div>
@endsection
