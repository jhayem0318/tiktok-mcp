<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_monthly_metrics', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tik_tok_shop_id')->constrained()->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->string('currency', 12)->nullable();
            $table->string('source')->default('TikTok Shop Open API');
            $table->json('order_summary');
            $table->json('finance_summary')->nullable();
            $table->boolean('orders_complete')->default(false);
            $table->boolean('finance_available')->default(false);
            $table->timestamp('synced_at');
            $table->timestamps();

            $table->unique(['tik_tok_shop_id', 'period_start'], 'shop_month_metric_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_monthly_metrics');
    }
};
