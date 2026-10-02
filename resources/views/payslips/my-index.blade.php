@extends('layouts.app')
@section('title','Slip Gaji Saya')
@section('content')
<h1>Slip Gaji Saya</h1>
<div class="card">
<table>
<thead><tr><th>Periode</th><th>Finalized</th><th>Gross</th><th>Deduction</th><th>Net</th><th>Aksi</th></tr></thead>
<tbody>
@forelse($payrolls as $payroll)
<tr>
<td>{{ $payroll->period->name }}</td>
<td>{{ $payroll->finalized_at?->timezone(config('app.timezone'))->format('d-m-Y H:i') }}</td>
<td>Rp {{ number_format((float)$payroll->gross_pay,0,',','.') }}</td>
<td>Rp {{ number_format((float)$payroll->total_deduction,0,',','.') }}</td>
<td><strong>Rp {{ number_format((float)$payroll->net_pay,0,',','.') }}</strong></td>
<td><a class="btn secondary" href="{{ route('my.payslips.show',$payroll) }}">Lihat Slip</a></td>
</tr>
@empty<tr><td colspan="6">Belum ada payroll finalized.</td></tr>@endforelse
</tbody>
</table>
<div style="margin-top:14px">{{ $payrolls->links() }}</div>
</div>
@endsection
