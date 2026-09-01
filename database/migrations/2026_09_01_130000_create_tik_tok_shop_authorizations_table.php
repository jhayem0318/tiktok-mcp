<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tik_tok_shop_authorizations', function (Blueprint $table): void {
            $table->id();
            $table->string('app_key');
            $table->string('open_id');
            $table->string('seller_name')->nullable();
            $table->string('seller_base_region', 16)->nullable();
            $table->unsignedTinyInteger('user_type');
            $table->text('access_token');
            $table->text('refresh_token');
            $table->timestamp('access_token_expires_at');
            $table->timestamp('refresh_token_expires_at');
            $table->json('granted_scopes')->nullable();
            $table->timestamps();

            $table->unique(['app_key', 'open_id']);
            $table->index('access_token_expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tik_tok_shop_authorizations');
    }
};
