@extends('layouts.app')
@section('title','Payroll Recap')
@section('content')
<div class="row"><h1 style="flex:1">Payroll Recap</h1></div>

<div class="card">
<form method="GET" class="row">
<div class="field"><label>Payroll Period</label><select name="payroll_period_id">
@foreach($periods as $item)
<option value="{{ $item->id }}" @selected($period?->id===$item->id)>{{ $item->name }} · {{ $item->status }}</option>
@endforeach
</select></div>
<button>Tampilkan</button>
</form>
</div>

@if($period)
<div class="card">
<h3>{{ $period->name }} · {{ $period->status }}</h3>
<table>
<tr><th>Jumlah Pegawai</th><td>{{ $summary['employees'] }}</td></tr>
<tr><th>Gross Pay</th><td>Rp {{ number_format((float)$summary['gross_pay'],0,',','.') }}</td></tr>
<tr><th>Total Deduction</th><td>Rp {{ number_format((float)$summary['deduction'],0,',','.') }}</td></tr>
<tr><th>Net Pay</th><td><strong>Rp {{ number_format((float)$summary['net_pay'],0,',','.') }}</strong></td></tr>
</table>
<br><a class="btn" href="{{ route('finance.reports.payroll.csv',['payroll_period_id'=>$period->id]) }}">Export CSV</a>
</div>
@else
<div class="card">Belum ada payroll period.</div>
@endif
@endsection
