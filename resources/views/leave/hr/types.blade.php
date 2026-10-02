@extends('layouts.app')
@section('title','Jenis Cuti & Izin')
@section('content')
<h1>Jenis Cuti & Izin</h1>
<div class="card">
<form method="POST" action="{{ route('hr.leave-types.store') }}">@csrf
<div class="row"><div class="field"><label>Kode</label><input name="code" required></div><div class="field"><label>Nama</label><input name="name" required></div><div class="field"><label>Kategori</label><select name="category"><option value="leave">leave</option><option value="permit">permit</option></select></div><div class="field"><label><input style="width:auto" type="checkbox" name="deduct_balance" value="1"> Potong saldo</label></div><button>Tambah</button></div>
</form>
</div>
<div class="card">
<table><thead><tr><th>Kode</th><th>Nama</th><th>Kategori</th><th>Potong Saldo</th><th>Status</th><th>Simpan</th></tr></thead><tbody>
@foreach($types as $type)
<tr><form method="POST" action="{{ route('hr.leave-types.update',$type) }}">@csrf @method('PUT')
<td>{{ $type->code }}</td><td><input name="name" value="{{ $type->name }}"></td><td><select name="category"><option value="leave" @selected($type->category==='leave')>leave</option><option value="permit" @selected($type->category==='permit')>permit</option></select></td><td><input style="width:auto" type="checkbox" name="deduct_balance" value="1" @checked($type->deduct_balance)></td><td><select name="is_active"><option value="1" @selected($type->is_active)>Aktif</option><option value="0" @selected(!$type->is_active)>Nonaktif</option></select></td><td><button>Simpan</button></td>
</form></tr>
@endforeach
</tbody></table>
</div>
@endsection
