@extends('layouts.app')
@section('title','Payroll Policy')
@section('content')
<div class="row"><h1 style="flex:1">Payroll Policy</h1><a class="btn secondary" href="{{ route('finance.payroll.index') }}">Payroll</a></div>

<div class="card">
<p class="muted">Perubahan policy bersifat append-only. Versi lama tidak diedit atau dihapus. Payroll period memakai versi terbaru dengan tanggal efektif ≤ tanggal akhir periode.</p>
<form method="POST" action="{{ route('finance.payroll-policies.store') }}">@csrf
<div class="row">
<div class="field"><label>Policy</label><select name="policy_key" required>
<option value="absent_deduction">Potongan ABSENT</option>
<option value="late_deduction">Potongan Keterlambatan</option>
<option value="overtime_pay">Nilai Lembur</option>
</select></div>
<div class="field"><label>Tanggal Efektif</label><input type="date" name="effective_from" value="{{ old('effective_from') }}" required></div>
<div class="field"><label><input style="width:auto" type="checkbox" name="enabled" value="1" @checked(old('enabled'))> Aktifkan policy</label></div>
</div>
<div class="row">
<div class="field"><label>Mode</label><select name="mode">
<option value="">-- Kosong jika dinonaktifkan --</option>
<option value="fixed_per_day">fixed_per_day</option>
<option value="salary_divisor_per_day">salary_divisor_per_day</option>
<option value="fixed_per_minute">fixed_per_minute</option>
<option value="fixed_per_incident">fixed_per_incident</option>
<option value="fixed_per_hour">fixed_per_hour</option>
</select></div>
<div class="field"><label>Nilai</label><input name="value" value="{{ old('value') }}" placeholder="Contoh 100000.00 atau divisor 25"></div>
</div>
<div class="field"><label>Catatan</label><textarea name="notes" rows="3">{{ old('notes') }}</textarea></div>
<br><button>Tambah Versi Policy</button>
</form>
</div>

<div class="card">
<table>
<thead><tr><th>Efektif</th><th>Policy</th><th>Status</th><th>Mode</th><th>Nilai</th><th>Pembuat</th><th>Catatan</th></tr></thead>
<tbody>
@forelse($versions as $version)
<tr>
<td>{{ $version->effective_from->format('d-m-Y') }}</td>
<td>{{ $version->policy_key }}</td>
<td>{{ $version->enabled ? 'AKTIF' : 'NONAKTIF' }}</td>
<td>{{ $version->mode ?? '-' }}</td>
<td>{{ $version->value ?? '-' }}</td>
<td>{{ $version->creator->name }}</td>
<td>{{ $version->notes ?: '-' }}</td>
</tr>
@empty<tr><td colspan="7">Belum ada versi payroll policy. Semua policy dianggap belum dikonfigurasi.</td></tr>@endforelse
</tbody>
</table>
</div>
@endsection
