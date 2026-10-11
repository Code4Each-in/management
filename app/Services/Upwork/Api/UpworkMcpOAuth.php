<?php

namespace App\Services\Upwork\Api;

use App\Models\UpworkApiCredential;
use App\Models\UpworkSetting;
use App\Services\Upwork\Notifications\AlertService;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * OAuth 2.1 for the Upwork MCP, as any MCP client does it: the PMS registers
 * itself once (dynamic client registration, public client), then a person
 * signs in and approves with PKCE. Tokens are stored encrypted under the
 * 'mcp' credential label. No API key application is needed.
 *
 * Refresh is serialized: Upwork rotates the refresh token on every refresh
 * (verified 9 Oct 2026), so two processes refreshing with the same refresh
 * token could break the connection. One process refreshes under a lock; the
 * others wait, re-read the stored credential and use the new tokens.
 *
 * Remaining risk: if a process dies after Upwork returned rotated tokens but
 * before they were saved, the stored refresh token is the superseded one and
 * the next refresh fails; the token-refresh alert then asks for a reconnect.
 * The window is kept to a single UPDATE right after the response.
 */
class UpworkMcpOAuth
{
    const LABEL = 'mcp';
    /** Assumed: Upwork does not state the refresh-token lifetime (rotation keeps the window rolling). */
    const REFRESH_TOKEN_DAYS = 14;

    const LOCK_KEY = 'upwork:mcp:token-refresh';
    /** Held at most this long: above the 30 s HTTP timeout plus the save. */
    const LOCK_SECONDS = 90;
    /** How long another process waits for a refresh in progress (setting override for tests). */
    const LOCK_WAIT_SECONDS = 45;

    /** The registered client, if it was registered for this redirect URI. */
    public function client(): ?array
    {
        $client = UpworkSetting::get('mcp.client');

        return is_array($client) && !empty($client['client_id']) && ($client['redirect_uri'] ?? null) === $this->redirectUri() ? $client : null;
    }

    public function redirectUri(): string
    {
        return route('upwork.config.mcp.callback');
    }

    /** Register the PMS as an MCP client (once per redirect URI). Returns the client id. */
    public function register(?int $userId = null): string
    {
        if ($client = $this->client()) {
            return $client['client_id'];
        }

        $response = Http::acceptJson()->asJson()->timeout(30)->post((string) config('upwork.mcp.registration_url'), [
            'client_name' => 'Code4Each PMS',
            'redirect_uris' => [$this->redirectUri()],
            'grant_types' => ['authorization_code', 'refresh_token'],
            'response_types' => ['code'],
            'token_endpoint_auth_method' => 'none',
        ]);
        if (!$response->successful() || !$response->json('client_id')) {
            throw new \RuntimeException('Upwork MCP client registration failed (HTTP ' . $response->status() . ').');
        }

        // Only the public client id is kept; the registration management token is discarded.
        UpworkSetting::set('mcp.client', [
            'client_id' => (string) $response->json('client_id'),
            'redirect_uri' => $this->redirectUri(),
            'registered_at' => now()->toIso8601String(),
        ], $userId);

        return (string) $response->json('client_id');
    }

    /** @return array{0: string, 1: string} [authorize URL, PKCE verifier] */
    public function authorizeUrl(string $state): array
    {
        $client = $this->client();
        if (!$client) {
            throw new \RuntimeException('Register the PMS with the Upwork MCP first.');
        }
        $verifier = self::b64u(random_bytes(48));

        $url = config('upwork.mcp.authorize_url') . '?' . http_build_query([
            'response_type' => 'code',
            'client_id' => $client['client_id'],
            'redirect_uri' => $this->redirectUri(),
            'state' => $state,
            'code_challenge' => self::b64u(hash('sha256', $verifier, true)),
            'code_challenge_method' => 'S256',
            'resource' => config('upwork.mcp.url'),
        ]);

        return [$url, $verifier];
    }

    public function exchangeCode(string $code, string $verifier, ?int $userId): UpworkApiCredential
    {
        $client = $this->client();
        if (!$client) {
            throw new \RuntimeException('The MCP client registration is missing.');
        }
        $token = $this->tokenRequest([
            'grant_type' => 'authorization_code', 'code' => $code, 'redirect_uri' => $this->redirectUri(),
            'client_id' => $client['client_id'], 'code_verifier' => $verifier, 'resource' => config('upwork.mcp.url'),
        ]);

        $credential = UpworkApiCredential::firstOrNew(['label' => self::LABEL]);
        $credential->connected_by = $userId;

        return $this->store($credential, $token);
    }

    /**
     * Refresh the access token, at most one process at a time.
     *
     * $credential is the copy the caller holds. If, once the lock is held, the
     * stored refresh token differs from it, another process already refreshed:
     * the stored credential is returned without another request to Upwork.
     *
     * @throws UpworkTokenRefreshBusy when another refresh holds the lock past the wait
     * @throws \RuntimeException when not connected or Upwork refuses / answers malformed
     */
    public function refresh(?UpworkApiCredential $credential = null): UpworkApiCredential
    {
        $seen = $credential ?? UpworkApiCredential::mcp();
        $client = $this->client();
        if (!$seen || !$seen->refresh_token || !$client) {
            throw new \RuntimeException('Upwork MCP is not connected.');
        }

        $lock = Cache::lock(self::LOCK_KEY, self::LOCK_SECONDS);
        try {
            $lock->block(max(0, (int) config('upwork.mcp.refresh_lock_wait_seconds', self::LOCK_WAIT_SECONDS)));
        } catch (LockTimeoutException $e) {
            throw new UpworkTokenRefreshBusy('Another process is refreshing the Upwork MCP token; try again shortly.');
        }

        try {
            $stored = UpworkApiCredential::mcp();
            if (!$stored || !$stored->refresh_token) {
                throw new \RuntimeException('Upwork MCP is not connected.');
            }
            if (!hash_equals((string) $stored->refresh_token, (string) $seen->refresh_token)) {
                return $stored; // refreshed by another process while we waited
            }

            try {
                $token = $this->tokenRequest([
                    'grant_type' => 'refresh_token', 'refresh_token' => $stored->refresh_token,
                    'client_id' => $client['client_id'], 'resource' => config('upwork.mcp.url'),
                ]);
            } catch (\RuntimeException $e) {
                // Only the error column changes; the stored tokens stay as they were.
                UpworkApiCredential::whereKey($stored->id)->update(['last_error' => mb_substr(AlertService::scrub($e->getMessage()), 0, 500), 'updated_at' => now()]);
                throw $e;
            }

            try {
                return DB::transaction(fn() => $this->store($stored, $token));
            } catch (\Throwable $e) {
                // Upwork has rotated the tokens but they could not be saved: a reconnect will be needed.
                Log::critical('[upwork] MCP token refresh succeeded at Upwork but saving the new tokens failed; reconnect the Upwork MCP.', ['error' => AlertService::scrub($e->getMessage())]);
                throw new \RuntimeException('Upwork MCP tokens were refreshed but could not be saved; reconnect under Agent & discovery.', 0, $e);
            }
        } finally {
            $lock->release();
        }
    }

    private function tokenRequest(array $form): array
    {
        try {
            $response = Http::asForm()->acceptJson()->timeout(30)->post((string) config('upwork.mcp.token_url'), $form);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new \RuntimeException('Upwork MCP token request timed out.');
        }
        $json = $response->json();
        if (!$response->successful()) {
            // Never include the body: it may echo credentials.
            $error = is_array($json) && is_string($json['error'] ?? null) ? preg_replace('/[^a-z_]/i', '', $json['error']) : 'no error code';
            throw new \RuntimeException('Upwork MCP token request failed (HTTP ' . $response->status() . ', ' . ($error ?: 'no error code') . ').');
        }
        if (($problem = self::malformed($json)) !== null) {
            throw new \RuntimeException("Upwork MCP token response was malformed ({$problem}); stored tokens were kept.");
        }

        return $json;
    }

    /** Why a token response cannot be stored, or null when it is complete. */
    public static function malformed($json): ?string
    {
        if (!is_array($json)) {
            return 'not JSON';
        }
        if (!is_string($json['access_token'] ?? null) || trim($json['access_token']) === '') {
            return 'missing access_token';
        }
        if (array_key_exists('refresh_token', $json) && (!is_string($json['refresh_token']) || trim($json['refresh_token']) === '')) {
            return 'empty refresh_token';
        }
        if (array_key_exists('expires_in', $json) && (!is_numeric($json['expires_in']) || (int) $json['expires_in'] <= 0)) {
            return 'invalid expires_in';
        }
        if (array_key_exists('token_type', $json) && strcasecmp((string) $json['token_type'], 'bearer') !== 0) {
            return 'unexpected token_type';
        }

        return null;
    }

    private function store(UpworkApiCredential $credential, array $token): UpworkApiCredential
    {
        $credential->forceFill([
            'access_token' => $token['access_token'],
            'refresh_token' => $token['refresh_token'] ?? $credential->refresh_token,
            'token_type' => $token['token_type'] ?? 'Bearer',
            'expires_at' => now()->addSeconds(max(60, (int) ($token['expires_in'] ?? 3600))),
            'refresh_expires_at' => isset($token['refresh_token']) ? now()->addDays(self::REFRESH_TOKEN_DAYS) : $credential->refresh_expires_at,
            'last_refreshed_at' => now(),
            'last_error' => null,
        ])->save();

        return $credential;
    }

    private static function b64u(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }
}
