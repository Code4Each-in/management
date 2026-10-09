<?php

namespace App\Services\Upwork\Api;

use App\Models\UpworkApiCredential;
use Illuminate\Support\Facades\Http;

/**
 * OAuth 2.0 (authorization code) for the Upwork API key. The key is
 * requested with read-only scopes only; scopes are fixed on Upwork's side
 * when the key is approved. Client id/secret live in .env; tokens are stored
 * encrypted in upwork_api_credentials. Refresh tokens expire after about two
 * weeks, so the scheduler refreshes daily.
 */
class UpworkOAuth
{
    /** Upwork refresh tokens last about two weeks. */
    const REFRESH_TOKEN_DAYS = 14;

    public function isConfigured(): bool
    {
        return (bool) config('upwork.api.client_id') && (bool) config('upwork.api.client_secret');
    }

    public function authorizeUrl(string $state): string
    {
        return config('upwork.api.authorize_url') . '?' . http_build_query([
            'response_type' => 'code',
            'client_id' => config('upwork.api.client_id'),
            'redirect_uri' => $this->redirectUri(),
            'state' => $state,
        ]);
    }

    public function redirectUri(): string
    {
        return config('upwork.api.redirect_uri') ?: route('upwork.config.api.callback');
    }

    public function exchangeCode(string $code, ?int $userId): UpworkApiCredential
    {
        $token = $this->tokenRequest(['grant_type' => 'authorization_code', 'code' => $code, 'redirect_uri' => $this->redirectUri()]);

        $credential = UpworkApiCredential::firstOrNew(['label' => 'default']);
        $credential->connected_by = $userId;
        $credential->tenant_id = $credential->tenant_id ?: config('upwork.api.tenant_id');

        return $this->store($credential, $token);
    }

    /** Refresh the access token (and with it the two-week refresh window). */
    public function refresh(?UpworkApiCredential $credential = null): UpworkApiCredential
    {
        $credential = $credential ?? UpworkApiCredential::current();
        if (!$credential || !$credential->refresh_token) {
            throw new \RuntimeException('Upwork API is not connected.');
        }

        try {
            $token = $this->tokenRequest(['grant_type' => 'refresh_token', 'refresh_token' => $credential->refresh_token]);
        } catch (\RuntimeException $e) {
            $credential->forceFill(['last_error' => mb_substr(\App\Services\Upwork\Notifications\AlertService::scrub($e->getMessage()), 0, 500)])->save();
            throw $e;
        }

        return $this->store($credential, $token);
    }

    private function tokenRequest(array $form): array
    {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('UPWORK_CLIENT_ID / UPWORK_CLIENT_SECRET are not set.');
        }

        $response = Http::asForm()->acceptJson()->timeout(30)
            ->withBasicAuth((string) config('upwork.api.client_id'), (string) config('upwork.api.client_secret'))
            ->post((string) config('upwork.api.token_url'), $form);

        if (!$response->successful() || !$response->json('access_token')) {
            // Never include the response body: it may echo credentials.
            $error = is_string($response->json('error')) ? preg_replace('/[^a-z_]/i', '', $response->json('error')) : '';
            throw new \RuntimeException('Upwork token request failed (HTTP ' . $response->status() . ', ' . ($error ?: 'no access token') . ').');
        }

        return $response->json();
    }

    private function store(UpworkApiCredential $credential, array $token): UpworkApiCredential
    {
        $credential->forceFill([
            'access_token' => $token['access_token'],
            'refresh_token' => $token['refresh_token'] ?? $credential->refresh_token,
            'token_type' => $token['token_type'] ?? 'Bearer',
            'expires_at' => now()->addSeconds(max(60, (int) ($token['expires_in'] ?? 86400))),
            'refresh_expires_at' => isset($token['refresh_token']) ? now()->addDays(self::REFRESH_TOKEN_DAYS) : $credential->refresh_expires_at,
            'last_refreshed_at' => now(),
            'last_error' => null,
        ])->save();

        return $credential;
    }
}
