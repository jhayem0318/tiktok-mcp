<?php

namespace App\Http\Controllers;

use App\Models\TikTokShop;
use App\Services\TikTokShopMcpTools;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Throwable;

class TikTokShopReviewController extends Controller
{
    private const DATASETS = ['analytics', 'orders', 'products', 'finance', 'returns', 'promotions'];

    public function __invoke(Request $request, TikTokShopMcpTools $tools): View|RedirectResponse|Response
    {
        if (! (bool) $request->session()->get('tiktok_review_authenticated', false)) {
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
        $shop = TikTokShop::query()
            ->where('name', 'like', 'SANDBOX\_%')
            ->latest('id')
            ->first()
            ?? TikTokShop::query()->latest('id')->first();

        if (isset($validated['dataset'])) {
            try {
                if ($shop === null) {
                    throw new \InvalidArgumentException('No authorized TikTok Shop is stored.');
                }

                $result = $tools->callForReview('tiktok_shop_'.$validated['dataset'], [
                    'start_date' => $validated['start_date'] ?? null,
                    'end_date' => $validated['end_date'] ?? null,
                    'limit' => $validated['limit'] ?? 20,
                ], $shop);
            } catch (Throwable $exception) {
                report($exception);
                $error = 'TikTok Shop did not return data for this test. Please select another dataset or date range.';
            }
        }

        return view('tiktok-review', [
            'datasets' => self::DATASETS,
            'selectedDataset' => $validated['dataset'] ?? null,
            'shop' => $shop,
            'result' => $result,
            'error' => $error,
            'records' => $this->records($result, $validated['dataset'] ?? null),
            'syncedAt' => $result === null ? null : now('Asia/Manila'),
        ]);
    }

    /** @return list<array<string, mixed>> */
    private function records(?array $result, ?string $dataset): array
    {
        if ($result === null || ! in_array($dataset, ['products', 'orders'], true)) {
            return [];
        }

        $records = Arr::get($result, 'data.'.$dataset, []);

        return is_array($records) ? array_values(array_filter($records, 'is_array')) : [];
    }
}
