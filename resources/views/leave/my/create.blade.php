@extends('layouts.app')
@section('title','Ajukan Cuti / Izin')
@section('content')
<h1>Ajukan Cuti / Izin</h1>
<div class="card"><form method="POST" action="{{ route('my.leave.store') }}">@csrf
<div class="row">
<div class="field"><label>Jenis</label><select name="leave_type_id" required>@foreach($leaveTypes as $type)<option value="{{ $type->id }}" @selected(old('leave_type_id')==$type->id)>{{ $type->name }}</option>@endforeach</select></div>
<div class="field"><label>Tanggal Mulai</label><input type="date" name="start_date" value="{{ old('start_date') }}" required></div>
<div class="field"><label>Tanggal Selesai</label><input type="date" name="end_date" value="{{ old('end_date') }}" required></div>
</div>
<div class="field"><label>Alasan</label><textarea name="reason" rows="4" style="width:100%" required>{{ old('reason') }}</textarea></div>
<p class="muted">Jumlah hari dihitung dari ShiftSchedule aktif dalam rentang tanggal pengajuan.</p>
<button>Kirim Pengajuan</button> <a class="btn secondary" href="{{ route('my.leave.index') }}">Batal</a>
</form></div>
@endsection
