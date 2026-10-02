@extends('layouts.app')
@section('title','Tambah Pegawai')
@section('content')
<h1>Tambah Pegawai</h1>
<div class="card"><form method="POST" action="{{ route('hr.employees.store') }}">@csrf
<div class="row"><div class="field"><label>NIP</label><input name="nip" value="{{ old('nip') }}" required></div><div class="field"><label>Nama</label><input name="name" value="{{ old('name') }}" required></div></div>
<div class="row"><div class="field"><label>Departemen</label><select name="department_id" required>@foreach($departments as $department)<option value="{{ $department->id }}" @selected(old('department_id')==$department->id)>{{ $department->name }}</option>@endforeach</select></div><div class="field"><label>Tanggal Masuk</label><input type="date" name="join_date" value="{{ old('join_date') }}"></div><div class="field"><label>Status</label><select name="employment_status"><option value="active">active</option><option value="inactive">inactive</option><option value="resigned">resigned</option></select></div></div>
<div class="row"><div class="field"><label>PIN Presensi (6 digit)</label><input name="pin" inputmode="numeric" pattern="\d{6}" maxlength="6" required></div></div>
<hr>
<h3>Akun Portal (opsional)</h3>
<div class="row"><div class="field"><label>Email</label><input type="email" name="portal_email" value="{{ old('portal_email') }}"></div><div class="field"><label>Password (min. 12 karakter)</label><input type="password" name="portal_password"></div></div>
<p class="muted">Akun portal diperlukan untuk pengajuan cuti/izin dan approval manager.</p>
<br><button>Simpan</button> <a class="btn secondary" href="{{ route('hr.employees.index') }}">Batal</a></form></div>
@endsection
