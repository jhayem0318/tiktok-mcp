<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'is_admin')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->boolean('is_admin')->default(false)->after('password');
            });
        }

        if (! Schema::hasColumn('users', 'access_expires_at')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->timestamp('access_expires_at')->nullable()->after('remember_token');
            });
        }

        if (! Schema::hasColumn('users', 'must_change_password')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->boolean('must_change_password')->default(false)->after('access_expires_at');
            });
        }

        if (! Schema::hasColumn('remote_mcp_invites', 'user_id')) {
            Schema::table('remote_mcp_invites', function (Blueprint $table): void {
                $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
            });
        }

        Schema::dropIfExists('remote_mcp_invite_tik_tok_shop');
        Schema::create('remote_mcp_invite_tik_tok_shop', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('remote_mcp_invite_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tik_tok_shop_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['remote_mcp_invite_id', 'tik_tok_shop_id'], 'remote_mcp_invite_shop_unique');
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
