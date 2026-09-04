<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['client_id', 'name', 'redirect_uris'])]
class RemoteMcpClient extends Model
{
    protected function casts(): array
    {
        return ['redirect_uris' => 'array'];
    }
}
