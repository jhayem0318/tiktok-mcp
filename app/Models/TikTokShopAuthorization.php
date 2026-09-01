<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TikTokShopAuthorization extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'app_key',
        'open_id',
        'seller_name',
        'seller_base_region',
        'user_type',
        'access_token',
        'refresh_token',
        'access_token_expires_at',
        'refresh_token_expires_at',
        'granted_scopes',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'access_token_expires_at' => 'immutable_datetime',
            'refresh_token_expires_at' => 'immutable_datetime',
            'granted_scopes' => 'array',
            'user_type' => 'integer',
        ];
    }
}
