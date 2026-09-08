<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tik_tok_ads_accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tik_tok_ads_authorization_id')
                ->constrained('tik_tok_ads_authorizations')
                ->cascadeOnDelete();
            $table->string('advertiser_id');
            $table->string('name')->nullable();
            $table->string('currency', 8)->nullable();
            $table->string('timezone', 64)->nullable();
            $table->timestamps();

            $table->unique(['tik_tok_ads_authorization_id', 'advertiser_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tik_tok_ads_accounts');
    }
};
