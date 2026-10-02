@extends('layouts.app')
@section('title','Edit Shift')
@section('content')
<h1>Edit Shift</h1><div class="card"><form method="POST" action="{{ route('hr.shifts.update',$shift) }}">@csrf @method('PUT')
<div class="row"><div class="field"><label>Kode</label><input name="code" value="{{ old('code',$shift->code) }}" required></div><div class="field"><label>Nama</label><input name="name" value="{{ old('name',$shift->name) }}" required></div><div class="field"><label>Mulai</label><input type="time" name="start_time" value="{{ substr($shift->start_time,0,5) }}" required></div><div class="field"><label>Selesai</label><input type="time" name="end_time" value="{{ substr($shift->end_time,0,5) }}" required></div><div class="field"><label>Status</label><select name="is_active"><option value="1" @selected($shift->is_active)>Aktif</option><option value="0" @selected(!$shift->is_active)>Nonaktif</option></select></div></div>
<br><button>Simpan</button> <a class="btn secondary" href="{{ route('hr.shifts.index') }}">Batal</a></form></div>@endsection
