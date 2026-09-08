<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tik_tok_ads_authorizations', function (Blueprint $table): void {
            $table->id();
            $table->string('client_id');
            $table->string('open_id')->nullable();
            $table->text('access_token');
            $table->text('refresh_token');
            $table->dateTime('access_token_expires_at');
            $table->dateTime('refresh_token_expires_at');
            $table->string('scope')->nullable();
            $table->timestamps();

            $table->index('access_token_expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tik_tok_ads_authorizations');
    }
};
