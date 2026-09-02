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
            pre { max-height: 620px; margin: 0; overflow: auto; padding: 20px; background: #090d14; color: #d8e1ef; font: 12px/1.6 ui-monospace, SFMono-Regular, Menlo, monospace; white-space: pre-wrap; overflow-wrap: anywhere; }
            .notice { margin-top: 16px; border-left: 3px solid var(--pink); padding: 12px 14px; background: rgba(254,44,85,.08); color: #ffc3cf; }
            footer { margin-top: 24px; color: #758095; font-size: 12px; }
            @media (max-width: 760px) { .hero, .grid, form { grid-template-columns: 1fr; } }
        </style>
    </head>
    <body>
        <main>
            <section class="hero">
                <div>
                    <div class="eyebrow">TikTok Shop app review</div>
                    <h1>GoCommerce TikTok Analytics</h1>
                    <p>A read-only commercial intelligence service for seller-owned TikTok Shop data. Customer identity, addresses, contact details, order identifiers, shop ciphers, and access tokens are removed before results are displayed.</p>
                </div>
                <div class="status">
                    <span class="dot"></span>API connection
                    <strong>{{ $shop?->name ?? 'No development shop authorized' }}</strong>
                    <p>{{ $shop?->region ?? 'PH' }} · Total Shop data · Asia/Manila</p>
                </div>
            </section>

            <section class="panel">
                <div class="eyebrow">Read-only capabilities</div>
                <h2>Choose a dataset to test</h2>
                <p>Every action below retrieves data only. This application cannot create, update, publish, fulfill, cancel, refund, or delete TikTok Shop resources.</p>
                <div class="grid">
                    @foreach ($datasets as $dataset)
                        <a class="dataset" href="{{ route('tiktok.review', ['dataset' => $dataset]) }}">
                            <span>GET · TikTok Shop Open API</span>
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
                    <div class="eyebrow">Test query</div>
                    <h2>{{ ucfirst($selectedDataset) }}</h2>
                    <form method="GET" action="{{ route('tiktok.review') }}">
                        <input type="hidden" name="dataset" value="{{ $selectedDataset }}">
                        <label>Start date<input type="date" name="start_date" value="{{ request('start_date') }}"></label>
                        <label>End date (exclusive)<input type="date" name="end_date" value="{{ request('end_date') }}"></label>
                        <label>Record limit<input type="number" name="limit" min="1" max="100" value="{{ request('limit', 20) }}"></label>
                        <button type="submit">Run read-only test</button>
                    </form>

                    @if ($error)
                        <div class="notice">{{ $error }}</div>
                    @elseif ($result)
                        <div class="result">
                            <header><strong>Sanitized API response</strong><span>Source: TikTok Shop Open API</span></header>
                            <pre>{{ json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                        </div>
                    @endif
                </section>
            @endif

            <footer>GoCommerce Inc. · Philippines local sellers · App ID 7679643544933009159 · Review environment</footer>
        </main>
    </body>
</html>
