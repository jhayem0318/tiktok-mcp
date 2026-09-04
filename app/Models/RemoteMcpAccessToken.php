<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['remote_mcp_client_id', 'remote_mcp_invite_id', 'token_hash', 'scopes', 'expires_at', 'revoked_at'])]
class RemoteMcpAccessToken extends Model
{
    protected function casts(): array
    {
        return ['scopes' => 'array', 'expires_at' => 'datetime', 'revoked_at' => 'datetime'];
    }
}
