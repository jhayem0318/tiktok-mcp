<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['user_id', 'label', 'code_hash', 'expires_at', 'revoked_at'])]
class RemoteMcpInvite extends Model
{
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shops(): BelongsToMany
    {
        return $this->belongsToMany(TikTokShop::class, 'remote_mcp_invite_tik_tok_shop')
            ->withTimestamps();
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null
            && ($this->expires_at === null || $this->expires_at->isFuture())
            && ($this->user === null || $this->user->hasActiveClientAccess());
    }
}
