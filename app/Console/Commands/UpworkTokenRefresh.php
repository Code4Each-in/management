<?php

namespace App\Console\Commands;

use App\Models\UpworkApiCredential;
use App\Services\Upwork\Api\UpworkMcpOAuth;
use App\Services\Upwork\Api\UpworkOAuth;
use App\Services\Upwork\Notifications\AlertService;
use Illuminate\Console\Command;

/** Refresh the Upwork API token daily, so the two-week refresh token never lapses. Prints no secrets. */
class UpworkTokenRefresh extends Command
{
    protected $signature = 'upwork:token-refresh';
    protected $description = 'Refresh the read-only Upwork API and Upwork MCP OAuth tokens (no job data is read)';

    public function handle(UpworkOAuth $oauth, UpworkMcpOAuth $mcp, AlertService $alerts)
    {
        $failed = false;
        foreach ([['Upwork API', UpworkApiCredential::current(), $oauth], ['Upwork MCP', UpworkApiCredential::mcp(), $mcp]] as [$label, $credential, $service]) {
            if (!$credential || !$credential->refresh_token) {
                $this->line("{$label}: not connected; nothing to refresh.");
                continue;
            }
            $alertKey = AlertService::TOKEN_REFRESH . ':' . $credential->label;
            try {
                $credential = $service->refresh($credential);
            } catch (\App\Services\Upwork\Api\UpworkTokenRefreshBusy $e) {
                $this->line("{$label}: another process is refreshing right now; skipped.");
                continue;
            } catch (\RuntimeException $e) {
                $failed = true;
                $message = AlertService::scrub($e->getMessage());
                $this->error("{$label}: refresh failed: {$message} Reconnect under Upwork → Configuration → Agent & discovery if this persists.");
                $alerts->raise($alertKey, "{$label} token refresh failed", [
                    $message,
                    'The current access token keeps working until ' . optional($credential->expires_at)->toDateTimeString() . '; reconnect before then to avoid a gap in discovery.',
                ]);
                continue;
            }
            $alerts->resolve($alertKey, "{$label} token refresh is working again");
            $this->line("{$label}: refreshed. Access token valid until " . $credential->expires_at->toDateTimeString() . '; refresh window until ' . optional($credential->refresh_expires_at)->toDateString() . '.');
        }

        return $failed ? 1 : 0;
    }
}
