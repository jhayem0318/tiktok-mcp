<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['remote_mcp_client_id', 'remote_mcp_invite_id', 'token_hash', 'scopes', 'expires_at', 'revoked_at'])]
class RemoteMcpAccessToken extends Model
{
    protected function casts(): array
    {
        return ['scopes' => 'array', 'expires_at' => 'datetime', 'revoked_at' => 'datetime'];
    }

    public function invite(): BelongsTo
    {
        return $this->belongsTo(RemoteMcpInvite::class, 'remote_mcp_invite_id');
    }
}
