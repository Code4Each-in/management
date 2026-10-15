<?php

namespace Tests\Feature\Upwork;

use App\Models\UpworkApiCredential;
use App\Models\UpworkSetting;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

/**
 * A fake Upwork MCP + OAuth server for tests. Records every tool call so tests
 * can assert that only read-only tools were sent. Responses are the sanitized
 * real MCP answers in tests/Fixtures/Upwork.
 */
trait FakesUpworkMcp
{
    /** @var array tool calls received: name, arguments, session, auth */
    protected $mcpCalls = [];
    /** @var int[] HTTP statuses for the next tools/call requests */
    protected $mcpToolStatuses = [];
    /** @var string fixture answering find_jobs get */
    protected $mcpGetFixture = 'mcp_get';
    /** @var callable|null token endpoint responder (form array => Http response) */
    protected $mcpTokenResponder;

    protected function mcpConnect(array $overrides = []): UpworkApiCredential
    {
        UpworkSetting::set('mcp.client', ['client_id' => 'pms-client', 'redirect_uri' => route('upwork.config.mcp.callback'), 'registered_at' => now()->toIso8601String()]);
        UpworkSetting::set('mcp.accounts', [['org_uid' => '1000000000000000002', 'name' => 'Code4Each', 'role' => 'FL_AGENCY', 'role_label' => 'Agency']]);

        return UpworkApiCredential::create(array_merge([
            'label' => 'mcp', 'access_token' => 'mcp-access-1', 'refresh_token' => 'mcp-refresh-1', 'tenant_id' => '1000000000000000002',
            'expires_at' => now()->addHours(12), 'refresh_expires_at' => now()->addDays(10),
        ], $overrides));
    }

    protected function mcpFixture(string $name): array
    {
        return json_decode(file_get_contents(base_path("tests/Fixtures/Upwork/{$name}.json")), true);
    }

    protected function fakeUpworkMcp(): void
    {
        Http::fake(function (Request $r) {
            if ($r->url() === 'https://www.upwork.com/api/v3/oauth2/token') {
                return $this->mcpTokenResponder
                    ? ($this->mcpTokenResponder)($r->data())
                    : Http::response(['access_token' => 'mcp-access-2', 'refresh_token' => 'mcp-refresh-2', 'token_type' => 'Bearer', 'expires_in' => 86400]);
            }
            if ($r->url() !== 'https://mcp.upwork.com/mcp') {
                return Http::response('unexpected host', 500);
            }
            $msg = json_decode($r->body(), true);
            if ($msg['method'] === 'initialize') {
                return Http::response(['jsonrpc' => '2.0', 'id' => $msg['id'], 'result' => ['serverInfo' => ['name' => 'fake']]], 200, ['Mcp-Session-Id' => 'sess-1']);
            }
            if ($msg['method'] === 'notifications/initialized') {
                return Http::response('', 202);
            }
            if ($msg['method'] !== 'tools/call') {
                return Http::response('', 400);
            }

            $this->mcpCalls[] = $msg['params'] + ['session' => $r->header('Mcp-Session-Id')[0] ?? null, 'auth' => $r->header('Authorization')[0] ?? null];
            if ($status = array_shift($this->mcpToolStatuses)) {
                return Http::response('', $status);
            }
            $action = $msg['params']['arguments']['action'] ?? null;
            $data = $action === 'get' ? $this->mcpFixture($this->mcpGetFixture) : $this->mcpFixture('mcp_search');

            return Http::response(['jsonrpc' => '2.0', 'id' => $msg['id'], 'result' => ['content' => [['type' => 'text', 'text' => json_encode($data)]]]]);
        });
    }

    /** "find_jobs:search" style names of every tool call sent. */
    protected function mcpToolNames(): array
    {
        return array_map(fn($c) => str_replace('upwork__', '', $c['name']) . (isset($c['arguments']['action']) ? ':' . $c['arguments']['action'] : ''), $this->mcpCalls);
    }
}
