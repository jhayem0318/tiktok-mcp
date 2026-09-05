<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'username', 'email', 'password', 'access_expires_at', 'must_change_password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'access_expires_at' => 'datetime',
            'must_change_password' => 'boolean',
        ];
    }

    public function remoteMcpInvites(): HasMany
    {
        return $this->hasMany(RemoteMcpInvite::class);
    }

    public function shops(): BelongsToMany
    {
        return $this->belongsToMany(TikTokShop::class, 'tik_tok_shop_user')->withTimestamps();
    }

    public function hasActiveClientAccess(): bool
    {
        return ! $this->is_admin
            && ($this->access_expires_at === null || $this->access_expires_at->isFuture());
    }
}
