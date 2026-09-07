<?php

return [

    /*
    |--------------------------------------------------------------------------
    | TikTok Shop Brand Classification
    |--------------------------------------------------------------------------
    |
    | Ordered list of brand keyword matchers per storefront. Line items are
    | classified by checking each brand's "match" keywords against the
    | product name, in order (most specific sub-brand first) — the first
    | match wins. A storefront with no entry here skips brand-mix
    | classification (falls back to a single bucket named after the shop).
    |
    | Looked up by TikTok shop_id first (add real shop_ids under "by_shop_id"
    | once known), then by shop name under "by_name" as a fallback — the
    | shop_id isn't exposed through the redacted MCP tool responses, so
    | seeding by name lets this ship before that value is on hand.
    |
    */

    'by_shop_id' => [
        //
    ],

    'by_name' => [
        'Anker Charging' => [
            ['name' => 'Soundcore', 'match' => ['soundcore']],
            ['name' => 'Eufy', 'match' => ['eufy']],
            ['name' => 'Solix', 'match' => ['solix']],
            ['name' => 'Anker', 'match' => ['anker']],
        ],
    ],

];
