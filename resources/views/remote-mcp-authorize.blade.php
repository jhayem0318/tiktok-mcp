<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Authorize GoCommerce TikTok MCP</title></head>
<body>
<main>
    <h1>Authorize read-only TikTok Shop access</h1>
    <p><strong>{{ $client->name ?: 'This application' }}</strong> requests access to sanitized GoCommerce TikTok Shop commercial data.</p>
    <p>It cannot publish, fulfil, cancel, refund, edit, or delete TikTok Shop resources.</p>
    <form method="post" action="{{ route('remote-mcp.authorize') }}">
        @csrf
        <button name="decision" value="approve" type="submit">Authorize</button>
        <button name="decision" value="deny" type="submit">Cancel</button>
    </form>
</main>
</body>
</html>
