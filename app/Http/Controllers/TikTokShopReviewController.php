<?php

namespace App\Http\Controllers;

use App\Models\TikTokShop;
use App\Services\TikTokShopMcpTools;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Throwable;

class TikTokShopReviewController extends Controller
{
    private const DATASETS = ['analytics', 'orders', 'products', 'finance', 'returns', 'promotions'];

    public function __invoke(Request $request, TikTokShopMcpTools $tools): View|Response
    {
        $authenticationFailure = $this->authenticationFailure($request);

        if ($authenticationFailure !== null) {
            return $authenticationFailure;
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

    private function authenticationFailure(Request $request): ?Response
    {
        $expectedUsername = (string) config('services.tiktok.review_username');
        $expectedPassword = (string) config('services.tiktok.review_password');

        if ($expectedUsername === '' || $expectedPassword === '') {
            return response('TikTok review access is not configured.', 503);
        }

        $validUsername = hash_equals($expectedUsername, (string) $request->getUser());
        $validPassword = hash_equals($expectedPassword, (string) $request->getPassword());

        if (! $validUsername || ! $validPassword) {
            return response('Reviewer authentication required.', 401, [
                'WWW-Authenticate' => 'Basic realm="GoCommerce TikTok Analytics Review"',
            ]);
        }

        return null;
    }
}
