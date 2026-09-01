<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TikTokShop extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'tik_tok_shop_authorization_id',
        'shop_id',
        'shop_code',
        'shop_cipher',
        'name',
        'region',
        'seller_type',
    ];

    /** @var list<string> */
    protected $hidden = ['shop_cipher'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['shop_cipher' => 'encrypted'];
    }
}
