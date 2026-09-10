<!DOCTYPE html>
<html lang="en">
    <head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Client sign in</title>
        <style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:#091019;color:#eff6ff;font:15px system-ui}main{width:min(420px,calc(100% - 32px));padding:32px;border:1px solid #273449;border-radius:18px;background:#111a27}label,form{display:grid;gap:8px}form{gap:16px}input{padding:12px;border:1px solid #34435b;border-radius:9px;background:#0b1220;color:#fff}button{padding:12px;border:0;border-radius:9px;background:#28d7d0;font-weight:800}.error{color:#ffb4c5}</style>
    </head>
    <body><main>
        <img src="{{ asset('images/gocommerce-wordmark.png') }}" alt="GoCommerce" style="height:32px;width:auto;margin-bottom:4px">
        <h1>Client sign in</h1>
        <p>Use the username and temporary password supplied by your administrator.</p>
        @if ($errors->any())<p class="error">{{ $errors->first() }}</p>@endif
        <form method="POST" action="{{ route('client.login.store') }}">@csrf
            <label>Username<input name="username" value="{{ old('username') }}" required autocomplete="username"></label>
            <label>Password<input type="password" name="password" required autocomplete="current-password"></label>
            <button>Sign in</button>
        </form>
    </main></body>
</html>
