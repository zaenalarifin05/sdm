@extends('layouts.app')
@section('title','Ajukan Lembur')
@section('content')
<h1>Ajukan Lembur</h1>
<div class="card"><form method="POST" action="{{ route('my.overtime.store') }}">@csrf
<div class="field"><label>Jadwal Kerja</label><select name="shift_schedule_id" required>
@foreach($schedules as $schedule)
<option value="{{ $schedule->id }}" @selected(old('shift_schedule_id')==$schedule->id)>
{{ $schedule->work_date->format('d-m-Y') }} · {{ $schedule->shift->name }} · Attendance: {{ $schedule->attendance?->attendance_status ?? 'belum diproses' }}
</option>
@endforeach
</select></div>
<div class="row">
<div class="field"><label>Mulai Lembur</label><input type="datetime-local" name="start_at" value="{{ old('start_at') }}" required></div>
<div class="field"><label>Selesai Lembur</label><input type="datetime-local" name="end_at" value="{{ old('end_at') }}" required></div>
</div>
<div class="field"><label>Alasan</label><textarea name="reason" rows="4" required>{{ old('reason') }}</textarea></div>
<p class="muted">Durasi dihitung otomatis oleh sistem. Pengajuan dikaitkan ke ShiftSchedule sebagai referensi hari kerja/presensi.</p>
<button>Kirim Pengajuan</button> <a class="btn secondary" href="{{ route('my.overtime.index') }}">Batal</a>
</form></div>
@endsection
