<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TikTokShopReviewLoginController extends Controller
{
    public function create(Request $request): View|RedirectResponse|Response
    {
        if ($this->credentialsAreMissing()) {
            return response('TikTok review access is not configured.', 503);
        }

        if ($request->session()->boolean('tiktok_review_authenticated')) {
            return redirect()->route('tiktok.review');
        }

        return view('tiktok-review-login');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:120'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        if ($this->credentialsAreMissing() || ! $this->credentialsMatch($validated['username'], $validated['password'])) {
            return back()->withErrors([
                'username' => 'The reviewer username or password is incorrect.',
            ])->onlyInput('username');
        }

        $request->session()->regenerate();
        $request->session()->put('tiktok_review_authenticated', true);

        return redirect()->route('tiktok.review');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->session()->forget('tiktok_review_authenticated');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('tiktok.review.login');
    }

    private function credentialsAreMissing(): bool
    {
        return (string) config('services.tiktok.review_username') === ''
            || (string) config('services.tiktok.review_password') === '';
    }

    private function credentialsMatch(string $username, string $password): bool
    {
        return hash_equals((string) config('services.tiktok.review_username'), $username)
            && hash_equals((string) config('services.tiktok.review_password'), $password);
    }
}
