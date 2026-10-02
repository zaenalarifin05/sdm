@extends('layouts.app')
@section('title','Departemen')
@section('content')
<div class="row"><h1 style="flex:1">Departemen</h1><a class="btn" href="{{ route('hr.departments.create') }}">Tambah Departemen</a></div>
<div class="card"><table><thead><tr><th>Kode</th><th>Nama</th><th>Manager</th><th>Pegawai</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
@forelse($departments as $department)<tr><td>{{ $department->code }}</td><td>{{ $department->name }}</td><td>{{ $department->manager?->name ?? '-' }}</td><td>{{ $department->employees_count }}</td><td>{{ $department->is_active?'Aktif':'Nonaktif' }}</td><td class="actions"><a class="btn secondary" href="{{ route('hr.departments.edit',$department) }}">Edit</a><form method="POST" action="{{ route('hr.departments.destroy',$department) }}" onsubmit="return confirm('Hapus departemen ini?')">@csrf @method('DELETE')<button class="btn danger">Hapus</button></form></td></tr>
@empty<tr><td colspan="6">Belum ada departemen.</td></tr>@endforelse</tbody></table></div>
@endsection
