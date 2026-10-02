@extends('layouts.app')
@section('title','Compensation Pegawai')
@section('content')
<h1>Compensation Pegawai</h1>

<div class="card">
<h3>Tambah Riwayat Gaji Pokok</h3>
<form method="POST" action="{{ route('finance.compensations.store') }}">@csrf
<div class="row">
<div class="field"><label>Pegawai</label><select name="employee_id" required>@foreach($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->nip }} · {{ $employee->name }}</option>@endforeach</select></div>
<div class="field"><label>Efektif Mulai</label><input type="date" name="effective_from" value="{{ old('effective_from') }}" required></div>
<div class="field"><label>Gaji Pokok (IDR)</label><input type="number" name="base_salary" min="0" step="0.01" value="{{ old('base_salary') }}" required></div>
</div>
<div class="field"><label>Catatan</label><textarea name="notes" rows="2">{{ old('notes') }}</textarea></div>
<br><button>Simpan Riwayat</button>
</form>
</div>

<div class="card">
<table>
<thead><tr><th>NIP</th><th>Pegawai</th><th>Departemen</th><th>Efektif</th><th>Gaji Pokok</th><th>Catatan</th></tr></thead>
<tbody>
@forelse($compensations as $compensation)
<tr>
<td>{{ $compensation->employee->nip }}</td>
<td>{{ $compensation->employee->name }}</td>
<td>{{ $compensation->employee->department->name }}</td>
<td>{{ $compensation->effective_from->format('d-m-Y') }}</td>
<td>Rp {{ number_format((float)$compensation->base_salary,0,',','.') }}</td>
<td>{{ $compensation->notes ?: '-' }}</td>
</tr>
@empty<tr><td colspan="6">Belum ada compensation.</td></tr>@endforelse
</tbody>
</table>
</div>
@endsection
