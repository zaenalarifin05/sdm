@extends('layouts.app')
@section('title','Tambah Hari Libur')
@section('content')
<h1>Tambah Hari Libur</h1>
<div class="card">
<form method="POST" action="{{ route('hr.holidays.store') }}">
@csrf
<div class="row">
    <div class="field"><label>Tanggal</label><input type="date" name="holiday_date" value="{{ old('holiday_date') }}" required></div>
    <div class="field"><label>Nama</label><input name="name" value="{{ old('name') }}" required></div>
    <div class="field"><label>Tipe</label>
        <select name="type">
            <option value="national">national</option>
            <option value="company">company</option>
        </select>
    </div>
</div>
<div class="field"><label>Referensi keputusan (opsional)</label><input name="reference" value="{{ old('reference') }}" placeholder="Contoh: SKB 3 Menteri ..."></div>
<br><button>Simpan</button> <a class="btn secondary" href="{{ route('hr.holidays.index') }}">Batal</a>
</form>
</div>
@endsection
