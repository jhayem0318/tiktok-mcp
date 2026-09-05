<!DOCTYPE html>
<html lang="en">
    <head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Client access administration</title>
        <style>:root{color-scheme:dark}*{box-sizing:border-box}body{margin:0;background:#091019;color:#e9f1fc;font:14px/1.5 system-ui}main{max-width:1180px;margin:auto;padding:32px}section{margin:22px 0;padding:24px;border:1px solid #29384f;border-radius:16px;background:#111a27}h1,h2{margin-top:0}form{display:grid;gap:14px}.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.shops{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:10px}label{display:grid;gap:6px;font-weight:700}input{width:100%;padding:11px;border:1px solid #34435b;border-radius:8px;background:#0b1220;color:#fff}button{padding:10px 14px;border:0;border-radius:8px;background:#28d7d0;color:#061015;font-weight:800}.danger{background:#f35b79;color:#fff}.notice{padding:14px;border-radius:10px;background:#0c2931;border:1px solid #28d7d0}table{width:100%;border-collapse:collapse}th,td{padding:12px;text-align:left;border-top:1px solid #29384f;vertical-align:top}.muted{color:#9badc4}.top{display:flex;justify-content:space-between;gap:18px;align-items:center}@media(max-width:700px){.grid{grid-template-columns:1fr}table{display:block;overflow:auto}}</style>
    </head>
    <body><main>
        <div class="top"><div><p style="color:#28d7d0;font-weight:900;letter-spacing:.12em;text-transform:uppercase">GoCommerce</p><h1>Client access</h1></div><form method="POST" action="{{ route('client-access.admin.logout') }}">@csrf<button>Sign out</button></form></div>
        @if (is_array($credentials))
            <section class="notice"><strong>Copy these credentials now.</strong> They are shown only once.
                <p>Email: <code>{{ $credentials['email'] }}</code><br>Temporary password: <code>{{ $credentials['temporary_password'] }}</code><br>OAuth invitation code: <code>{{ $credentials['oauth_invitation_code'] }}</code></p>
            </section>
        @endif
        @if ($errors->any())<section style="border-color:#f35b79;color:#ffc4d0">{{ $errors->first() }}</section>@endif
        <section>
            <h2>Create client access</h2>
            <p class="muted">The client must change the generated password on first login. Client and OAuth invitation expiries are independent.</p>
            <form method="POST" action="{{ route('client-access.admin.store') }}">@csrf
                <div class="grid"><label>Client name<input name="name" value="{{ old('name') }}" required></label><label>Client email<input name="email" type="email" value="{{ old('email') }}" required></label><label>Dashboard access expires (optional)<input name="access_expires_at" type="datetime-local" value="{{ old('access_expires_at') }}"></label><label>OAuth invitation expires (optional)<input name="invite_expires_at" type="datetime-local" value="{{ old('invite_expires_at') }}"></label></div>
                <div><strong>Assigned Shops</strong><div class="shops">@forelse ($shops as $shop)<label style="font-weight:500"><span><input type="checkbox" name="shop_ids[]" value="{{ $shop->id }}" @checked(in_array($shop->id, old('shop_ids', [])))> {{ $shop->name }} · {{ $shop->region }}</span></label>@empty<p class="muted">No production Shop is currently authorized.</p>@endforelse</div></div>
                <div><button>Create client, password, and OAuth invitation</button></div>
            </form>
        </section>
        <section><h2>Clients</h2><table><thead><tr><th>Client</th><th>Dashboard access</th><th>OAuth invitations / Shop access</th><th>Action</th></tr></thead><tbody>
            @forelse ($clients as $client)<tr><td><strong>{{ $client->name }}</strong><br><span class="muted">{{ $client->email }}</span></td><td>{{ $client->access_expires_at?->format('Y-m-d H:i') ?? 'No expiry' }}<br><span class="muted">{{ $client->must_change_password ? 'Password change pending' : 'Password set' }}</span></td><td>@forelse ($client->remoteMcpInvites as $invite)<div><strong>{{ $invite->revoked_at ? 'Revoked' : 'Active' }}</strong> · expires {{ $invite->expires_at?->format('Y-m-d H:i') ?? 'never' }}<br><span class="muted">{{ $invite->shops->pluck('name')->join(', ') ?: 'No Shop assigned' }}</span></div>@empty<span class="muted">No invitation</span>@endforelse</td><td>@foreach ($client->remoteMcpInvites->whereNull('revoked_at') as $invite)<form method="POST" action="{{ route('client-access.admin.invites.revoke', $invite) }}">@csrf<button class="danger">Revoke OAuth</button></form>@endforeach</td></tr>@empty<tr><td colspan="4" class="muted">No client accounts yet.</td></tr>@endforelse
        </tbody></table></section>
    </main></body>
</html>
