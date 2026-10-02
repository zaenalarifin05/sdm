@extends('layouts.app')
@section('title','Ajukan Izin Keluar')
@section('content')
<h1>Ajukan Izin Keluar Sementara</h1>
<div class="card">
<form method="POST" action="{{ route('my.temporary-permissions.store') }}">@csrf
<div class="field"><label>Jadwal Kerja</label><select name="shift_schedule_id" required>
@foreach($schedules as $schedule)
<option value="{{ $schedule->id }}" @selected(old('shift_schedule_id')==$schedule->id)>
{{ $schedule->work_date->format('d-m-Y') }} · {{ $schedule->shift->name }} · {{ $schedule->attendance?->attendance_status ?? 'belum diproses' }}
</option>
@endforeach
</select></div>
<div class="field"><label>Alasan</label><textarea name="reason" rows="4" required>{{ old('reason') }}</textarea></div>
<p class="muted">Setelah disetujui atasan, gunakan tombol Keluar dan Kembali agar durasi dicatat dari server.</p>
<button>Kirim Pengajuan</button> <a class="btn secondary" href="{{ route('my.temporary-permissions.index') }}">Batal</a>
</form>
</div>
@endsection
