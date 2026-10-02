@extends('layouts.app')
@section('title','Edit Departemen')
@section('content')
<h1>Edit Departemen</h1><div class="card"><form method="POST" action="{{ route('hr.departments.update',$department) }}">@csrf @method('PUT')
<div class="row"><div class="field"><label>Kode</label><input name="code" value="{{ old('code',$department->code) }}" required></div><div class="field"><label>Nama</label><input name="name" value="{{ old('name',$department->name) }}" required></div><div class="field"><label>Status</label><select name="is_active"><option value="1" @selected($department->is_active)>Aktif</option><option value="0" @selected(!$department->is_active)>Nonaktif</option></select></div></div>
<br><button>Simpan</button> <a class="btn secondary" href="{{ route('hr.departments.index') }}">Batal</a></form></div>@endsection
