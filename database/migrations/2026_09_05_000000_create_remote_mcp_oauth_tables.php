<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remote_mcp_invites', function (Blueprint $table): void {
            $table->id();
            $table->string('label');
            $table->string('code_hash');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('remote_mcp_clients', function (Blueprint $table): void {
            $table->id();
            $table->uuid('client_id')->unique();
            $table->string('name')->nullable();
            $table->json('redirect_uris');
            $table->timestamps();
        });

        Schema::create('remote_mcp_authorization_codes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('remote_mcp_client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('remote_mcp_invite_id')->constrained()->cascadeOnDelete();
            $table->string('code_hash')->unique();
            $table->string('redirect_uri', 2048);
            $table->string('code_challenge');
            $table->string('scope');
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });

        Schema::create('remote_mcp_access_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('remote_mcp_client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('remote_mcp_invite_id')->constrained()->cascadeOnDelete();
            $table->string('token_hash')->unique();
            $table->json('scopes');
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remote_mcp_access_tokens');
        Schema::dropIfExists('remote_mcp_authorization_codes');
        Schema::dropIfExists('remote_mcp_clients');
        Schema::dropIfExists('remote_mcp_invites');
    }
};
