<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'tik_tok_shop_id', 'period_start', 'period_end', 'currency', 'source', 'order_summary',
    'finance_summary', 'channel_summary', 'orders_complete', 'finance_available', 'synced_at',
])]
class ShopMonthlyMetric extends Model
{
    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'order_summary' => 'array',
            'finance_summary' => 'array',
            'channel_summary' => 'array',
            'orders_complete' => 'boolean',
            'finance_available' => 'boolean',
            'synced_at' => 'datetime',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(TikTokShop::class, 'tik_tok_shop_id');
    }
}
