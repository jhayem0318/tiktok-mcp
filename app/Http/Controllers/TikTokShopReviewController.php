<?php

namespace App\Http\Controllers;

use App\Models\TikTokShop;
use App\Services\TikTokShopMcpTools;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Throwable;

class TikTokShopReviewController extends Controller
{
    private const DATASETS = ['analytics', 'orders', 'products', 'finance', 'returns', 'promotions'];

    public function __invoke(Request $request, TikTokShopMcpTools $tools): View|RedirectResponse|Response
    {
        if (! $request->session()->boolean('tiktok_review_authenticated')) {
            return redirect()->route('tiktok.review.login');
        }

        $validated = $request->validate([
            'dataset' => ['nullable', Rule::in(self::DATASETS)],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after:start_date'],
            'limit' => ['nullable', 'integer', 'between:1,100'],
        ]);

        $result = null;
        $error = null;

        if (isset($validated['dataset'])) {
            try {
                $result = $tools->call('tiktok_shop_'.$validated['dataset'], [
                    'start_date' => $validated['start_date'] ?? null,
                    'end_date' => $validated['end_date'] ?? null,
                    'limit' => $validated['limit'] ?? 20,
                ]);
            } catch (Throwable $exception) {
                report($exception);
                $error = 'TikTok Shop did not return data for this test. Please select another dataset or date range.';
            }
        }

        return view('tiktok-review', [
            'datasets' => self::DATASETS,
            'selectedDataset' => $validated['dataset'] ?? null,
            'shop' => TikTokShop::query()->latest('id')->first(['name', 'region']),
            'result' => $result,
            'error' => $error,
        ]);
    }
}
