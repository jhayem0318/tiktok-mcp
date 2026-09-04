<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['remote_mcp_client_id', 'remote_mcp_invite_id', 'code_hash', 'redirect_uri', 'code_challenge', 'scope', 'expires_at', 'used_at'])]
class RemoteMcpAuthorizationCode extends Model
{
    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'used_at' => 'datetime'];
    }
}
