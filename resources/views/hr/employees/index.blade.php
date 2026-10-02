@extends('layouts.app')
@section('title','Pegawai')
@section('content')
<div class="row"><h1 style="flex:1">Pegawai</h1><a class="btn" href="{{ route('hr.employees.create') }}">Tambah Pegawai</a></div>
<div class="card"><table><thead><tr><th>NIP</th><th>Nama</th><th>Departemen</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
@forelse($employees as $employee)<tr><td>{{ $employee->nip }}</td><td>{{ $employee->name }}</td><td>{{ $employee->department->name }}</td><td>{{ $employee->employment_status }}</td><td class="actions"><a class="btn secondary" href="{{ route('hr.employees.edit',$employee) }}">Edit</a>@if($employee->is_active)<form method="POST" action="{{ route('hr.employees.destroy',$employee) }}" onsubmit="return confirm('Nonaktifkan pegawai ini?')">@csrf @method('DELETE')<button class="btn danger">Nonaktifkan</button></form>@endif</td></tr>
@empty<tr><td colspan="5">Belum ada pegawai.</td></tr>@endforelse</tbody></table><div style="margin-top:14px">{{ $employees->links() }}</div></div>@endsection
