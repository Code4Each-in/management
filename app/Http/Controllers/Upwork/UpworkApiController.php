<?php

namespace App\Http\Controllers\Upwork;

use App\Http\Controllers\Controller;
use App\Models\UpworkApiCredential;
use App\Models\UpworkConfigLog;
use App\Models\UpworkOpportunity;
use App\Models\UpworkSetting;
use App\Services\Upwork\Api\McpUpworkClient;
use App\Services\Upwork\Api\UpworkMcpOAuth;
use App\Services\Upwork\Api\UpworkOAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Connect / disconnect the read-only Upwork API (OAuth 2.0) and the Upwork MCP. Tokens are stored
 * encrypted and never displayed. Connecting grants nothing beyond the
 * read-only scopes the API key was approved with.
 */
class UpworkApiController extends Controller
{
    public function connect(Request $request, UpworkOAuth $oauth)
    {
        $this->authorize('manageConfig', UpworkOpportunity::class);

        if (!$oauth->isConfigured()) {
            return redirect()->route('upwork.config.settings.edit')->with('error', 'Set UPWORK_CLIENT_ID and UPWORK_CLIENT_SECRET in .env first.');
        }

        $state = Str::random(40);
        $request->session()->put('upwork_oauth_state', $state);

        return redirect()->away($oauth->authorizeUrl($state));
    }

    public function callback(Request $request, UpworkOAuth $oauth)
    {
        $this->authorize('manageConfig', UpworkOpportunity::class);

        $expected = $request->session()->pull('upwork_oauth_state');
        if (!$expected || !hash_equals($expected, (string) $request->query('state'))) {
            return redirect()->route('upwork.config.settings.edit')->with('error', 'Upwork connection failed: invalid or expired state. Please try again.');
        }
        if (!$request->filled('code')) {
            return redirect()->route('upwork.config.settings.edit')->with('error', 'Upwork connection was cancelled.');
        }

        try {
            $oauth->exchangeCode((string) $request->query('code'), $request->user()->id);
        } catch (\RuntimeException $e) {
            return redirect()->route('upwork.config.settings.edit')->with('error', 'Upwork connection failed: ' . $e->getMessage());
        }

        UpworkConfigLog::record($request->user()->id, 'setting', null, 'Upwork API', 'connected', []);

        return redirect()->route('upwork.config.settings.edit')->with('success', 'Upwork API connected (read-only).');
    }

    public function disconnect(Request $request)
    {
        $this->authorize('manageConfig', UpworkOpportunity::class);

        UpworkApiCredential::where('label', 'default')->delete();
        UpworkConfigLog::record($request->user()->id, 'setting', null, 'Upwork API', 'disconnected', []);

        return redirect()->route('upwork.config.settings.edit')->with('success', 'Upwork API disconnected; stored tokens deleted.');
    }

    // ---- Upwork MCP (direct, no AI model) ------------------------------------------------

    public function mcpConnect(Request $request, UpworkMcpOAuth $oauth)
    {
        $this->authorize('manageConfig', UpworkOpportunity::class);

        try {
            $oauth->register($request->user()->id);
            $state = Str::random(40);
            [$url, $verifier] = $oauth->authorizeUrl($state);
        } catch (\RuntimeException $e) {
            return redirect()->route('upwork.config.settings.edit')->with('error', $e->getMessage());
        }
        $request->session()->put('upwork_mcp_oauth', ['state' => $state, 'verifier' => $verifier]);

        return redirect()->away($url);
    }

    public function mcpCallback(Request $request, UpworkMcpOAuth $oauth)
    {
        $this->authorize('manageConfig', UpworkOpportunity::class);

        return $this->finishMcpConnect($request, $oauth, $request->query('state'), $request->query('code'));
    }

    /**
     * On a public host Upwork sends the sign-in result to a loopback address
     * that does not load (UpworkMcpOAuth::redirectUri()); the person pastes
     * that address here. The code is useless without the PKCE verifier,
     * which never leaves the session.
     */
    public function mcpPaste(Request $request, UpworkMcpOAuth $oauth)
    {
        $this->authorize('manageConfig', UpworkOpportunity::class);

        $request->validate(['callback_url' => ['required', 'string', 'max:4000']]);
        parse_str((string) parse_url(trim($request->input('callback_url')), PHP_URL_QUERY), $query);
        if (!isset($query['state']) && !isset($query['error'])) {
            // Nothing usable pasted: keep the pending sign-in so the person can paste again.
            return redirect()->route('upwork.config.settings.edit')->with('error', 'Paste the full address from the browser\'s address bar after approving on Upwork (it starts with ' . UpworkMcpOAuth::LOOPBACK_REDIRECT_URI . ').');
        }

        return $this->finishMcpConnect($request, $oauth, $query['state'] ?? null, $query['code'] ?? null);
    }

    private function finishMcpConnect(Request $request, UpworkMcpOAuth $oauth, $state, $code)
    {
        $pending = $request->session()->pull('upwork_mcp_oauth');
        if (!$pending || !is_string($state) || !hash_equals((string) $pending['state'], $state)) {
            return redirect()->route('upwork.config.settings.edit')->with('error', 'Upwork MCP connection failed: invalid or expired state. Please try again.');
        }
        if (!is_string($code) || $code === '') {
            return redirect()->route('upwork.config.settings.edit')->with('error', 'Upwork MCP connection was cancelled.');
        }

        try {
            $credential = $oauth->exchangeCode($code, $pending['verifier'], $request->user()->id);
        } catch (\RuntimeException $e) {
            return redirect()->route('upwork.config.settings.edit')->with('error', 'Upwork MCP connection failed: ' . $e->getMessage());
        }

        // Read the accounts (read-only) and default to the agency.
        $accounts = (new McpUpworkClient($oauth))->accounts() ?? [];
        UpworkSetting::set('mcp.accounts', $accounts, $request->user()->id);
        $agency = collect($accounts)->firstWhere('role', 'FL_AGENCY') ?? (count($accounts) === 1 ? $accounts[0] : null);
        if ($agency && !$credential->tenant_id) {
            $credential->forceFill(['tenant_id' => $agency['org_uid']])->save();
        }

        UpworkConfigLog::record($request->user()->id, 'setting', null, 'Upwork MCP', 'connected', ['account' => [null, $agency['name'] ?? null]]);

        return redirect()->route('upwork.config.settings.edit')->with('success', 'Upwork MCP connected (read-only)' . ($agency ? ", searching as {$agency['name']}." : '. Choose the account to search as.'));
    }

    public function mcpAccount(Request $request)
    {
        $this->authorize('manageConfig', UpworkOpportunity::class);

        $accounts = collect((array) UpworkSetting::get('mcp.accounts'));
        $validated = $request->validate(['org_uid' => ['required', 'string', \Illuminate\Validation\Rule::in($accounts->pluck('org_uid')->all())]]);
        $credential = UpworkApiCredential::mcp();
        if (!$credential) {
            return redirect()->route('upwork.config.settings.edit')->with('error', 'Connect the Upwork MCP first.');
        }
        $old = $credential->tenant_id;
        $credential->forceFill(['tenant_id' => $validated['org_uid']])->save();
        UpworkConfigLog::record($request->user()->id, 'setting', null, 'Upwork MCP account', 'updated', ['org_uid' => [$old, $validated['org_uid']]]);

        return redirect()->route('upwork.config.settings.edit')->with('success', 'Searching as ' . $accounts->firstWhere('org_uid', $validated['org_uid'])['name'] . '.');
    }

    public function mcpDisconnect(Request $request)
    {
        $this->authorize('manageConfig', UpworkOpportunity::class);

        UpworkApiCredential::where('label', UpworkMcpOAuth::LABEL)->delete();
        UpworkConfigLog::record($request->user()->id, 'setting', null, 'Upwork MCP', 'disconnected', []);

        return redirect()->route('upwork.config.settings.edit')->with('success', 'Upwork MCP disconnected; stored tokens deleted.');
    }
}
