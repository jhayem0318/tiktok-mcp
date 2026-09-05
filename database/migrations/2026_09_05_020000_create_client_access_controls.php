<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_admin')->default(false)->after('password');
            $table->timestamp('access_expires_at')->nullable()->after('remember_token');
            $table->boolean('must_change_password')->default(false)->after('access_expires_at');
        });

        Schema::table('remote_mcp_invites', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        Schema::create('remote_mcp_invite_tik_tok_shop', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('remote_mcp_invite_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tik_tok_shop_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['remote_mcp_invite_id', 'tik_tok_shop_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remote_mcp_invite_tik_tok_shop');

        Schema::table('remote_mcp_invites', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['is_admin', 'access_expires_at', 'must_change_password']);
        });
    }
};
