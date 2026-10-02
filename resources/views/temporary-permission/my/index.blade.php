@extends('layouts.app')
@section('title','Izin Keluar Saya')
@section('content')
<div class="row"><h1 style="flex:1">Izin Keluar Sementara</h1><a class="btn" href="{{ route('my.temporary-permissions.create') }}">Ajukan Izin Keluar</a></div>
<div class="card">
<p class="muted">Akumulasi izin keluar maksimal {{ config('temporary_permission.max_allowed_minutes') }} menit per hari kerja. Lebih dari batas tersebut dianggap ABSENT.</p>
<table>
<thead><tr><th>Tanggal/Shift</th><th>Alasan</th><th>Status</th><th>Keluar</th><th>Kembali</th><th>Durasi</th><th>Aksi</th></tr></thead>
<tbody>
@forelse($permissions as $permission)
<tr>
<td>{{ $permission->shiftSchedule->work_date->format('d-m-Y') }}<br>{{ $permission->shiftSchedule->shift->name }}</td>
<td>{{ $permission->reason }}</td>
<td>{{ $permission->status }}</td>
<td>{{ $permission->out_at?->timezone(config('app.timezone'))->format('H:i:s') ?? '-' }}</td>
<td>{{ $permission->returned_at?->timezone(config('app.timezone'))->format('H:i:s') ?? '-' }}</td>
<td>{{ $permission->duration_minutes !== null ? $permission->duration_minutes.' menit' : '-' }}</td>
<td>
@if($permission->status==='APPROVED' && !$permission->out_at)
<form method="POST" action="{{ route('my.temporary-permissions.start',$permission) }}">@csrf<button>Keluar Sekarang</button></form>
@elseif($permission->status==='APPROVED' && $permission->out_at && !$permission->returned_at)
<form method="POST" action="{{ route('my.temporary-permissions.return',$permission) }}">@csrf<button>Kembali Sekarang</button></form>
@else -
@endif
</td>
</tr>
@empty<tr><td colspan="7">Belum ada izin keluar sementara.</td></tr>@endforelse
</tbody></table>
<div style="margin-top:14px">{{ $permissions->links() }}</div>
</div>
@endsection
