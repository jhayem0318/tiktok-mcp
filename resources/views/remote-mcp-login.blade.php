<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>GoCommerce TikTok MCP</title></head>
<body>
<main>
    <h1>Connect GoCommerce TikTok MCP</h1>
    <p>Enter the invitation code supplied by GoCommerce. This connection grants read-only, sanitized TikTok Shop data.</p>
    <form method="post" action="{{ route('remote-mcp.login.store') }}">
        @csrf
        <label>Invitation code <input name="invite_code" type="password" autocomplete="one-time-code" required></label>
        @error('invite_code')<p>{{ $message }}</p>@enderror
        <button type="submit">Continue</button>
    </form>
</main>
</body>
</html>
