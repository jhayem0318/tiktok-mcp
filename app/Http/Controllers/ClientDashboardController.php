<?php

namespace App\Http\Controllers;

use App\Models\TikTokShop;
use App\Models\User;
use App\Services\TikTokShopMcpTools;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Throwable;

class ClientDashboardController extends Controller
{
    public function login(Request $request): View|RedirectResponse
    {
        return $this->client($request) === null ? view('client-dashboard-login') : redirect()->route('client.dashboard');
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $validated = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        $client = User::query()->where('email', $validated['email'])->where('is_admin', false)->first();

        if ($client === null || ! $client->hasActiveClientAccess() || ! Hash::check($validated['password'], $client->password)) {
            return back()->withErrors(['email' => 'The email, password, or client access is invalid.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        $request->session()->put('client_dashboard_user_id', $client->id);

        return redirect()->route($client->must_change_password ? 'client.password.edit' : 'client.dashboard');
    }

    public function editPassword(Request $request): View|RedirectResponse
    {
        return $this->requireClient($request)->must_change_password ? view('client-password') : redirect()->route('client.dashboard');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $client = $this->requireClient($request);
        $validated = $request->validate(['password' => ['required', 'string', 'min:12', 'confirmed']]);
        $client->update(['password' => Hash::make($validated['password']), 'must_change_password' => false]);

        return redirect()->route('client.dashboard');
    }

    public function dashboard(Request $request, TikTokShopMcpTools $tools): View|RedirectResponse
    {
        $client = $this->requireClient($request);
        if ($client->must_change_password) {
            return redirect()->route('client.password.edit');
        }

        $shops = $this->assignedShops($client);
        $validated = $request->validate([
            'shop_id' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after:start_date'],
        ]);
        $shop = $shops->firstWhere('shop_id', $validated['shop_id'] ?? null) ?? $shops->first();
        $summary = null;
        $error = null;

        if ($shop !== null) {
            try {
                $summary = $tools->callForShop('tiktok_shop_orders', [
                    'start_date' => $validated['start_date'] ?? now('Asia/Manila')->subDays(7)->toDateString(),
                    'end_date' => $validated['end_date'] ?? now('Asia/Manila')->toDateString(),
                    'limit' => 1,
                ], $shop);
            } catch (Throwable $exception) {
                report($exception);
                $error = 'TikTok Shop data is temporarily unavailable. Please try again later.';
            }
        }

        return view('client-dashboard', [
            'client' => $client, 'shops' => $shops, 'selectedShop' => $shop, 'summary' => $summary, 'error' => $error,
            'startDate' => $validated['start_date'] ?? now('Asia/Manila')->subDays(7)->toDateString(),
            'endDate' => $validated['end_date'] ?? now('Asia/Manila')->toDateString(),
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('client.login');
    }

    private function client(Request $request): ?User
    {
        $id = $request->session()->get('client_dashboard_user_id');

        return is_int($id) ? User::query()->where('is_admin', false)->find($id) : null;
    }

    private function requireClient(Request $request): User
    {
        $client = $this->client($request);
        abort_unless($client?->hasActiveClientAccess(), 403, 'Client access is no longer active.');

        return $client;
    }

    /** @return Collection<int, TikTokShop> */
    private function assignedShops(User $client): Collection
    {
        return $client->remoteMcpInvites()->with('shops')->whereNull('revoked_at')
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->get()->flatMap(fn ($invite) => $invite->shops)->unique('id')->values();
    }
}
