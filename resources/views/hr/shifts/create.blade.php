@extends('layouts.app')
@section('title','Tambah Shift')
@section('content')
<h1>Tambah Shift</h1><div class="card"><form method="POST" action="{{ route('hr.shifts.store') }}">@csrf
<div class="row"><div class="field"><label>Kode</label><input name="code" required></div><div class="field"><label>Nama</label><input name="name" required></div><div class="field"><label>Mulai</label><input type="time" name="start_time" required></div><div class="field"><label>Selesai</label><input type="time" name="end_time" required></div></div>
<p class="muted">Lintas hari dihitung otomatis bila jam selesai ≤ jam mulai.</p><button>Simpan</button> <a class="btn secondary" href="{{ route('hr.shifts.index') }}">Batal</a></form></div>@endsection
