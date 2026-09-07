<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_monthly_metrics', function (Blueprint $table): void {
            $table->json('brand_summaries')->nullable()->after('channel_summary');
        });
    }

    public function down(): void
    {
        Schema::table('shop_monthly_metrics', function (Blueprint $table): void {
            $table->dropColumn('brand_summaries');
        });
    }
};
