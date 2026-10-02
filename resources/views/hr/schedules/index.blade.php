@extends('layouts.app')
@section('title','Jadwal Shift')
@section('content')
<div class="row"><h1 style="flex:1">Jadwal Shift</h1><a class="btn" href="{{ route('hr.schedules.create') }}">Assign Shift</a></div>
<div class="card"><form method="GET" class="row"><div class="field"><label>Filter tanggal</label><input type="date" name="work_date" value="{{ request('work_date') }}"></div><button>Filter</button><a class="btn secondary" href="{{ route('hr.schedules.index') }}">Reset</a></form></div>
<div class="card"><table><thead><tr><th>Tanggal</th><th>NIP</th><th>Pegawai</th><th>Departemen</th><th>Shift</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
@forelse($schedules as $schedule)<tr><td>{{ $schedule->work_date->format('d-m-Y') }}</td><td>{{ $schedule->employee->nip }}</td><td>{{ $schedule->employee->name }}</td><td>{{ $schedule->employee->department->name }}</td><td>{{ $schedule->shift->name }}</td><td>{{ $schedule->status }}</td><td class="actions"><a class="btn secondary" href="{{ route('hr.schedules.edit',$schedule) }}">Edit</a>@if($schedule->status!=='cancelled')<form method="POST" action="{{ route('hr.schedules.destroy',$schedule) }}" onsubmit="return confirm('Batalkan jadwal ini?')">@csrf @method('DELETE')<button class="btn danger">Batalkan</button></form>@endif</td></tr>
@empty<tr><td colspan="7">Belum ada jadwal.</td></tr>@endforelse</tbody></table><div style="margin-top:14px">{{ $schedules->links() }}</div></div>@endsection
