<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tik_tok_shops', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tik_tok_shop_authorization_id')
                ->constrained('tik_tok_shop_authorizations')
                ->cascadeOnDelete();
            $table->string('shop_id');
            $table->string('shop_code')->nullable();
            $table->text('shop_cipher');
            $table->string('name')->nullable();
            $table->string('region', 16)->nullable();
            $table->string('seller_type', 32)->nullable();
            $table->timestamps();

            $table->unique(['tik_tok_shop_authorization_id', 'shop_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tik_tok_shops');
    }
};
