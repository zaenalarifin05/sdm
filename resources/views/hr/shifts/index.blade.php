@extends('layouts.app')
@section('title','Shift')
@section('content')
<div class="row"><h1 style="flex:1">Shift</h1><a class="btn" href="{{ route('hr.shifts.create') }}">Tambah Shift</a></div>
<div class="card"><table><thead><tr><th>Kode</th><th>Nama</th><th>Jam</th><th>Lintas Hari</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
@foreach($shifts as $shift)<tr><td>{{ $shift->code }}</td><td>{{ $shift->name }}</td><td>{{ $shift->start_time }}–{{ $shift->end_time }}</td><td>{{ $shift->crosses_midnight?'Ya':'Tidak' }}</td><td>{{ $shift->is_active?'Aktif':'Nonaktif' }}</td><td class="actions"><a class="btn secondary" href="{{ route('hr.shifts.edit',$shift) }}">Edit</a>@if($shift->is_active)<form method="POST" action="{{ route('hr.shifts.destroy',$shift) }}" onsubmit="return confirm('Nonaktifkan shift ini?')">@csrf @method('DELETE')<button class="btn danger">Nonaktifkan</button></form>@endif</td></tr>@endforeach
</tbody></table></div>@endsection
