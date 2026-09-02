<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">
        <title>Reviewer sign in - GoCommerce TikTok Analytics</title>
        <style>
            :root { color-scheme: dark; --ink: #f7f8fb; --muted: #9aa4b5; --line: #293245; --panel: #151b27; --cyan: #25f4ee; --pink: #fe2c55; }
            * { box-sizing: border-box; }
            body { display: grid; min-height: 100vh; margin: 0; place-items: center; background: radial-gradient(circle at 20% 0%, #17303b 0, #0b0e14 42%, #080a0f 100%); color: var(--ink); font: 15px/1.5 Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
            main { width: min(440px, calc(100% - 32px)); border: 1px solid var(--line); border-radius: 22px; background: rgba(21, 27, 39, .96); box-shadow: 0 24px 70px rgba(0,0,0,.35); padding: 32px; }
            .eyebrow { color: var(--cyan); font-size: 11px; font-weight: 900; letter-spacing: .14em; text-transform: uppercase; }
            h1 { margin: 8px 0 10px; font-size: 30px; line-height: 1.1; letter-spacing: -.035em; }
            p { margin: 0 0 24px; color: var(--muted); }
            form, label { display: grid; gap: 9px; }
            form { gap: 17px; }
            label { color: #c9d1df; font-size: 12px; font-weight: 800; }
            input { width: 100%; border: 1px solid var(--line); border-radius: 11px; outline: none; background: #0b1018; color: var(--ink); padding: 13px 14px; }
            input:focus { border-color: var(--cyan); box-shadow: 0 0 0 3px rgba(37,244,238,.1); }
            button { border: 0; border-radius: 11px; background: linear-gradient(120deg, var(--cyan), #55c7ff); color: #061015; cursor: pointer; font-weight: 900; padding: 13px 18px; }
            .error { border-left: 3px solid var(--pink); margin-bottom: 18px; background: rgba(254,44,85,.08); color: #ffc3cf; padding: 10px 12px; }
            footer { margin-top: 20px; color: #748095; font-size: 11px; }
        </style>
    </head>
    <body>
        <main>
            <div class="eyebrow">TikTok Shop app review</div>
            <h1>Reviewer sign in</h1>
            <p>Use the temporary review credentials supplied in TikTok Shop Partner Center.</p>

            @if ($errors->any())
                <div class="error">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('tiktok.review.login.store') }}">
                @csrf
                <label>Username<input name="username" value="{{ old('username') }}" required autocomplete="username"></label>
                <label>Password<input type="password" name="password" required autocomplete="current-password"></label>
                <button type="submit">Sign in to review dashboard</button>
            </form>
            <footer>GoCommerce Inc. · App ID 7679643544933009159</footer>
        </main>
    </body>
</html>
