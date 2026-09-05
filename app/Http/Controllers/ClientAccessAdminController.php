<?php

namespace App\Http\Controllers;

use App\Models\RemoteMcpInvite;
use App\Models\TikTokShop;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ClientAccessAdminController extends Controller
{
    public function login(Request $request): View|RedirectResponse|Response
    {
        if ($this->credentialsAreMissing()) {
            return response('Client access administration is not configured.', 503);
        }

        return (bool) $request->session()->get('client_access_admin_authenticated', false)
            ? redirect()->route('client-access.admin')
            : view('client-access-admin-login');
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $validated = $request->validate(['username' => ['required', 'string'], 'password' => ['required', 'string']]);

        if ($this->credentialsAreMissing() || ! hash_equals((string) config('services.tiktok.admin_username'), $validated['username'])
            || ! hash_equals((string) config('services.tiktok.admin_password'), $validated['password'])) {
            return back()->withErrors(['username' => 'The administrator credentials are incorrect.'])->onlyInput('username');
        }

        $request->session()->regenerate();
        $request->session()->put('client_access_admin_authenticated', true);

        return redirect()->route('client-access.admin');
    }

    public function index(Request $request): View|RedirectResponse
    {
        if (! (bool) $request->session()->get('client_access_admin_authenticated', false)) {
            return redirect()->route('client-access.admin.login');
        }

        return view('client-access-admin', [
            'shops' => TikTokShop::query()->where('name', 'not like', 'SANDBOX\_%')->orderBy('name')->get(),
            'clients' => User::query()->where('is_admin', false)->with('shops')->orderBy('name')->get(),
            'oauthInvitations' => RemoteMcpInvite::query()->with('shops')->latest()->get(),
            'credentials' => $request->session()->pull('client_access_credentials'),
        ]);
    }

    public function storeClient(Request $request): RedirectResponse
    {
        abort_unless((bool) $request->session()->get('client_access_admin_authenticated', false), 403);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'min:3', 'max:60', 'alpha_dash', Rule::unique('users', 'username')],
            'access_expires_at' => ['nullable', 'date', 'after:now'],
            'shop_ids' => ['required', 'array', 'min:1'],
            'shop_ids.*' => ['integer', Rule::exists('tik_tok_shops', 'id')],
        ]);
        $password = Str::password(18, true, true, false, false);
        $client = DB::transaction(function () use ($validated, $password): User {
            $client = User::query()->create([
                'name' => $validated['name'], 'username' => $validated['username'],
                'email' => $validated['username'].'@clients.invalid', 'password' => Hash::make($password),
                'access_expires_at' => $validated['access_expires_at'] ?? null, 'must_change_password' => true,
            ]);
            $client->shops()->sync($validated['shop_ids']);

            return $client;
        });
        $request->session()->flash('client_access_credentials', [
            'name' => $client->name, 'username' => $client->username, 'temporary_password' => $password,
        ]);

        return redirect()->route('client-access.admin');
    }

    public function resetClientPassword(Request $request, User $client): RedirectResponse
    {
        abort_unless((bool) $request->session()->get('client_access_admin_authenticated', false), 403);
        abort_if($client->is_admin, 404);

        $password = Str::password(18, true, true, false, false);
        $client->update([
            'password' => Hash::make($password),
            'must_change_password' => true,
        ]);
        $request->session()->flash('client_access_credentials', [
            'name' => $client->name,
            'username' => $client->username,
            'temporary_password' => $password,
        ]);

        return redirect()->route('client-access.admin');
    }

    public function destroyClient(Request $request, User $client): RedirectResponse
    {
        abort_unless((bool) $request->session()->get('client_access_admin_authenticated', false), 403);
        abort_if($client->is_admin, 404);

        $client->delete();

        return redirect()->route('client-access.admin')->with('client_access_notice', 'Client dashboard access was deleted.');
    }

    public function storeOauthInvitation(Request $request): RedirectResponse
    {
        abort_unless((bool) $request->session()->get('client_access_admin_authenticated', false), 403);
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'invite_expires_at' => ['nullable', 'date', 'after:now'],
            'shop_ids' => ['required', 'array', 'min:1'],
            'shop_ids.*' => ['integer', Rule::exists('tik_tok_shops', 'id')],
        ]);
        $inviteCode = Str::random(40);
        DB::transaction(function () use ($validated, $inviteCode): void {
            $invite = RemoteMcpInvite::query()->create([
                'label' => $validated['label'], 'code_hash' => Hash::make($inviteCode),
                'expires_at' => $validated['invite_expires_at'] ?? null,
            ]);
            $invite->shops()->sync($validated['shop_ids']);
        });
        $request->session()->flash('oauth_invitation_credentials', [
            'label' => $validated['label'], 'oauth_invitation_code' => $inviteCode,
        ]);

        return redirect()->route('client-access.admin');
    }

    public function revokeInvite(Request $request, RemoteMcpInvite $invite): RedirectResponse
    {
        abort_unless((bool) $request->session()->get('client_access_admin_authenticated', false), 403);
        $invite->update(['revoked_at' => now()]);

        return redirect()->route('client-access.admin');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('client-access.admin.login');
    }

    private function credentialsAreMissing(): bool
    {
        return (string) config('services.tiktok.admin_username') === '' || (string) config('services.tiktok.admin_password') === '';
    }
}
