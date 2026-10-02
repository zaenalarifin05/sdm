@extends('layouts.app')
@section('title','Payroll '.$period->name)
@section('content')
<div class="row"><h1 style="flex:1">{{ $period->name }}</h1><a class="btn secondary" href="{{ route('finance.payroll.index') }}">Kembali</a></div>

<div class="card">
<p><strong>Periode:</strong> {{ $period->period_start->format('d-m-Y') }} s/d {{ $period->period_end->format('d-m-Y') }} · <strong>Status:</strong> {{ $period->status }}</p>
@if($period->status==='DRAFT')
<div class="row">
<form method="POST" action="{{ route('finance.payroll.generate',$period) }}">@csrf
<button>Generate / Refresh Draft</button>
</form>
<form method="POST" action="{{ route('finance.payroll.apply-policies',$period) }}">@csrf
<button>Apply Policy Preview</button>
</form>
<form method="POST" action="{{ route('finance.payroll.finalize',$period) }}">@csrf
<button>Finalize Payroll</button>
</form>
</div>
@endif
<p class="muted">Finalize akan ditolak bila masih ada INCOMPLETE atau policy finansial untuk ABSENT/LATE/OVERTIME yang relevan belum dikonfigurasi.</p>
</div>

<div class="card">
<h3>Status Policy</h3>
<table>
<tr><th>Potongan ABSENT</th><td>{{ config('payroll.policies.absent_deduction.enabled') ? 'READY · '.config('payroll.policies.absent_deduction.mode') : 'BELUM DIKONFIGURASI' }}</td></tr>
<tr><th>Potongan Keterlambatan</th><td>{{ config('payroll.policies.late_deduction.enabled') ? 'READY · '.config('payroll.policies.late_deduction.mode') : 'BELUM DIKONFIGURASI' }}</td></tr>
<tr><th>Nilai Lembur</th><td>{{ config('payroll.policies.overtime_pay.enabled') ? 'READY · '.config('payroll.policies.overtime_pay.mode') : 'BELUM DIKONFIGURASI' }}</td></tr>
</table>
</div>

<div class="card">
<table>
<thead><tr><th>NIP</th><th>Pegawai</th><th>Base</th><th>Allowance</th><th>Gross</th><th>Deduction</th><th>Net</th><th>Absent</th><th>Late</th><th>OT</th><th>Status</th><th>Aksi</th></tr></thead>
<tbody>
@forelse($payrolls as $payroll)
<tr>
<td>{{ $payroll->employee->nip }}</td>
<td>{{ $payroll->employee->name }}</td>
<td>Rp {{ number_format((float)$payroll->base_salary_snapshot,0,',','.') }}</td>
<td>Rp {{ number_format((float)$payroll->total_allowance,0,',','.') }}</td>
<td>Rp {{ number_format((float)$payroll->gross_pay,0,',','.') }}</td>
<td>Rp {{ number_format((float)$payroll->total_deduction,0,',','.') }}</td>
<td><strong>Rp {{ number_format((float)$payroll->net_pay,0,',','.') }}</strong></td>
<td>{{ $payroll->absent_days }}</td>
<td>{{ $payroll->late_days }} / {{ $payroll->late_minutes }} mnt</td>
<td>{{ $payroll->approved_overtime_minutes }} mnt</td>
<td>{{ $payroll->status }}</td>
<td><a class="btn secondary" href="{{ route('finance.payroll-entry.show',$payroll) }}">Detail</a></td>
</tr>
@empty<tr><td colspan="12">Draft belum digenerate.</td></tr>@endforelse
</tbody>
</table>
</div>
@endsection
