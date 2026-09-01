<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tik_tok_shop_authorizations', function (Blueprint $table): void {
            $table->dateTime('access_token_expires_at')->change();
            $table->dateTime('refresh_token_expires_at')->change();
        });
    }

    public function down(): void
    {
        Schema::table('tik_tok_shop_authorizations', function (Blueprint $table): void {
            $table->timestamp('access_token_expires_at')->change();
            $table->timestamp('refresh_token_expires_at')->change();
        });
    }
};
