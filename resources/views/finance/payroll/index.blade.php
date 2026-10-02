@extends('layouts.app')
@section('title','Payroll')
@section('content')
<h1>Payroll</h1>

<div class="card">
<h3>Buat Payroll Period</h3>
<form method="POST" action="{{ route('finance.payroll.store') }}">@csrf
<div class="row">
<div class="field"><label>Nama Periode</label><input name="name" value="{{ old('name') }}" placeholder="Oktober 2026" required></div>
<div class="field"><label>Mulai</label><input type="date" name="period_start" value="{{ old('period_start') }}" required></div>
<div class="field"><label>Selesai</label><input type="date" name="period_end" value="{{ old('period_end') }}" required></div>
<button>Buat Periode</button>
</div>
</form>
</div>

<div class="card">
<table>
<thead><tr><th>Periode</th><th>Rentang</th><th>Status</th><th>Draft Pegawai</th><th>Aksi</th></tr></thead>
<tbody>
@forelse($periods as $period)
<tr>
<td>{{ $period->name }}</td>
<td>{{ $period->period_start->format('d-m-Y') }} s/d {{ $period->period_end->format('d-m-Y') }}</td>
<td>{{ $period->status }}</td>
<td>{{ $period->payrolls_count }}</td>
<td><a class="btn secondary" href="{{ route('finance.payroll.show',$period) }}">Buka</a></td>
</tr>
@empty<tr><td colspan="5">Belum ada payroll period.</td></tr>@endforelse
</tbody>
</table>
</div>
@endsection
