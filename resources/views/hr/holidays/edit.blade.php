@extends('layouts.app')
@section('title','Edit Hari Libur')
@section('content')
<h1>Edit Hari Libur</h1>
<div class="card">
<form method="POST" action="{{ route('hr.holidays.update',$holiday) }}">
@csrf @method('PUT')
<div class="row">
    <div class="field"><label>Tanggal</label><input type="date" name="holiday_date" value="{{ old('holiday_date',$holiday->holiday_date->format('Y-m-d')) }}" required></div>
    <div class="field"><label>Nama</label><input name="name" value="{{ old('name',$holiday->name) }}" required></div>
    <div class="field"><label>Tipe</label>
        <select name="type">
            <option value="national" @selected(old('type',$holiday->type)==='national')>national</option>
            <option value="company" @selected(old('type',$holiday->type)==='company')>company</option>
        </select>
    </div>
    <div class="field"><label>Status</label>
        <select name="is_active">
            <option value="1" @selected($holiday->is_active)>Aktif</option>
            <option value="0" @selected(!$holiday->is_active)>Nonaktif</option>
        </select>
    </div>
</div>
<div class="field"><label>Referensi keputusan (opsional)</label><input name="reference" value="{{ old('reference',$holiday->reference) }}"></div>
<br><button>Simpan</button> <a class="btn secondary" href="{{ route('hr.holidays.index') }}">Batal</a>
</form>
</div>
@endsection
