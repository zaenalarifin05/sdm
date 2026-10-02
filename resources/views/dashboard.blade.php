@extends('layouts.app')
@section('title','Dashboard')
@section('content')
<div class="card"><h1>Dashboard</h1><p>Selamat datang, <strong>{{ auth()->user()->name }}</strong>.</p><p class="muted">Role: {{ auth()->user()->role->value }}</p>
@if(auth()->user()->hasAnyRole('hr_admin','system_admin'))<p>Gunakan menu di atas untuk mengelola master data dan jadwal shift.</p>@else<p>Dashboard khusus role ini akan dilengkapi pada vertical slice berikutnya.</p>@endif
</div>@endsection
