<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>{{ $client->name }} · TikTok Shop</title>
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Condensed:wght@600;700&family=IBM+Plex+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@400;500;600&display=swap">
<style>
:root{
  --ink:#10181A;--muted:#55716C;--ink-faint:#85A29C;--line:#D7E6E2;--paper:#FFFFFF;--page:#F1F6F4;--surface-2:#E8F1EE;
  --accent:#148F62;--accent-fill:#1BAF7A;--eufy:#2A78D6;--solix:#C6551F;--solix-fill:#EB6834;--soundcore:#A87200;--soundcore-fill:#EDA100;
  --bad:#C62E2D;--bad-fill:#E34948;--warn:#9C6A00;--shadow:0 1px 2px rgba(16,24,26,.04),0 8px 20px -12px rgba(16,24,26,.12);
}
@media(prefers-color-scheme:dark){:root{
  --ink:#EDF5F2;--muted:#93B0AA;--ink-faint:#5E7C77;--line:#2A3B38;--paper:#16201F;--page:#0E1516;--surface-2:#1C2928;
  --accent:#3FD69C;--accent-fill:#199E70;--eufy:#6FAAF0;--solix:#F2895A;--solix-fill:#D95926;--soundcore:#F0BB4E;--soundcore-fill:#C98500;
  --bad:#F0827E;--bad-fill:#E66767;--warn:#E0B84D;--shadow:0 1px 2px rgba(0,0,0,.3),0 10px 26px -14px rgba(0,0,0,.55);
}}
*{box-sizing:border-box}body{margin:0;background:var(--page);color:var(--ink);font:14px/1.5 'IBM Plex Sans',ui-sans-serif,system-ui,sans-serif}
.shell{max-width:1240px;margin:auto;padding:28px 24px 70px}
.nav{height:auto;padding-bottom:20px;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;gap:18px;margin-bottom:22px}
.brand{display:flex;align-items:center;gap:10px}
.brand img{height:26px;width:auto;display:block;border-radius:6px}
.sidebar-title img{height:22px;width:auto;display:block}
.nav-actions{display:flex;gap:8px;align-items:center}
.btn{display:inline-flex;align-items:center;justify-content:center;min-height:38px;padding:9px 14px;border-radius:8px;border:1px solid var(--line);background:var(--paper);color:var(--muted);font:600 12.5px 'IBM Plex Sans',sans-serif;text-decoration:none;cursor:pointer}
.btn:hover{border-color:var(--ink-faint);color:var(--ink)}
.btn.primary{border-color:var(--accent-fill);background:var(--accent-fill);color:#06231B;font-weight:700}
.eyebrow{font:600 11px 'IBM Plex Mono',monospace;letter-spacing:.09em;text-transform:uppercase;color:var(--ink-faint)}
h1,h2{font-family:'IBM Plex Sans Condensed',sans-serif;font-weight:700;letter-spacing:-.01em;text-wrap:balance}
h1{font-size:28px;margin:6px 0 2px}h2{font-size:20px;margin:3px 0}
.sub{color:var(--muted);margin:0;font-size:12.5px}
.intro{display:flex;justify-content:space-between;gap:20px;align-items:end;padding:8px 0 20px}
.tabs{display:flex;flex-wrap:wrap;gap:8px}
.tab{font:600 12.5px 'IBM Plex Sans',sans-serif;border:1px solid var(--line);border-radius:99px;padding:8px 14px;background:var(--paper);color:var(--muted);text-decoration:none;transition:transform .08s}
.tab:hover{border-color:var(--ink-faint);color:var(--ink);transform:translateY(-1px)}
.tab.active{background:var(--accent-fill);border-color:var(--accent-fill);color:#06231B;font-weight:700}
.filter{background:var(--paper);border:1px solid var(--line);border-radius:14px;padding:16px;display:grid;grid-template-columns:1.6fr 1fr 1fr auto;gap:10px;align-items:end;box-shadow:var(--shadow)}
label{display:grid;gap:5px;color:var(--ink-faint);font:600 10.5px 'IBM Plex Mono',monospace;letter-spacing:.06em;text-transform:uppercase}
input,select{width:100%;border:1px solid var(--line);border-radius:7px;padding:10px;background:var(--paper);color:var(--ink);font:500 12.5px 'IBM Plex Mono',monospace}
.section{margin-top:34px}
.section-head{display:flex;align-items:end;justify-content:space-between;gap:16px;margin:0 0 12px}
.section-head p{margin:3px 0 0;color:var(--muted);font-size:12.5px}
.kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}
.card{background:var(--paper);border:1px solid var(--line);border-radius:14px;padding:17px;box-shadow:var(--shadow)}
.metric-label{font:600 10.5px 'IBM Plex Mono',monospace;letter-spacing:.06em;text-transform:uppercase;color:var(--ink-faint)}
.metric{font-family:'IBM Plex Sans Condensed',sans-serif;font-size:26px;font-weight:700;letter-spacing:-.02em;margin:8px 0 3px;font-variant-numeric:tabular-nums}
.positive{color:var(--accent)}.negative{color:var(--bad)}
.metric-note{font-size:11.5px;color:var(--muted)}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.rows{padding:2px 17px}
.row{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:12px;padding:11px 0;border-bottom:1px solid var(--line);align-items:center}
.row:last-child{border:0}
.name{font-weight:600;font-size:13px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.detail{color:var(--ink-faint);font:10.5px 'IBM Plex Mono',monospace;margin-top:2px}
.amount{font:600 12.5px 'IBM Plex Mono',monospace;font-variant-numeric:tabular-nums}
.bar{height:4px;border-radius:3px;background:var(--surface-2);margin-top:7px;overflow:hidden}
.bar i{display:block;height:100%;border-radius:inherit;background:var(--accent-fill)}
.bar.cancel i{background:var(--bad-fill)}
.empty{color:var(--muted);padding:16px}
.status{background:var(--surface-2);border:1px solid var(--line);color:var(--accent);border-radius:99px;padding:9px 14px;font:600 11.5px 'IBM Plex Mono',monospace}
.status.warn{color:var(--warn)}
.unavailable{background:var(--surface-2);color:var(--muted);border:1px dashed var(--line);border-radius:12px;padding:18px;font-size:12.5px}
.sample{max-height:500px;overflow:auto}
.pill{font:700 10px 'IBM Plex Mono',monospace;letter-spacing:.04em;padding:4px 7px;border-radius:99px;background:var(--surface-2);color:var(--muted)}
.brand-filter{grid-column:1/-1;display:flex;flex-wrap:wrap;gap:8px;align-items:center}
.brand-pill{position:relative;font:600 12.5px 'IBM Plex Sans',sans-serif;border:1px solid var(--line);border-radius:99px;padding:8px 14px;background:var(--paper);color:var(--muted);cursor:pointer}
.brand-pill:hover{border-color:var(--ink-faint)}
.brand-pill input{position:absolute;opacity:0;pointer-events:none}
.brand-pill:has(input:checked){background:var(--accent-fill);border-color:var(--accent-fill);color:#06231B;font-weight:700}
.fulfil{margin-top:14px}
.fulfil-label{display:flex;justify-content:space-between;font:11px 'IBM Plex Mono',monospace;color:var(--muted);margin-bottom:6px}
.fulfil-bar{height:9px;border-radius:6px;overflow:hidden;display:flex;background:var(--surface-2)}
.fulfil-bar i{display:block;height:100%}
.fulfil-bar .ok{background:var(--accent-fill)}
.fulfil-bar .no{background:var(--bad-fill)}
.ads-status{display:inline-flex;align-items:center;gap:7px;padding:9px 12px;border-radius:99px;border:1px solid var(--line);background:var(--paper);font:600 11.5px 'IBM Plex Mono',monospace;color:var(--muted);text-decoration:none}
.ads-status:hover{border-color:var(--ink-faint)}
.dot{display:inline-block;width:8px;height:8px;border-radius:99px;flex:none}
.dot.disconnected{background:var(--bad-fill)}
.dot.expiring{background:var(--soundcore-fill)}
.dot.connected{background:var(--accent-fill)}
.swatch{display:inline-block;width:9px;height:9px;border-radius:99px;margin-right:8px;flex:none}
.swatch-0{background:var(--accent-fill)}.swatch-1{background:var(--soundcore-fill)}.swatch-2{background:var(--eufy)}.swatch-3{background:var(--solix-fill)}.swatch-4{background:var(--bad-fill)}
.chart-wrap{position:relative}
.chart-tooltip{position:absolute;pointer-events:none;background:var(--ink);color:var(--page);border-radius:8px;padding:9px 12px;font:11.5px/1.5 'IBM Plex Mono',monospace;box-shadow:var(--shadow);opacity:0;transform:translate(-50%,-100%);transition:opacity .08s;white-space:nowrap;z-index:5}
.chart-tooltip .tt-title{font:700 11px 'IBM Plex Sans Condensed',sans-serif;color:var(--page);margin-bottom:4px}
.chart-tooltip .tt-row{display:flex;justify-content:space-between;gap:14px}
.chart-tooltip .tt-row i{width:8px;height:8px;border-radius:2px;display:inline-block;margin-right:6px;vertical-align:middle}
.mom-bar,.donut-seg{cursor:pointer;transition:opacity .1s}
.mom-bar:hover,.donut-seg:hover{opacity:.75}
.trend-marker{transition:r .08s}
#trendHit rect{cursor:crosshair}
.donut-grid{display:flex;gap:20px;align-items:center}
.footer{border-top:1px solid var(--line);margin-top:44px;padding-top:16px;color:var(--ink-faint);font:10.5px 'IBM Plex Mono',monospace;line-height:1.8}
@media(max-width:840px){.shell{padding:20px 16px 50px}.nav{align-items:flex-start}.intro{display:block}.tabs{margin-top:16px}.filter,.kpis,.grid{grid-template-columns:1fr}.nav-actions{flex-wrap:wrap}.metric{font-size:22px}}
</style><style>
html{scroll-behavior:smooth}body{margin:0}.section-head{scroll-margin-top:24px}
.sidebar{position:fixed;inset:0 auto 0 0;width:216px;padding:22px 16px;background:var(--paper);border-right:1px solid var(--line);overflow:auto}
.sidebar nav{display:grid;gap:2px;margin-top:20px}
.sidebar a{padding:8px 10px;border-radius:8px;color:var(--muted);text-decoration:none;font:600 12.5px 'IBM Plex Sans',sans-serif}
.sidebar a:hover{background:var(--surface-2);color:var(--ink)}
.sidebar a[aria-current]{background:var(--surface-2);color:var(--accent)}
.shell{margin-left:216px}
.sidebar-title{display:flex;align-items:center}
@media(max-width:840px){.sidebar{position:static;width:auto;padding:14px}.sidebar nav{display:flex;overflow:auto;margin-top:10px;gap:6px}.sidebar a{white-space:nowrap}.shell{margin-left:0}}
</style></head><body><aside class="sidebar"><div class="sidebar-title"><img src="{{ asset('images/gocommerce-wordmark.png') }}" alt="GoCommerce" style="height:22px;width:auto"></div><nav aria-label="Dashboard sections" id="section-menu"></nav></aside><main class="shell">
@php($adsStatusLabel = ['disconnected' => 'Ads not connected', 'expiring' => 'Ads reconnecting', 'connected' => 'Ads connected'][$adsStatus])
<header class="nav"><div class="brand"><img src="{{ asset('images/gocommerce-wordmark.png') }}" alt="GoCommerce"> <small style="font-size:12px;font-weight:700;color:var(--muted)">TikTok Analytics</small></div><div class="nav-actions"><a class="ads-status" href="{{ route('tiktok-ads.connect') }}" title="{{ $adsAccounts->pluck('name')->filter()->implode(', ') ?: $adsAccounts->pluck('advertiser_id')->implode(', ') ?: 'Click to connect a TikTok Ads account' }}"><span class="dot {{ $adsStatus }}"></span>{{ $adsStatusLabel }}</a><a class="btn" href="{{ route('client.password.edit') }}">Change password</a><form method="POST" action="{{ route('client.logout') }}">@csrf<button class="btn">Sign out</button></form></div></header>
@if(session('ads_connection_success'))<div class="status" style="display:block;margin-bottom:16px">TikTok Ads account connected.</div>@endif
@if(session('ads_connection_error'))<div class="status warn" style="display:block;margin-bottom:16px">{{ session('ads_connection_error') }}</div>@endif
@if($errors->any())<div class="status warn" style="display:block;margin-bottom:16px">{{ $errors->first() }}</div>@endif
<div class="intro"><div><div class="eyebrow">TikTok Shop performance</div><h1>{{ $client->name }}</h1><p class="sub">Seller-owned Shop operations and commercial performance</p></div><nav class="tabs">@foreach($shops as $shop)<a class="tab {{ $selectedShop?->id === $shop->id ? 'active' : '' }}" href="{{ route('client.dashboard',['shop_id'=>$shop->shop_id,'start_date'=>$startDate,'end_date'=>$endDate]) }}">{{ $shop->name }} · {{ $shop->region }}</a>@endforeach</nav></div>
<section class="filter"><form id="refresh-form" style="display:contents" method="POST" action="{{ route('client.dashboard.reports.store') }}">@csrf<label>Shop<select name="shop_id">@foreach($shops as $shop)<option value="{{ $shop->shop_id }}" @selected($selectedShop?->id === $shop->id)>{{ $shop->name }} · {{ $shop->region }}</option>@endforeach</select></label><label>Start date<input name="start_date" type="date" value="{{ $startDate }}"></label><label>End date (exclusive)<input name="end_date" type="date" value="{{ $endDate }}"></label><button id="refresh-btn" class="btn primary">Refresh report</button>@if(count($availableBrands))<div class="brand-filter"><span class="metric-label" style="align-self:center">Brands</span>@foreach($availableBrands as $brand)<label class="brand-pill"><input type="checkbox" name="brands[]" value="{{ $brand }}" @checked(in_array($brand, $selectedBrands, true))>{{ $brand }}</label>@endforeach<span class="metric-note">None selected shows all brands</span></div>@endif</form></section>
<script>
document.getElementById('refresh-form')?.addEventListener('submit', function (e) {
 e.preventDefault();
 var form = e.target, btn = document.getElementById('refresh-btn');
 if (btn.disabled) return;
 btn.disabled = true;
 btn.textContent = 'Refreshing…';
 fetch(form.action, { method: 'POST', body: new FormData(form), headers: { 'Accept': 'text/html' } })
  .then(function (res) {
   if (res.status === 429) {
    alert('Too many refresh requests in a short time. Please wait a moment and try again.');
    btn.disabled = false;
    btn.textContent = 'Refresh report';
    return;
   }
   if (res.ok || res.redirected) {
    window.location.href = res.url;
    return;
   }
   alert('The report could not be refreshed. Please try again.');
   btn.disabled = false;
   btn.textContent = 'Refresh report';
  })
  .catch(function () {
   alert('The report could not be refreshed — check your connection and try again.');
   btn.disabled = false;
   btn.textContent = 'Refresh report';
  });
});
document.querySelectorAll('#refresh-form input[name="brands[]"]').forEach(function (checkbox) {
 checkbox.addEventListener('change', function () {
  document.getElementById('refresh-form')?.requestSubmit();
 });
});
</script>
<section class="section"><div class="section-head"><div><div class="eyebrow">Monthly history</div><h2>Saved months</h2><p>Select a saved month to view its database snapshot without requesting live data.</p></div></div>@forelse($monthlyHistory->groupBy(fn($m) => $m->period_start->format('Y')) as $year => $yearMonths)<div class="eyebrow" style="margin:{{ $loop->first ? '0' : '16px' }} 0 8px">{{ $year }}</div><div class="tabs">@foreach($yearMonths as $month)<a class="tab {{ $selectedShop && $month->period_start->toDateString() === $startDate && $month->period_end->toDateString() === $endDate ? 'active' : '' }}" href="{{ route('client.dashboard', ['shop_id'=>$selectedShop->shop_id,'start_date'=>$month->period_start->toDateString(),'end_date'=>$month->period_end->toDateString()]) }}">{{ $month->period_start->format('M Y') }} · {{ $month->orders_complete ? 'Saved' : 'Incomplete' }}</a>@endforeach</div>@empty<p class="empty">No months saved yet. Historical months must first be imported from the API.</p>@endforelse</section>
@if($report?->status === 'pending' || $report?->status === 'running')<section class="section"><div class="status"><strong>Preparing the report.</strong> The page will refresh in 10 seconds; larger date ranges are processed in the background.</div><meta http-equiv="refresh" content="10"></section>
@elseif($report?->status === 'failed')<section class="section"><div class="status warn">{{ $report->error_message }}</div></section>
@elseif($report?->status === 'completed')
@php($summary = $report->result ?? [])
@php($orders = $summary['order_value_summary'] ?? [])
@php($status = $summary['status_summary'] ?? [])
@php($dash = $summary['dashboard_summary'] ?? [])
@php($finance = $summary['finance_summary'] ?? [])
@php($currency = $orders['currency'] ?? 'PHP')
@php($productMax = max(1, (float) data_get($dash, 'top_products.0.value', 1)))
@php($brandLabel = empty($selectedBrands) ? 'All brands' : implode(' + ', $selectedBrands))
<section class="section"><div class="section-head"><div><div class="eyebrow">01 · Overview</div><h2>Commercial summary</h2><p>{{ data_get($summary,'date_range.start') }} to {{ data_get($summary,'date_range.end_exclusive') }} exclusive · {{ data_get($summary,'date_range.timezone','Asia/Manila') }} · {{ $currency }} · {{ $brandLabel }}</p></div><div class="status {{ ($orders['complete'] ?? false) ? '' : 'warn' }}">{{ ($orders['complete'] ?? false) ? 'Complete all-page data' : 'Check data completeness' }} · {{ number_format((int)($status['pages_fetched'] ?? 0)) }} pages</div></div>@php($fulfilGmv = max(0.01, (float)($orders['calculated_gmv'] ?? 0)))@php($fulfilOk = max(0, min(100, ((float)($dash['nmv'] ?? 0) / $fulfilGmv) * 100)))<div class="fulfil"><div class="fulfil-label"><span>Fulfilled value</span><span>Canceled value</span></div><div class="fulfil-bar"><i class="ok" style="width:{{ number_format($fulfilOk,2) }}%"></i><i class="no" style="width:{{ number_format(100-$fulfilOk,2) }}%"></i></div></div><div class="kpis"><div class="card"><div class="metric-label">Net order value</div><div class="metric positive">{{ number_format((float)($dash['nmv'] ?? 0),2) }}</div><div class="metric-note">{{ $currency }} · GMV less canceled value; refunds excluded</div></div><div class="card"><div class="metric-label">Calculated GMV</div><div class="metric">{{ number_format((float)($orders['calculated_gmv'] ?? 0),2) }}</div><div class="metric-note">{{ $currency }} · subtotal plus platform discount</div></div><div class="card"><div class="metric-label">Orders scanned</div><div class="metric">{{ number_format((int)($status['records_scanned'] ?? 0)) }}</div><div class="metric-note">{{ number_format((int)($dash['completed_orders'] ?? 0)) }} delivered / completed</div></div><div class="card"><div class="metric-label">Average order value</div><div class="metric">{{ number_format((float)($dash['aov'] ?? 0),2) }}</div><div class="metric-note">{{ $currency }} · non-canceled orders</div></div></div></section>
<section class="section"><div class="section-head"><div><div class="eyebrow">02 · Operational health</div><h2>Orders, cancellations & subsidy</h2></div></div><div class="kpis"><div class="card"><div class="metric-label">Cancel rate by value</div><div class="metric negative">{{ number_format((float)($dash['cancel_rate_by_value'] ?? 0),2) }}%</div><div class="metric-note">{{ number_format((float)($dash['canceled_value'] ?? 0),2) }} {{ $currency }} canceled value</div></div><div class="card"><div class="metric-label">Canceled orders</div><div class="metric negative">{{ number_format((int)($dash['canceled_orders'] ?? 0)) }}</div><div class="metric-note">Of {{ number_format((int)($status['records_scanned'] ?? 0)) }} scanned orders</div></div><div class="card"><div class="metric-label">Units sold</div><div class="metric">{{ number_format((int)($dash['units'] ?? 0)) }}</div><div class="metric-note">Non-canceled line-item units</div></div><div class="card"><div class="metric-label">Platform subsidy</div><div class="metric" style="color:var(--amber)">{{ number_format((float)($dash['platform_subsidy'] ?? 0),2) }}</div><div class="metric-note">{{ $currency }} · platform discount amount</div></div></div></section>
<section class="section"><div class="section-head"><div><div class="eyebrow">03 · Finance</div><h2>Settlement snapshot</h2><p>Statement totals may settle on a different schedule than order creation.@if(!empty($selectedBrands)) Always whole-shop — not affected by the brand filter.@endif</p></div></div>@if($summary['finance_unavailable'] ?? false)<div class="unavailable">{{ $summary['finance_unavailable'] }}</div>@else<div class="kpis"><div class="card"><div class="metric-label">Settlement amount</div><div class="metric positive">{{ number_format((float)($finance['settlement_amount'] ?? 0),2) }}</div><div class="metric-note">{{ $finance['currency'] ?? $currency }} · {{ number_format((int)($finance['statement_records'] ?? 0)) }} statements</div></div><div class="card"><div class="metric-label">Net sales</div><div class="metric">{{ number_format((float)($finance['net_sales_amount'] ?? 0),2) }}</div><div class="metric-note">Finance statement total</div></div><div class="card"><div class="metric-label">Fees</div><div class="metric negative">{{ number_format((float)($finance['fee_amount'] ?? 0),2) }}</div><div class="metric-note">Fees recorded in statements</div></div><div class="card"><div class="metric-label">Shipping cost</div><div class="metric">{{ number_format((float)($finance['shipping_cost_amount'] ?? 0),2) }}</div><div class="metric-note">Statement shipping cost</div></div></div>@endif</section>
<section class="section"><div class="section-head"><div><div class="eyebrow">04 · Monthly trajectory</div><h2>Stored API history</h2><p>Monthly Shop totals are stored from complete API pagination. Finance uses statement dates; order metrics use order-created dates.</p></div></div>@if($monthlyHistory->count() >= 2)@php($chartMonths = $monthlyHistory->values())@php($chartCount = $chartMonths->count())@php($gmvSeries = $chartMonths->map(fn($m) => (float) data_get($m->order_summary, 'order_value_summary.calculated_gmv', 0))->all())@php($nmvSeries = $chartMonths->map(fn($m) => (float) data_get($m->order_summary, 'dashboard_summary.nmv', 0))->all())@php($crSeries = $chartMonths->map(fn($m) => (float) data_get($m->order_summary, 'dashboard_summary.cancel_rate_by_value', 0))->all())@php($maxGmv = max(1.0, max($gmvSeries)))@php($padL = 8)@php($padT = 16)@php($plotW = 744)@php($plotH = 176)@php($xAt = fn($i) => $padL + ($chartCount > 1 ? ($plotW / ($chartCount - 1)) * $i : $plotW / 2))@php($yAt = fn($v) => $padT + $plotH * (1 - $v / $maxGmv))@php($gmvPath = 'M'.implode(' L', collect($gmvSeries)->map(fn($v, $i) => round($xAt($i), 1).','.round($yAt($v), 1))->all()))@php($gmvArea = $gmvPath.' L'.round($xAt($chartCount - 1), 1).','.round($padT + $plotH, 1).' L'.round($xAt(0), 1).','.round($padT + $plotH, 1).' Z')@php($nmvPath = 'M'.implode(' L', collect($nmvSeries)->map(fn($v, $i) => round($xAt($i), 1).','.round($yAt($v), 1))->all()))@php($maxCr = max(10.0, ceil(max($crSeries) / 10) * 10))@php($padT2 = 10)@php($plotH2 = 36)@php($yAt2 = fn($v) => $padT2 + $plotH2 * (1 - $v / $maxCr))@php($crPath = 'M'.implode(' L', collect($crSeries)->map(fn($v, $i) => round($xAt($i), 1).','.round($yAt2($v), 1))->all()))@php($subsidySeries = $chartMonths->map(fn($m) => (float) data_get($m->order_summary, 'dashboard_summary.platform_subsidy', 0))->all())@php($momSeries = collect($nmvSeries)->map(function($v, $i) use ($nmvSeries) { if ($i === 0) return null; $prev = $nmvSeries[$i - 1]; return $prev == 0.0 ? null : (($v - $prev) / abs($prev)) * 100; })->all())@php($trendPoints = $chartMonths->map(fn($m, $i) => ['label' => $m->period_start->format('F Y'), 'short' => $m->period_start->format('M'), 'x' => round($xAt($i), 1), 'gmvY' => round($yAt($gmvSeries[$i]), 1), 'nmvY' => round($yAt($nmvSeries[$i]), 1), 'gmv' => $gmvSeries[$i], 'nmv' => $nmvSeries[$i], 'subsidy' => $subsidySeries[$i], 'cancel' => $crSeries[$i]])->all())<div class="card chart-wrap" style="margin-bottom:14px"><svg id="trendChart" viewBox="0 0 760 200" width="100%" height="180" role="img" aria-label="Monthly GMV and NMV trend"><text x="8" y="10" font-family="IBM Plex Mono" font-size="9" fill="var(--ink-faint)">{{ number_format($maxGmv,0) }} {{ $currency }}</text><line x1="8" y1="16" x2="752" y2="16" stroke="var(--line)" stroke-width="1"/><line x1="8" y1="192" x2="752" y2="192" stroke="var(--line)" stroke-width="1"/><path d="{{ $gmvArea }}" fill="var(--eufy)" opacity=".16"/><path d="{{ $gmvPath }}" fill="none" stroke="var(--eufy)" stroke-width="2"/><path d="{{ $nmvPath }}" fill="none" stroke="var(--accent-fill)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/><g font-family="IBM Plex Mono" font-size="9.5" fill="var(--ink-faint)">@foreach($chartMonths as $i => $m)<text x="{{ round($xAt($i),1) }}" y="200" text-anchor="{{ $i===0 ? 'start' : ($i===$chartCount-1 ? 'end' : 'middle') }}">{{ $m->period_start->format('M') }}</text>@endforeach</g><line id="trendCrosshair" x1="0" y1="16" x2="0" y2="192" stroke="var(--ink-faint)" stroke-width="1" stroke-dasharray="3 3" opacity="0"/><g fill="var(--eufy)">@foreach($trendPoints as $i => $p)<circle class="trend-marker" data-i="{{ $i }}" cx="{{ $p['x'] }}" cy="{{ $p['gmvY'] }}" r="3"/>@endforeach</g><g fill="var(--accent-fill)">@foreach($trendPoints as $i => $p)<circle class="trend-marker" data-i="{{ $i }}" cx="{{ $p['x'] }}" cy="{{ $p['nmvY'] }}" r="3"/>@endforeach</g><g id="trendHit" fill="transparent">@foreach($trendPoints as $i => $p)<rect data-i="{{ $i }}" x="{{ max(8, $p['x'] - ($plotW / max(1,$chartCount - 1)) / 2) }}" y="0" width="{{ $plotW / max(1,$chartCount - 1) }}" height="200"/>@endforeach</g></svg><div class="chart-tooltip" id="trendTooltip"></div><div style="display:flex;gap:18px;margin:2px 0 10px;font-size:12px"><span style="display:flex;align-items:center;gap:6px"><i style="width:16px;height:3px;background:var(--eufy);display:inline-block;border-radius:2px"></i>GMV</span><span style="display:flex;align-items:center;gap:6px"><i style="width:16px;height:3px;background:var(--accent-fill);display:inline-block;border-radius:2px"></i>NMV</span></div><svg viewBox="0 0 760 60" width="100%" height="54" role="img" aria-label="Monthly cancel rate trend"><line x1="8" y1="60" x2="752" y2="60" stroke="var(--line)" stroke-width="1"/><path d="{{ $crPath }}" fill="none" stroke="var(--bad-fill)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><text x="8" y="12" font-family="IBM Plex Mono" font-size="9" fill="var(--ink-faint)">Cancel rate — {{ number_format($maxCr,0) }}% ceiling</text></svg></div>
<script>
(function(){
  var months = {!! json_encode($trendPoints) !!};
  var svg = document.getElementById('trendChart');
  var wrap = svg.closest('.chart-wrap');
  var tooltip = document.getElementById('trendTooltip');
  var crosshair = document.getElementById('trendCrosshair');
  var markers = svg.querySelectorAll('.trend-marker');
  var fmt = function(n){ return '{{ $currency }} ' + Number(n).toLocaleString('en-US', {maximumFractionDigits:0}); };
  function showMonth(i){
    var m = months[i]; if(!m) return;
    crosshair.setAttribute('x1', m.x); crosshair.setAttribute('x2', m.x); crosshair.setAttribute('opacity', 1);
    markers.forEach(function(el){ el.setAttribute('r', el.dataset.i == i ? 5 : 3); });
    tooltip.innerHTML =
      '<div class="tt-title">'+m.label+'</div>'+
      '<div class="tt-row"><span><i style="background:var(--eufy)"></i>GMV</span><span>'+fmt(m.gmv)+'</span></div>'+
      '<div class="tt-row"><span><i style="background:var(--accent-fill)"></i>NMV</span><span>'+fmt(m.nmv)+'</span></div>'+
      '<div class="tt-row"><span><i style="background:var(--soundcore-fill)"></i>Subsidy</span><span>'+fmt(m.subsidy)+'</span></div>'+
      '<div class="tt-row"><span><i style="background:var(--bad-fill)"></i>Cancel rate</span><span>'+Number(m.cancel).toFixed(2)+'%</span></div>';
    var r = wrap.getBoundingClientRect();
    var scale = r.width / 760;
    tooltip.style.left = Math.max(60, Math.min(r.width - 60, m.x * scale)) + 'px';
    tooltip.style.top = '10px';
    tooltip.style.opacity = 1;
  }
  svg.querySelectorAll('#trendHit rect').forEach(function(rect){
    rect.addEventListener('mouseenter', function(){ showMonth(+rect.dataset.i); });
  });
  svg.addEventListener('mouseleave', function(){
    crosshair.setAttribute('opacity', 0);
    markers.forEach(function(el){ el.setAttribute('r', 3); });
    tooltip.style.opacity = 0;
  });
})();
</script>
@if($chartCount >= 2)@php($momMax = max(5.0, ceil(max(array_map(fn($v) => abs($v ?? 0), $momSeries)) / 5) * 5))@php($momW = $plotW / ($chartCount - 1))<div class="card chart-wrap" style="margin-bottom:14px"><div style="font-size:12.5px;font-weight:700;margin-bottom:10px">Month-over-month NMV change</div><svg id="momChart" viewBox="0 0 760 110" width="100%" height="100" role="img" aria-label="Month over month NMV change"><line x1="8" y1="55" x2="752" y2="55" stroke="var(--line)" stroke-width="1"/><g font-family="IBM Plex Mono" font-size="9" fill="var(--ink-faint)">@foreach($trendPoints as $i => $p)@if($i > 0)@php($pct = $momSeries[$i])@php($barH = $pct === null ? 0 : min(40, abs($pct) / $momMax * 40))@php($barY = $pct !== null && $pct >= 0 ? 55 - $barH : 55)<rect class="mom-bar" data-month="{{ $p['label'] }}" data-pct="{{ $pct === null ? 0 : round($pct,1) }}" data-prev="{{ $nmvSeries[$i-1] }}" data-cur="{{ $nmvSeries[$i] }}" x="{{ round($xAt($i) - $momW * 0.3, 1) }}" y="{{ round($barY,1) }}" width="{{ round($momW * 0.6, 1) }}" height="{{ round($barH,1) }}" fill="{{ $pct !== null && $pct < 0 ? 'var(--bad-fill)' : 'var(--accent-fill)' }}"/><text x="{{ round($xAt($i),1) }}" y="{{ $pct !== null && $pct >= 0 ? round($barY - 4,1) : round($barY + $barH + 12,1) }}" text-anchor="middle">{{ $pct === null ? '—' : ($pct >= 0 ? '+' : '').number_format($pct,1).'%' }}</text><text x="{{ round($xAt($i),1) }}" y="70" text-anchor="middle">{{ $p['short'] }}</text>@endif @endforeach</g></svg><div class="chart-tooltip" id="momTooltip"></div></div>
<script>
(function(){
  var wrap = document.getElementById('momChart').closest('.chart-wrap');
  var tooltip = document.getElementById('momTooltip');
  var fmt = function(n){ return '{{ $currency }} ' + Number(n).toLocaleString('en-US', {maximumFractionDigits:0}); };
  wrap.querySelectorAll('.mom-bar').forEach(function(bar){
    bar.addEventListener('mousemove', function(e){
      tooltip.innerHTML =
        '<div class="tt-title">'+bar.dataset.month+'</div>'+
        '<div class="tt-row"><span>NMV change</span><span>'+bar.dataset.pct+'%</span></div>'+
        '<div class="tt-row"><span>Prior month</span><span>'+fmt(bar.dataset.prev)+'</span></div>'+
        '<div class="tt-row"><span>This month</span><span>'+fmt(bar.dataset.cur)+'</span></div>';
      var r = wrap.getBoundingClientRect();
      tooltip.style.left = (e.clientX - r.left) + 'px';
      tooltip.style.top = (e.clientY - r.top - 10) + 'px';
      tooltip.style.opacity = 1;
    });
    bar.addEventListener('mouseleave', function(){ tooltip.style.opacity = 0; });
  });
})();
</script>
@endif
@endif<div class="card rows">@php($previousNmv = null)@forelse($monthlyHistory as $month)@php($monthOrders = $month->order_summary['order_value_summary'] ?? [])@php($monthDash = $month->order_summary['dashboard_summary'] ?? [])@php($monthNmv = (float)($monthDash['nmv'] ?? 0))@php($mom = $previousNmv === null || $previousNmv == 0.0 ? null : (($monthNmv - $previousNmv) / abs($previousNmv)) * 100)@php($previousNmv = $monthNmv)<div class="row"><div><div class="name">{{ $month->period_start->format('F Y') }}</div><div class="detail">GMV {{ number_format((float)($monthOrders['calculated_gmv'] ?? 0),2) }} · Subsidy {{ number_format((float)($monthDash['platform_subsidy'] ?? 0),2) }} · Cancel {{ number_format((float)($monthDash['cancel_rate_by_value'] ?? 0),2) }}% · {{ $month->orders_complete ? 'complete' : 'incomplete' }}</div></div><div class="amount">NMV {{ number_format($monthNmv,2) }}@if($mom !== null)<br><span class="{{ $mom >= 0 ? 'positive' : 'negative' }}" style="font-size:11px">{{ $mom >= 0 ? '+' : '' }}{{ number_format($mom,1) }}% MoM</span>@endif</div></div>@empty<div class="empty">No stored history yet. An administrator can backfill months through the read-only TikTok Shop API.</div>@endforelse</div></section>
@if(!empty($dash['brand_mix']))
<section class="section"><div class="section-head"><div><div class="eyebrow">Brand mix</div><h2>Revenue by brand</h2><p>{{ $currency }} · Same GMV/NMV/cancel-rate formulas as the headline KPIs, split by product-name keyword match</p></div></div>
@if(!empty($dash['brand_mix']))
@php($brandTotalNmv = max(0.01, collect($dash['brand_mix'])->sum(fn($b) => max(0, (float)$b['nmv']))))
@php($brandOffset = 0)
<div class="card" style="margin-bottom:14px"><div class="donut-grid chart-wrap"><svg viewBox="0 0 120 120" width="120" height="120" role="img" aria-label="Brand mix donut"><circle cx="60" cy="60" r="45" fill="none" stroke="var(--surface-2)" stroke-width="18"/>@foreach($dash['brand_mix'] as $brand)@php($bPct = max(0, (float)$brand['nmv']) / $brandTotalNmv * 100)@php($bDash = round($bPct / 100 * 282.6, 2))<circle class="donut-seg" data-name="{{ $brand['name'] }}" data-value="{{ (float)$brand['nmv'] }}" data-pct="{{ number_format($bPct,2) }}" data-extra="{{ number_format((int)$brand['units']) }} units" cx="60" cy="60" r="45" fill="none" stroke="var(--{{ ['accent-fill','soundcore-fill','eufy','solix-fill','bad-fill'][$loop->index % 5] }})" stroke-width="18" stroke-dasharray="{{ $bDash }} 282.6" stroke-dashoffset="{{ -$brandOffset }}" transform="rotate(-90 60 60)"/>@php($brandOffset += $bDash)@endforeach</svg><div class="legend" style="display:flex;flex-direction:column;gap:8px;font-size:11.5px">@foreach($dash['brand_mix'] as $brand)@php($bPct2 = max(0, (float)$brand['nmv']) / $brandTotalNmv * 100)<span style="display:inline-flex;align-items:center;gap:6px"><span class="swatch swatch-{{ $loop->index % 5 }}"></span>{{ $brand['name'] }} &middot; {{ number_format($bPct2,1) }}%</span>@endforeach</div><div class="chart-tooltip" id="brandDonutTooltip"></div></div></div>
<script>
(function(){
  var currency = @json($currency);
  document.querySelectorAll('#brandDonutTooltip').forEach(function(tooltip){
    var wrap = tooltip.closest('.chart-wrap');
    wrap.querySelectorAll('.donut-seg').forEach(function(seg){
      seg.addEventListener('mousemove', function(e){
        tooltip.innerHTML =
          '<div class="tt-title">'+seg.dataset.name+'</div>'+
          '<div class="tt-row"><span>NMV</span><span>'+currency+' '+Number(seg.dataset.value).toLocaleString('en-US',{maximumFractionDigits:0})+'</span></div>'+
          '<div class="tt-row"><span>Share</span><span>'+seg.dataset.pct+'%</span></div>'+
          '<div class="tt-row"><span>Units</span><span>'+seg.dataset.extra+'</span></div>';
        var r = wrap.getBoundingClientRect();
        tooltip.style.left = (e.clientX - r.left) + 'px';
        tooltip.style.top = (e.clientY - r.top - 10) + 'px';
        tooltip.style.opacity = 1;
      });
      seg.addEventListener('mouseleave', function(){ tooltip.style.opacity = 0; });
    });
  });
})();
</script>
@endif
<div class="card rows">@foreach($dash['brand_mix'] as $brand)<div class="row"><div><div class="name"><span class="swatch swatch-{{ $loop->index % 5 }}"></span>{{ $brand['name'] }}</div><div class="detail">{{ number_format((int)$brand['units']) }} units sold</div></div><div class="amount">{{ number_format((float)$brand['nmv'],2) }}<br><span class="negative" style="font-size:11px">{{ number_format((float)$brand['cancel_rate_by_value'],2) }}% canceled</span></div></div>@endforeach</div></section>
@endif
@if(!empty($dash['campaign_periods']))
<section class="section"><div class="section-head"><div><div class="eyebrow">Campaign periods</div><h2>Orders by day-of-month window</h2><p>Fixed default windows; cancel rate is value-weighted, same formula as the headline KPI</p></div></div><div class="card rows">@foreach($dash['campaign_periods'] as $period)<div class="row"><div><div class="name">{{ $period['name'] }}</div><div class="detail">{{ number_format((int)$period['orders']) }} orders · GMV {{ number_format((float)$period['gmv'],2) }} · Subsidy {{ number_format((float)$period['platform_subsidy'],2) }}</div></div><div class="amount">NMV {{ number_format((float)$period['nmv'],2) }}<br><span class="negative" style="font-size:11px">{{ number_format((float)$period['cancel_rate_by_value'],2) }}% canceled</span></div></div>@endforeach</div></section>
@endif
@if($summary['channel_performance_unavailable'] ?? false)
<section class="section"><div class="section-head"><div><div class="eyebrow">Channel performance</div><h2>Product cards, LIVE &amp; Videos</h2></div></div><div class="unavailable">{{ $summary['channel_performance_unavailable'] }}</div></section>
@elseif(!empty($summary['channel_performance']))
<section class="section"><div class="section-head"><div><div class="eyebrow">Channel performance</div><h2>Product cards, LIVE &amp; Videos</h2><p>Revenue, orders, and units only &mdash; subsidy and cancel rate aren't available at this granularity from TikTok's analytics API.@if(!empty($selectedBrands)) Always whole-shop — not affected by the brand filter.@endif</p></div></div>
@if(!empty($summary['channel_performance']))
@php($channelTotalGmv = max(0.01, collect($summary['channel_performance'])->sum(fn($c) => max(0, (float)$c['gmv']))))
@php($channelOffset = 0)
<div class="card" style="margin-bottom:14px"><div class="donut-grid chart-wrap"><svg viewBox="0 0 120 120" width="120" height="120" role="img" aria-label="Channel mix donut"><circle cx="60" cy="60" r="45" fill="none" stroke="var(--surface-2)" stroke-width="18"/>@foreach($summary['channel_performance'] as $channel)@php($cPct = max(0, (float)$channel['gmv']) / $channelTotalGmv * 100)@php($cDash = round($cPct / 100 * 282.6, 2))<circle class="donut-seg" data-name="{{ $channel['name'] }}" data-value="{{ (float)$channel['gmv'] }}" data-pct="{{ number_format($cPct,2) }}" data-extra="{{ $channel['orders'] === null ? 'orders not available' : number_format((int)$channel['orders']).' orders' }}" cx="60" cy="60" r="45" fill="none" stroke="var(--{{ ['accent-fill','eufy','soundcore-fill','solix-fill','bad-fill'][$loop->index % 5] }})" stroke-width="18" stroke-dasharray="{{ $cDash }} 282.6" stroke-dashoffset="{{ -$channelOffset }}" transform="rotate(-90 60 60)"/>@php($channelOffset += $cDash)@endforeach</svg><div class="legend" style="display:flex;flex-direction:column;gap:8px;font-size:11.5px">@foreach($summary['channel_performance'] as $channel)@php($cPct2 = max(0, (float)$channel['gmv']) / $channelTotalGmv * 100)<span style="display:inline-flex;align-items:center;gap:6px"><span class="swatch" style="background:var(--{{ ['accent-fill','eufy','soundcore-fill','solix-fill','bad-fill'][$loop->index % 5] }})"></span>{{ $channel['name'] }} &middot; {{ number_format($cPct2,1) }}%</span>@endforeach</div><div class="chart-tooltip" id="channelDonutTooltip"></div></div></div>
<script>
(function(){
  var currency = @json($currency);
  document.querySelectorAll('#channelDonutTooltip').forEach(function(tooltip){
    var wrap = tooltip.closest('.chart-wrap');
    wrap.querySelectorAll('.donut-seg').forEach(function(seg){
      seg.addEventListener('mousemove', function(e){
        tooltip.innerHTML =
          '<div class="tt-title">'+seg.dataset.name+'</div>'+
          '<div class="tt-row"><span>GMV</span><span>'+currency+' '+Number(seg.dataset.value).toLocaleString('en-US',{maximumFractionDigits:0})+'</span></div>'+
          '<div class="tt-row"><span>Share</span><span>'+seg.dataset.pct+'%</span></div>'+
          '<div class="tt-row"><span></span><span>'+seg.dataset.extra+'</span></div>';
        var r = wrap.getBoundingClientRect();
        tooltip.style.left = (e.clientX - r.left) + 'px';
        tooltip.style.top = (e.clientY - r.top - 10) + 'px';
        tooltip.style.opacity = 1;
      });
      seg.addEventListener('mouseleave', function(){ tooltip.style.opacity = 0; });
    });
  });
})();
</script>
@endif
<div class="kpis">@foreach($summary['channel_performance'] as $channel)<div class="card"><div class="metric-label">{{ $channel['name'] }}</div><div class="metric">{{ number_format((float)$channel['gmv'],2) }}</div><div class="metric-note">{{ $currency }} · {{ $channel['orders'] === null ? 'orders not available' : number_format((int)$channel['orders']).' orders' }}</div></div>@endforeach</div></section>
@endif
<section class="section grid"><div><div class="section-head"><div><div class="eyebrow">05 · Best-selling SKUs</div><h2>Top products by order value</h2><p>Non-canceled line items only</p></div></div><div class="card rows">@forelse(array_slice($dash['top_products'] ?? [],0,10) as $item)<div class="row"><div><div class="name">{{ $item['name'] }}</div><div class="detail">{{ $item['sku'] }} · {{ number_format((int)$item['units']) }} units</div><div class="bar"><i style="width:{{ min(100,((float)$item['value'] / $productMax) * 100) }}%"></i></div></div><div class="amount">{{ number_format((float)$item['value'],2) }}</div></div>@empty<div class="empty">TikTok did not return SKU detail for this period.</div>@endforelse</div></div><div><div class="section-head"><div><div class="eyebrow">06 · Cancellations</div><h2>Top canceled SKUs</h2><p>Highest canceled value</p></div></div><div class="card rows">@forelse(array_slice($dash['top_cancel_skus'] ?? [],0,10) as $item)<div class="row"><div><div class="name">{{ $item['name'] }}</div><div class="detail">{{ $item['sku'] }} · {{ number_format((int)$item['units']) }} units canceled</div><div class="bar cancel"><i style="width:{{ min(100,((float)$item['value'] / max(1,(float)data_get($dash,'top_cancel_skus.0.value',1))) * 100) }}%"></i></div></div><div class="amount negative">{{ number_format((float)$item['value'],2) }}</div></div>@empty<div class="empty">No canceled SKU detail was returned.</div>@endforelse</div></div></section>
<section class="section grid"><div><div class="section-head"><div><div class="eyebrow">05 · Payment methods</div><h2>Order payment mix</h2><p>By order count</p></div></div><div class="card rows">@forelse(array_slice($dash['payment_methods'] ?? [],0,10) as $item)<div class="row"><span class="name">{{ $item['name'] }}</span><span class="amount">{{ number_format((int)$item['orders']) }} orders</span></div>@empty<div class="empty">TikTok did not return payment-method data for this Shop and period.</div>@endforelse</div></div><div><div class="section-head"><div><div class="eyebrow">06 · Delivery geography</div><h2>Top locations</h2><p>Only country, region, province, or city—never exact addresses</p></div></div><div class="card rows">@forelse(array_slice($dash['locations'] ?? [],0,10) as $item)<div class="row"><span class="name">{{ $item['name'] }}</span><span class="amount">{{ number_format((int)$item['orders']) }} orders</span></div>@empty<div class="empty">No coarse location was returned by TikTok for this period.</div>@endforelse</div></div></section>
<section class="section"><div class="section-head"><div><div class="eyebrow">07 · Why orders cancel</div><h2>Cancellation reasons</h2></div></div><div class="card rows">@forelse(array_slice($dash['cancel_reasons'] ?? [],0,10) as $item)<div class="row"><span class="name">{{ $item['name'] }}</span><span class="amount negative">{{ number_format((int)$item['orders']) }}</span></div>@empty<div class="empty">TikTok did not return a cancellation reason breakdown.</div>@endforelse</div></section>
<section class="section"><div class="section-head"><div><div class="eyebrow">08 · Paid advertising</div><h2>TikTok Ads-attributed performance</h2><p>@if($adsAccount) {{ $adsAccount->name }} · {{ $adsAccount->currency ?? 'PHP' }} @endif Never summed with Shop GMV/NMV above — a separate revenue scope, reconciled by date range only.</p></div>@if($adsAccount)<span class="status {{ $adsStatus === 'expiring' ? 'warn' : '' }}">{{ $adsStatus === 'expiring' ? 'Reconnect soon' : 'Live from TikTok Ads MCP' }}</span>@endif</div>
@if($adsStatus === 'disconnected')<div class="unavailable"><strong>TikTok Ads is not connected yet.</strong><br><br><a class="btn primary" style="margin-top:10px" href="{{ route('tiktok-ads.connect') }}">Connect TikTok Ads</a></div>
@elseif($adsError)<div class="unavailable"><strong>Ads performance is temporarily unavailable.</strong><br><br>{{ $adsError }}</div>
@elseif($adsPerformance)<div class="kpis"><div class="card"><div class="metric-label">Spend</div><div class="metric">{{ number_format((float)data_get($adsPerformance,'data.summary.spend',0),2) }}</div><div class="metric-note">{{ $adsPerformance['currency'] ?? 'PHP' }} · {{ data_get($adsPerformance,'date_range.start') }} to {{ data_get($adsPerformance,'date_range.end_exclusive') }} exclusive</div></div><div class="card"><div class="metric-label">Impressions</div><div class="metric">{{ number_format((int)data_get($adsPerformance,'data.summary.impressions',0)) }}</div><div class="metric-note">{{ number_format((int)data_get($adsPerformance,'data.summary.clicks',0)) }} clicks</div></div><div class="card"><div class="metric-label">Conversions</div><div class="metric">{{ number_format((float)data_get($adsPerformance,'data.summary.conversions',0),0) }}</div><div class="metric-note">{{ number_format((float)data_get($adsPerformance,'data.summary.complete_payment',0),2) }} complete payment value</div></div><div class="card"><div class="metric-label">ROAS</div><div class="metric {{ (data_get($adsPerformance,'data.summary.roas') ?? 0) >= 1 ? 'positive' : '' }}">{{ data_get($adsPerformance,'data.summary.roas') === null ? '—' : number_format((float)data_get($adsPerformance,'data.summary.roas'),2).'x' }}</div><div class="metric-note">Complete payment value ÷ spend</div></div></div>
@else<div class="unavailable">No TikTok Ads account matched this Shop yet.</div>@endif</section>
<section class="section"><div class="section-head"><div><div class="eyebrow">09 · Redacted order review</div><h2>Commercial detail sample</h2><p>Maximum 100 records. Customer names, contacts, exact addresses, tracking, and payment references are removed.</p></div></div><div class="card rows sample">@forelse($summary['order_details'] ?? [] as $detail)@php($items = is_array($detail['line_items'] ?? null) ? $detail['line_items'] : [])<div class="row"><div><span class="pill">{{ $detail['status'] ?? 'UNKNOWN' }}</span>@forelse($items as $item)<div class="name" style="margin-top:8px">{{ $item['product_name'] ?? $item['product_title'] ?? $item['sku_name'] ?? 'Product detail unavailable' }}</div><div class="detail">SKU: {{ $item['seller_sku'] ?? $item['sku_id'] ?? 'Not returned' }} · Qty: {{ $item['quantity'] ?? $item['sku_quantity'] ?? '—' }}</div>@empty<div class="detail" style="margin-top:8px">No line-item detail returned.</div>@endforelse</div></div>@empty<div class="empty">No redacted order details were returned.</div>@endforelse</div></section>
<footer class="footer">Source: {{ $summary['source'] ?? 'TikTok Shop Open API' }} · {{ $summary['attribution'] ?? 'Total Shop data, not TikTok Ads-attributed revenue.' }} · Full-period values are used only when all API pages and required line-item prices were received.</footer>
@elseif($selectedShop)<section class="section"><div class="unavailable"><strong>Ready to prepare this dashboard.</strong><br><br>Select the date range above and choose <em>Refresh report</em>.</div></section>
@else<section class="section"><div class="status warn">No Shop is assigned to this dashboard account.</div></section>@endif
</main><script>
const menu=document.getElementById('section-menu');
const navLinks=[],navTargets=[];
document.querySelectorAll('.section-head').forEach((section,index)=>{
 const label=section.querySelector('.eyebrow');if(!label)return;
 const title=label.textContent.replace(/^\d+\s*·\s*/, '');
 label.textContent=String(index+1).padStart(2,'0')+' · '+title;
 section.id='dashboard-section-'+index;
 const link=document.createElement('a');link.href='#'+section.id;link.textContent=label.textContent;
 menu.appendChild(link);navLinks.push(link);navTargets.push(section);
});

// Click sets the active link immediately; scroll position takes back over shortly
// after (a side-by-side pair of sections shares one scroll position, so position
// tracking alone can't tell which of the two was clicked).
const FOCUS_Y=130;let suppressUntil=0,ticking=false;
function setActive(idx){navLinks.forEach((a,i)=>{if(i===idx)a.setAttribute('aria-current','location');else a.removeAttribute('aria-current');});}
navLinks.forEach((a,i)=>{a.addEventListener('click',()=>{setActive(i);suppressUntil=Date.now()+700;});});
function updateActive(){
 ticking=false;
 if(Date.now()<suppressUntil)return;
 const atBottom=window.innerHeight+window.scrollY>=document.documentElement.scrollHeight-2;
 if(atBottom){setActive(navTargets.length-1);return;}
 let bestIdx=0,bestTop=-Infinity;
 navTargets.forEach((t,i)=>{const top=t.getBoundingClientRect().top;if(top<=FOCUS_Y&&top>bestTop){bestTop=top;bestIdx=i;}});
 setActive(bestIdx);
}
window.addEventListener('scroll',()=>{if(ticking)return;ticking=true;requestAnimationFrame(updateActive);},{passive:true});
window.addEventListener('resize',updateActive);
updateActive();
</script></body></html>
