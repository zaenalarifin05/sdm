@extends('layouts.app')
@section('title','Payroll '.$payroll->employee->name)
@section('content')
<div class="row"><h1 style="flex:1">Payroll {{ $payroll->employee->name }}</h1><a class="btn secondary" href="{{ route('finance.payroll.show',$payroll->period) }}">Kembali</a></div>
<div class="card">
<p>{{ $payroll->employee->nip }} · {{ $payroll->employee->department->name }} · {{ $payroll->period->name }}</p>
<table>
<tr><th>Base Salary</th><td>Rp {{ number_format((float)$payroll->base_salary_snapshot,0,',','.') }}</td></tr>
<tr><th>Total Allowance</th><td>Rp {{ number_format((float)$payroll->total_allowance,0,',','.') }}</td></tr>
<tr><th>Gross Pay</th><td>Rp {{ number_format((float)$payroll->gross_pay,0,',','.') }}</td></tr>
<tr><th>Total Deduction</th><td>Rp {{ number_format((float)$payroll->total_deduction,0,',','.') }}</td></tr>
<tr><th>Net Pay</th><td><strong>Rp {{ number_format((float)$payroll->net_pay,0,',','.') }}</strong></td></tr>
</table>
</div>
@if($payroll->status==='DRAFT')
<div class="card">
<h3>Tambah Komponen Manual</h3>
<form method="POST" action="{{ route('finance.payroll-items.store',$payroll) }}">@csrf
<div class="row">
<div class="field"><label>Tipe</label><select name="type"><option value="ALLOWANCE">ALLOWANCE</option><option value="DEDUCTION">DEDUCTION</option></select></div>
<div class="field"><label>Kode</label><input name="code" required></div>
<div class="field"><label>Nama</label><input name="name" required></div>
<div class="field"><label>Nominal</label><input type="number" name="amount" min="0.01" step="0.01" required></div>
<button>Tambah</button>
</div>
</form>
</div>
@endif
<div class="card">
<table><thead><tr><th>Tipe</th><th>Kode</th><th>Nama</th><th>Nominal</th><th>Sumber</th><th>Aksi</th></tr></thead><tbody>
@forelse($payroll->items as $item)
<tr><td>{{ $item->type }}</td><td>{{ $item->code }}</td><td>{{ $item->name }}</td><td>Rp {{ number_format((float)$item->amount,0,',','.') }}</td><td>{{ $item->source_type ?? '-' }}</td><td>
@if($payroll->status==='DRAFT')
<form method="POST" action="{{ route('finance.payroll-items.destroy',[$payroll,$item]) }}">@csrf @method('DELETE')<button class="btn danger">Hapus</button></form>
@else -
@endif
</td></tr>
@empty<tr><td colspan="6">Belum ada komponen tambahan.</td></tr>@endforelse
</tbody></table>
</div>
@endsection
