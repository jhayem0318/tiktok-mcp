<!DOCTYPE html>
<html lang="en">
    <head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Set your password</title>
        <style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:#091019;color:#eff6ff;font:15px system-ui}main{width:min(420px,calc(100% - 32px));padding:32px;border:1px solid #273449;border-radius:18px;background:#111a27}label,form{display:grid;gap:8px}form{gap:16px}input{padding:12px;border:1px solid #34435b;border-radius:9px;background:#0b1220;color:#fff}button{padding:12px;border:0;border-radius:9px;background:#28d7d0;font-weight:800}.error{color:#ffb4c5}</style>
    </head>
    <body><main>
        <p style="color:#28d7d0;font-weight:800;letter-spacing:.12em;text-transform:uppercase">Security</p>
        <h1>{{ $mustChangePassword ? 'Set your password' : 'Change your password' }}</h1>
        <p>Choose a new password with at least 12 characters.</p>
        @if ($errors->any())<p class="error">{{ $errors->first() }}</p>@endif
        <form method="POST" action="{{ route('client.password.update') }}">@csrf
            @unless($mustChangePassword)<label>Current password<input name="current_password" type="password" required autocomplete="current-password"></label>@endunless
            <label>New password<input name="password" type="password" required autocomplete="new-password"></label>
            <label>Confirm password<input name="password_confirmation" type="password" required autocomplete="new-password"></label>
            <button>Save password</button>
        </form>
    </main></body>
</html>
