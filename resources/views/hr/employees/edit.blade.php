@extends('layouts.app')
@section('title','Edit Pegawai')
@section('content')
<h1>Edit Pegawai</h1><div class="card"><form method="POST" action="{{ route('hr.employees.update',$employee) }}">@csrf @method('PUT')
<div class="row"><div class="field"><label>NIP</label><input name="nip" value="{{ old('nip',$employee->nip) }}" required></div><div class="field"><label>Nama</label><input name="name" value="{{ old('name',$employee->name) }}" required></div></div>
<div class="row"><div class="field"><label>Departemen</label><select name="department_id" required>@foreach($departments as $department)<option value="{{ $department->id }}" @selected(old('department_id',$employee->department_id)==$department->id)>{{ $department->name }}</option>@endforeach</select></div><div class="field"><label>Tanggal Masuk</label><input type="date" name="join_date" value="{{ old('join_date',optional($employee->join_date)->format('Y-m-d')) }}"></div><div class="field"><label>Status</label><select name="employment_status">@foreach(['active','inactive','resigned'] as $status)<option value="{{ $status }}" @selected(old('employment_status',$employee->employment_status)===$status)>{{ $status }}</option>@endforeach</select></div></div>
<div class="row"><div class="field"><label>PIN Baru (opsional, 6 digit)</label><input name="pin" inputmode="numeric" pattern="\d{6}" maxlength="6"></div></div>
<br><button>Simpan</button> <a class="btn secondary" href="{{ route('hr.employees.index') }}">Batal</a></form></div>@endsection
