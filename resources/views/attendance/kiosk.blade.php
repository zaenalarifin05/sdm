<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Presensi Pegawai · SDM Pabrik</title>
    <style>
        body{font-family:system-ui,sans-serif;background:#eef2ff;margin:0;display:grid;place-items:center;min-height:100vh;color:#111827}
        .card{background:#fff;width:min(460px,92vw);padding:30px;border-radius:18px;box-shadow:0 12px 40px #0002}
        h1{margin-top:0}.clock{font-size:1.1rem;color:#475569}.field{margin-top:16px}label{display:block;font-weight:700;margin-bottom:6px}
        input{box-sizing:border-box;width:100%;font-size:1.15rem;padding:12px;border:1px solid #cbd5e1;border-radius:9px}
        button{width:100%;margin-top:20px;padding:13px;border:0;border-radius:9px;background:#1e3a8a;color:#fff;font-size:1rem;font-weight:800;cursor:pointer}
        .success{background:#dcfce7;padding:14px;border-radius:10px;margin:16px 0}.error{background:#fee2e2;color:#991b1b;padding:12px;border-radius:10px;margin:16px 0}
        .muted{color:#64748b;font-size:.92rem}
    </style>
</head>
<body>
<div class="card">
    <h1>Presensi Pegawai</h1>
    <div class="clock">{{ now()->timezone(config('app.timezone'))->translatedFormat('l, d F Y · H:i') }} WIB</div>

    @if(session('attendance_result'))
        @php($result = session('attendance_result'))
        <div class="success">
            <strong>{{ $result['action'] === 'check_in' ? 'CHECK-IN BERHASIL' : 'CHECK-OUT BERHASIL' }}</strong><br>
            {{ $result['employee'] }} · {{ $result['shift'] }} · {{ $result['time'] }} WIB
        </div>
    @endif

    @error('nip')<div class="error">{{ $message }}</div>@enderror
    @error('pin')<div class="error">{{ $message }}</div>@enderror

    <form method="POST" action="{{ route('attendance.store') }}" autocomplete="off">
        @csrf
        <div class="field"><label for="nip">NIP</label><input id="nip" name="nip" value="{{ old('nip') }}" required autofocus maxlength="32"></div>
        <div class="field"><label for="pin">PIN 6 Digit</label><input id="pin" name="pin" type="password" inputmode="numeric" pattern="\d{6}" maxlength="6" required></div>
        <button type="submit">PROSES PRESENSI</button>
    </form>
    <p class="muted">Sistem otomatis menentukan Check-In atau Check-Out berdasarkan status presensi Anda.</p>
</div>
</body>
</html>
