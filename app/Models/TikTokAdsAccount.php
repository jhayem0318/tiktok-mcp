<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TikTokAdsAccount extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'tik_tok_ads_authorization_id',
        'advertiser_id',
        'name',
        'currency',
        'timezone',
    ];

    public function authorization(): BelongsTo
    {
        return $this->belongsTo(TikTokAdsAuthorization::class, 'tik_tok_ads_authorization_id');
    }
}
