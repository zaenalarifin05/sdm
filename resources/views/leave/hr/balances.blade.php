@extends('layouts.app')
@section('title','Saldo Cuti')
@section('content')
<h1>Saldo Cuti</h1>
<div class="card">
<form method="POST" action="{{ route('hr.leave-balances.store') }}">@csrf
<div class="row">
<div class="field"><label>Pegawai</label><select name="employee_id">@foreach($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->nip }} · {{ $employee->name }}</option>@endforeach</select></div>
<div class="field"><label>Jenis</label><select name="leave_type_id">@foreach($leaveTypes as $type)<option value="{{ $type->id }}">{{ $type->name }}</option>@endforeach</select></div>
<div class="field"><label>Tahun</label><input type="number" name="year" value="{{ $year }}" required></div>
<div class="field"><label>Alokasi Hari</label><input type="number" name="allocated_days" min="0" max="366" required></div>
<button>Simpan</button>
</div>
</form>
</div>
<div class="card">
<form method="GET" class="row"><div class="field"><label>Tahun</label><input type="number" name="year" value="{{ $year }}"></div><button>Tampilkan</button></form>
<table><thead><tr><th>Pegawai</th><th>Departemen</th><th>Jenis</th><th>Alokasi</th><th>Terpakai</th><th>Sisa</th></tr></thead><tbody>
@forelse($balances as $balance)
<tr><td>{{ $balance->employee->name }}</td><td>{{ $balance->employee->department->name }}</td><td>{{ $balance->leaveType->name }}</td><td>{{ $balance->allocated_days }}</td><td>{{ $balance->used_days }}</td><td>{{ $balance->available_days }}</td></tr>
@empty<tr><td colspan="6">Belum ada saldo untuk tahun ini.</td></tr>@endforelse
</tbody></table>
</div>
@endsection
