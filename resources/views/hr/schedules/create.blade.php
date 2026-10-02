@extends('layouts.app')
@section('title','Assign Shift')
@section('content')
<h1>Assign Shift</h1><div class="card"><form method="POST" action="{{ route('hr.schedules.store') }}">@csrf
<div class="row"><div class="field"><label>Pegawai</label><select name="employee_id" required>@foreach($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->nip }} · {{ $employee->name }}</option>@endforeach</select></div><div class="field"><label>Shift</label><select name="shift_id" required>@foreach($shifts as $shift)<option value="{{ $shift->id }}">{{ $shift->code }} · {{ $shift->name }}</option>@endforeach</select></div><div class="field"><label>Tanggal kerja</label><input type="date" name="work_date" value="{{ old('work_date') }}" required></div></div>
<br><button>Simpan</button> <a class="btn secondary" href="{{ route('hr.schedules.index') }}">Batal</a></form></div>@endsection
