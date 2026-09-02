<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">
        <title>GoCommerce TikTok Analytics - Review</title>
        <style>
            :root { color-scheme: dark; --ink: #f7f8fb; --muted: #9aa4b5; --line: #293245; --panel: #151b27; --cyan: #25f4ee; --pink: #fe2c55; }
            * { box-sizing: border-box; }
            body { margin: 0; min-height: 100vh; background: radial-gradient(circle at 15% 0%, #172c38 0, #0b0e14 38%, #080a0f 100%); color: var(--ink); font: 15px/1.55 Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
            main { width: min(1180px, calc(100% - 36px)); margin: 0 auto; padding: 42px 0 72px; }
            .eyebrow { color: var(--cyan); font-size: 12px; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; }
            h1 { max-width: 760px; margin: 8px 0 12px; font-size: clamp(34px, 6vw, 64px); line-height: 1.02; letter-spacing: -.045em; }
            h2 { margin: 0 0 8px; font-size: 20px; }
            p { margin: 0; color: var(--muted); }
            .hero { display: grid; grid-template-columns: 1.5fr .8fr; gap: 24px; align-items: end; margin-bottom: 26px; }
            .topbar { display: flex; justify-content: flex-end; margin-bottom: 20px; }
            .logout { border: 1px solid var(--line); background: transparent; color: var(--muted); font-weight: 700; padding: 8px 12px; }
            .status, .panel, .dataset, .result { border: 1px solid var(--line); border-radius: 18px; background: color-mix(in srgb, var(--panel) 93%, transparent); box-shadow: 0 18px 45px rgba(0,0,0,.18); }
            .status { padding: 20px; }
            .status strong { display: block; margin-top: 4px; font-size: 18px; }
            .dot { display: inline-block; width: 9px; height: 9px; margin-right: 8px; border-radius: 50%; background: #54e38e; box-shadow: 0 0 16px #54e38e; }
            .panel { padding: 24px; margin-top: 18px; }
            .grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-top: 18px; }
            .dataset { display: block; min-height: 132px; padding: 18px; color: inherit; text-decoration: none; transition: transform .15s ease, border-color .15s ease; }
            .dataset:hover { transform: translateY(-2px); border-color: var(--cyan); }
            .dataset span { display: block; margin-bottom: 18px; color: var(--pink); font-size: 11px; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
            .dataset strong { display: block; font-size: 18px; text-transform: capitalize; }
            .dataset small { color: var(--muted); }
            form { display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 12px; align-items: end; margin-top: 18px; }
            label { display: grid; gap: 7px; color: var(--muted); font-size: 12px; font-weight: 700; }
            input { width: 100%; border: 1px solid var(--line); border-radius: 10px; background: #0c111a; color: var(--ink); padding: 11px 12px; }
            button { border: 0; border-radius: 10px; background: linear-gradient(120deg, var(--cyan), #55c7ff); color: #061015; cursor: pointer; font-weight: 900; padding: 12px 18px; }
            .result { margin-top: 18px; overflow: hidden; }
            .result header { display: flex; justify-content: space-between; gap: 16px; padding: 18px 20px; border-bottom: 1px solid var(--line); }
            .syncbar { display: flex; justify-content: space-between; gap: 18px; align-items: center; margin-top: 18px; padding: 18px 20px; border: 1px solid var(--line); border-radius: 14px; background: #0c111a; }
            .syncbar strong { display: block; font-size: 17px; }
            .syncbar p { font-size: 13px; }
            .table-wrap { overflow-x: auto; }
            table { width: 100%; border-collapse: collapse; }
            th, td { padding: 14px 16px; border-bottom: 1px solid var(--line); text-align: left; vertical-align: top; }
            th { color: var(--muted); font-size: 11px; letter-spacing: .08em; text-transform: uppercase; }
            td code { color: var(--cyan); font-size: 12px; }
            .empty { padding: 28px 20px; text-align: center; }
            .empty strong { display: block; margin-bottom: 5px; }
            .pill { display: inline-block; padding: 3px 8px; border-radius: 999px; background: rgba(84,227,142,.12); color: #76efa4; font-size: 11px; font-weight: 800; }
            pre { max-height: 620px; margin: 0; overflow: auto; padding: 20px; background: #090d14; color: #d8e1ef; font: 12px/1.6 ui-monospace, SFMono-Regular, Menlo, monospace; white-space: pre-wrap; overflow-wrap: anywhere; }
            .notice { margin-top: 16px; border-left: 3px solid var(--pink); padding: 12px 14px; background: rgba(254,44,85,.08); color: #ffc3cf; }
            footer { margin-top: 24px; color: #758095; font-size: 12px; }
            @media (max-width: 760px) { .hero, .grid, form { grid-template-columns: 1fr; } }
        </style>
    </head>
    <body>
        <main>
            <div class="topbar">
                <form method="POST" action="{{ route('tiktok.review.logout') }}">
                    @csrf
                    <button class="logout" type="submit">Sign out</button>
                </form>
            </div>
            <section class="hero">
                <div>
                    <div class="eyebrow">TikTok Shop app review</div>
                    <h1>GoCommerce TikTok Analytics</h1>
                    <p>A read-only commercial intelligence service that synchronizes seller-owned TikTok Shop data. Customer identity, addresses, contact details, shop ciphers, and access tokens are removed before results are displayed. Sandbox product and order IDs remain visible for TikTok verification.</p>
                </div>
                <div class="status">
                    <span class="dot"></span>API connection
                    <strong>{{ $shop?->name ?? 'No development shop authorized' }}</strong>
                    <p>{{ $shop?->region ?? 'PH' }} · Total Shop data · Asia/Manila</p>
                </div>
            </section>

            <section class="panel">
                <div class="eyebrow">Read-only capabilities</div>
                <h2>Synchronize data from TikTok Shop</h2>
                <p>Choose a dataset and retrieve the latest records directly from TikTok Shop Open API. This application cannot create, update, publish, fulfill, cancel, refund, or delete TikTok Shop resources.</p>
                <div class="grid">
                    @foreach ($datasets as $dataset)
                        <a class="dataset" href="{{ route('tiktok.review', ['dataset' => $dataset]) }}">
                            <span>Read-only sync · TikTok Shop Open API</span>
                            <strong>{{ $dataset }}</strong>
                            <small>{{ match ($dataset) {
                                'analytics' => 'GMV and product performance',
                                'orders' => 'Redacted order aggregates',
                                'products' => 'Products and SKU catalogue',
                                'finance' => 'Fees, commissions and settlements',
                                'returns' => 'Redacted returns and refunds',
                                'promotions' => 'Current promotion activities',
                            } }}</small>
                        </a>
                    @endforeach
                </div>
            </section>

            @if ($selectedDataset)
                <section class="panel">
                    <div class="eyebrow">TikTok Shop synchronization</div>
                    <h2>{{ ucfirst($selectedDataset) }} sync</h2>
                    <form method="GET" action="{{ route('tiktok.review') }}">
                        <input type="hidden" name="dataset" value="{{ $selectedDataset }}">
                        <label>Start date<input type="date" name="start_date" value="{{ request('start_date') }}"></label>
                        <label>End date (exclusive)<input type="date" name="end_date" value="{{ request('end_date') }}"></label>
                        <label>Record limit<input type="number" name="limit" min="1" max="100" value="{{ request('limit', 20) }}"></label>
                        <button type="submit">Sync from TikTok Shop</button>
                    </form>

                    @if ($error)
                        <div class="notice">{{ $error }}</div>
                    @elseif ($result)
                        <div class="syncbar">
                            <div>
                                <strong><span class="dot"></span>Synchronization completed</strong>
                                <p>Source: TikTok Shop Open API · {{ $syncedAt?->format('M j, Y g:i:s A') }} Asia/Manila</p>
                            </div>
                            <span class="pill">READ ONLY</span>
                        </div>

                        @if (in_array($selectedDataset, ['products', 'orders'], true))
                            <div class="result">
                                <header>
                                    <strong>Synchronized {{ $selectedDataset }}</strong>
                                    <span>{{ count($records) }} record(s)</span>
                                </header>
                                @if (count($records) === 0)
                                    <div class="empty">
                                        <strong>No {{ $selectedDataset }} found in this sandbox shop</strong>
                                        <p>Add sandbox {{ $selectedDataset === 'products' ? 'products' : 'orders' }} in TikTok Shop, then run the synchronization again.</p>
                                    </div>
                                @else
                                    <div class="table-wrap">
                                        <table>
                                            <thead>
                                                <tr>
                                                    <th>TikTok Shop {{ $selectedDataset === 'products' ? 'Product' : 'Order' }} ID</th>
                                                    <th>{{ $selectedDataset === 'products' ? 'Product' : 'Order' }}</th>
                                                    <th>Status</th>
                                                    <th>Synced from</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($records as $record)
                                                    <tr>
                                                        <td><code>{{ $record['id'] ?? $record[$selectedDataset === 'products' ? 'product_id' : 'order_id'] ?? 'Unavailable' }}</code></td>
                                                        <td>{{ $record['title'] ?? $record['product_name'] ?? ($selectedDataset === 'orders' ? count($record['line_items'] ?? []) . ' line item(s)' : 'TikTok Shop product') }}</td>
                                                        <td><span class="pill">{{ $record['status'] ?? 'SYNCED' }}</span></td>
                                                        <td>TikTok Shop Open API</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                        @endif

                        <div class="result">
                            <header><strong>Sanitized synchronization response</strong><span>Source: TikTok Shop Open API</span></header>
                            <pre>{{ json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                        </div>
                    @endif
                </section>
            @endif

            <footer>GoCommerce Inc. · Philippines local sellers · App ID 7679643544933009159 · Review environment</footer>
        </main>
    </body>
</html>
