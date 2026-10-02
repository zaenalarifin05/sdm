<!doctype html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Login · SDM Pabrik</title>
<style>body{font-family:system-ui,sans-serif;background:#f3f4f6;margin:0;display:grid;place-items:center;min-height:100vh;color:#111827}.card{background:white;width:min(420px,90vw);padding:28px;border-radius:14px;box-shadow:0 8px 30px #0001}label{display:block;margin:14px 0 6px;font-weight:600}input{box-sizing:border-box;width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px}button{width:100%;margin-top:18px;padding:11px;border:0;border-radius:8px;background:#1e3a8a;color:white;font-weight:700;cursor:pointer}.error{color:#b91c1c;font-size:.9rem;margin-top:8px}</style></head>
<body><div class="card"><h1>SDM Pabrik</h1><p>Masuk untuk mengelola data sesuai hak akses Anda.</p>
<form method="POST" action="{{ route('login.store') }}">@csrf
<label for="email">Email</label><input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username">
<label for="password">Password</label><input id="password" name="password" type="password" required autocomplete="current-password">
<label style="font-weight:400"><input style="width:auto" type="checkbox" name="remember" value="1"> Ingat saya</label>
@error('email')<div class="error">{{ $message }}</div>@enderror
<button type="submit">Masuk</button></form></div></body></html>
