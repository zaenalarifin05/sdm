@extends('layouts.app')
@section('title','Tambah Departemen')
@section('content')
<h1>Tambah Departemen</h1><div class="card"><form method="POST" action="{{ route('hr.departments.store') }}">@csrf
<div class="row"><div class="field"><label>Kode</label><input name="code" value="{{ old('code') }}" required></div><div class="field"><label>Nama</label><input name="name" value="{{ old('name') }}" required></div></div>
<br><button>Simpan</button> <a class="btn secondary" href="{{ route('hr.departments.index') }}">Batal</a></form></div>@endsection
