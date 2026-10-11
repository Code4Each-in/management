<?php

namespace App\Services\Upwork\Api;

use App\Models\UpworkApiCredential;
use App\Services\Upwork\Api\Contracts\UpworkClientInterface;
use App\Services\Upwork\UpworkOpportunityService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

/**
 * The Upwork MCP, called directly by the PMS over MCP Streamable HTTP. No AI
 * model is involved: the PMS sends the tool calls itself.
 *
 * READ-ONLY: the MCP also exposes write tools (send_message, save_job,
 * manage_proposals, respond_to_offer, update_profile, ...). Only the tools and
 * actions in TOOLS can be called; anything else is refused before a request
 * is sent, and there is no public method that takes a tool name.
 */
class McpUpworkClient implements UpworkClientInterface
{
    /** Read-only allowlist: tool => allowed actions (null = the tool takes no action). */
    const TOOLS = [
        'find_jobs' => ['search', 'get'],
        'list_accounts' => null,
    ];

    /** Search filters the MCP accepts (QueryPlanner params outside this list are dropped). */
    const SEARCH_PARAMS = [
        'query', 'title', 'limit', 'sort', 'include_full_details', 'job_type', 'experience_level', 'workload', 'duration',
        'verified_payment_only', 'rate_min', 'rate_max', 'budget_min', 'budget_max', 'proposals_min', 'proposals_max',
        'client_hires_min', 'client_hires_max', 'category', 'subcategory', 'location', 'timezone', 'skills', 'previous_clients_only',
    ];

    const CONTRACTOR_TYPES = ['INDEPENDENT' => 'Individuals only', 'INDIVIDUAL' => 'Individuals only', 'AGENCY' => 'Agencies only', 'ALL' => 'All freelancers'];

    /** @var UpworkMcpOAuth */
    private $oauth;
    /** @var string|null */
    private $session;
    /** @var int */
    private $requestId = 0;

    public function __construct(?UpworkMcpOAuth $oauth = null)
    {
        $this->oauth = $oauth ?? new UpworkMcpOAuth();
    }

    public function name(): string
    {
        return 'upwork-mcp';
    }

    public function isConfigured(): bool
    {
        $credential = UpworkApiCredential::mcp();

        return $this->oauth->client() !== null && $credential && $credential->isConnected() && (bool) $credential->tenant_id;
    }

    public function search(array $params, ?string $cursor = null): UpworkApiResult
    {
        $filters = array_intersect_key($params, array_flip(self::SEARCH_PARAMS));
        $filters['limit'] = max(1, min(10, (int) ($filters['limit'] ?? 10)));
        if ($cursor !== null) {
            $filters['cursor'] = $cursor;
        }

        $result = $this->callTool('find_jobs', ['action' => 'search', 'params' => $filters]);
        if (!$result->ok) {
            return $result;
        }

        $data = $result->rows[0];
        $rows = array_values(array_filter(array_map([self::class, 'mapSearchJob'], (array) ($data['jobs'] ?? []))));
        $more = ($data['hasMore'] ?? false) || Arr::get($data, 'pageInfo.hasNextPage');
        $next = $more ? (string) ($data['next_cursor'] ?? Arr::get($data, 'pageInfo.endCursor')) : null;

        return UpworkApiResult::rows($rows, $next ?: null, $result->durationMs);
    }

    public function job(string $jobId): UpworkApiResult
    {
        $result = $this->callTool('find_jobs', ['action' => 'get', 'params' => ['id' => UpworkOpportunityService::normalizeJobId($jobId)]]);
        if (!$result->ok) {
            return $result;
        }
        if (!is_array(Arr::get($result->rows[0], 'data.marketplaceJobPosting'))) {
            return UpworkApiResult::failure('not_found', "Job {$jobId} not found or not visible.", $result->durationMs);
        }

        return UpworkApiResult::rows([self::mapJobDetail($result->rows[0])], null, $result->durationMs);
    }

    /** Upwork accounts the connected person can act for (read-only). @return array[]|null */
    public function accounts(): ?array
    {
        $result = $this->callTool('list_accounts', [], false);
        if (!$result->ok) {
            return null;
        }

        return array_values(array_map(fn($a) => [
            'org_uid' => (string) ($a['org_uid'] ?? ''),
            'name' => (string) ($a['name'] ?? ''),
            'role' => (string) ($a['role'] ?? ''),
            'role_label' => (string) ($a['role_label'] ?? ''),
        ], array_filter((array) ($result->rows[0]['accounts'] ?? []), fn($a) => !empty($a['org_uid']))));
    }

    // ---- Mapping (MCP payloads -> PMS rows) --------------------------------------------------

    /** One search result -> PMS row (stage 1). */
    public static function mapSearchJob($j): ?array
    {
        if (!is_array($j) || empty($j['id'])) {
            return null;
        }
        $jobId = UpworkOpportunityService::normalizeJobId((string) $j['id']);
        $type = strtolower((string) ($j['job_type'] ?? ''));
        $client = (array) ($j['client'] ?? []);

        return array_filter([
            'upwork_job_id' => $jobId,
            'upwork_url' => self::cleanUrl($j['url'] ?? null) ?? "https://www.upwork.com/jobs/~02{$jobId}",
            'title' => self::text($j['title'] ?? null),
            'description' => self::text($j['description'] ?? ($j['description_snippet'] ?? null)),
            'job_type' => in_array($type, ['hourly', 'fixed'], true) ? $type : null,
            'hourly_min' => self::num($j['hourly_min'] ?? null),
            'hourly_max' => self::num($j['hourly_max'] ?? null),
            'fixed_budget' => $type === 'fixed' ? self::num($j['budget'] ?? null) : null,
            'experience_level' => isset($j['experience_level']) ? strtolower((string) $j['experience_level']) : null,
            'duration' => $j['duration'] ?? null,
            'workload' => isset($j['engagement']) ? strtolower((string) $j['engagement']) : null,
            'client_country' => $client['country'] ?? null,
            'client_rating' => self::num($client['rating'] ?? null),
            'client_reviews_count' => self::num($client['total_reviews'] ?? null),
            'client_total_spent' => self::num($client['total_spent'] ?? null),
            'client_jobs_posted' => self::num($client['total_posted_jobs'] ?? null),
            'payment_verified' => isset($client['verification_status']) ? strtoupper((string) $client['verification_status']) === 'VERIFIED' : null,
            'proposals_tier' => $j['proposals_tier'] ?? null,
            'posted_at' => $j['published_date'] ?? ($j['created_date'] ?? null),
            'fetched_at' => now()->toIso8601String(),
            'raw_data' => array_filter(['source' => 'upwork-mcp', 'skills' => array_values(array_filter((array) ($j['skills'] ?? []), 'is_string')),
                'freelancers_to_hire' => $j['freelancers_to_hire'] ?? null], fn($v) => $v !== null && $v !== []),
        ], fn($v) => $v !== null && $v !== '');
    }

    /** find_jobs get -> PMS row (stage 2): Upwork's job object plus the client record. */
    public static function mapJobDetail(array $d): array
    {
        $node = (array) Arr::get($d, 'data.marketplaceJobPosting', []);
        $row = GraphQlUpworkClient::mapJobDetail($node);
        $record = (array) ($d['client_record'] ?? []);
        $hourly = (array) Arr::get($node, 'contractTerms.hourlyContractTerms', []);
        $quals = (array) ($d['preferred_qualifications'] ?? []);

        $history = [];
        foreach (['open' => 'open', 'closed' => 'closed'] as $key => $status) {
            foreach ((array) Arr::get($d, "client_work_history.{$key}", []) as $c) {
                $history[] = array_filter([
                    'title' => self::text($c['title'] ?? null), 'type' => $c['type'] ?? null, 'status' => $status,
                    'started' => $c['started'] ?? null, 'ended' => $c['ended'] ?? null,
                    'feedback_score' => $c['feedback_score'] ?? null, 'feedback_to_client_score' => $c['feedback_to_client_score'] ?? null,
                ], fn($v) => $v !== null); // never reviewer names or comments
            }
        }

        $contractor = $quals['contractor_type'] ?? Arr::get($node, 'contractorSelection.qualification.contractorType');

        return array_filter(array_merge($row, [
            'upwork_url' => self::cleanUrl($node['url'] ?? null) ?? ($row['upwork_url'] ?? null),
            'title' => self::text($row['title'] ?? null),
            'description' => self::text($row['description'] ?? null),
            'hourly_min' => self::num($hourly['hourlyBudgetMin'] ?? ($row['hourly_min'] ?? null)),
            'hourly_max' => self::num($hourly['hourlyBudgetMax'] ?? ($row['hourly_max'] ?? null)),
            'workload' => isset($hourly['engagementType']) ? strtolower((string) $hourly['engagementType']) : null,
            'contractor_type' => $contractor ? (self::CONTRACTOR_TYPES[strtoupper((string) $contractor)] ?? (string) $contractor) : null,
            'client_timezone' => Arr::get($node, 'clientCompanyPublic.timezone'),
            'client_rating' => self::num($record['feedback_score'] ?? null),
            'client_reviews_count' => self::num($record['feedback_count'] ?? null),
            'client_total_spent' => self::num($record['spend_total'] ?? null),
            'client_hire_rate' => self::num($record['hire_rate_percent'] ?? null),
            'client_jobs_posted' => self::num($record['jobs_posted'] ?? null),
            'client_jobs_hired' => self::num($record['jobs_with_hires'] ?? null),
            'client_open_jobs' => isset($record['open_jobs']) ? (int) $record['open_jobs'] : null,
            'client_work_history' => $history ?: null,
            'connects_required' => isset($d['connects_cost']) ? (int) $d['connects_cost'] : null,
            'raw_data' => array_filter(array_merge((array) ($row['raw_data'] ?? []), [
                'source' => 'upwork-mcp',
                'applied' => $d['applied'] ?? null,
                'can_apply' => $d['can_apply'] ?? null,
                'preferred_qualifications' => $quals ?: null,
                'screening_questions' => array_values(array_map([self::class, 'text'], (array) ($d['screening_questions'] ?? []))) ?: null,
            ]), fn($v) => $v !== null),
        ]), fn($v) => $v !== null && $v !== '');
    }

    /** Strip Upwork's untrusted-content wrapper; the text stays untrusted data. */
    public static function text($value): ?string
    {
        if (!is_string($value)) {
            return null;
        }
        $value = trim(preg_replace('#</?untrusted_participant_content>#', '', $value));

        return $value === '' ? null : $value;
    }

    private static function cleanUrl($url): ?string
    {
        return is_string($url) && $url !== '' ? strtok($url, '?') : null;
    }

    private static function num($v): ?float
    {
        if (is_string($v)) {
            $v = str_replace([',', '$', ' '], '', $v);
        }

        return is_numeric($v) ? (float) $v : null;
    }

    // ---- MCP transport -----------------------------------------------------------------------

    /** The only place a tool call leaves for Upwork. */
    private function callTool(string $tool, array $arguments, bool $withOrg = true): UpworkApiResult
    {
        if (!array_key_exists($tool, self::TOOLS)) {
            throw new UpworkWriteRefused("Refused: '{$tool}' is not an allowlisted read-only Upwork tool.");
        }
        $actions = self::TOOLS[$tool];
        if ($actions !== null && !in_array($arguments['action'] ?? null, $actions, true)) {
            throw new UpworkWriteRefused("Refused: {$tool} action '" . ($arguments['action'] ?? '') . "' is not read-only.");
        }
        if ($actions === null) {
            unset($arguments['action']);
        }

        $credential = UpworkApiCredential::mcp();
        if (!$this->oauth->client() || !$credential || !$credential->isConnected()) {
            return UpworkApiResult::failure('not_configured', 'Upwork MCP is not connected.');
        }
        if ($withOrg) {
            if (!$credential->tenant_id) {
                return UpworkApiResult::failure('not_configured', 'Choose the Upwork account to search as.');
            }
            $arguments = ['action' => $arguments['action'], 'org_uid' => $credential->tenant_id] + $arguments;
        }

        $started = microtime(true);
        try {
            $response = $this->rpcWithRecovery($credential, 'tools/call', ['name' => config('upwork.mcp.tool_prefix') . $tool, 'arguments' => (object) $arguments]);
        } catch (ConnectionException $e) {
            return UpworkApiResult::failure('timeout', 'Upwork MCP did not respond.', self::ms($started));
        } catch (UpworkTokenRefreshBusy $e) {
            return UpworkApiResult::failure('refresh_busy', $e->getMessage(), self::ms($started)); // transient, not an auth failure
        } catch (\RuntimeException $e) {
            return UpworkApiResult::failure('auth', $e->getMessage(), self::ms($started));
        }
        if ($response instanceof UpworkApiResult) {
            return $response;
        }

        if (isset($response['error'])) {
            $message = (string) ($response['error']['message'] ?? 'MCP error');
            return UpworkApiResult::failure(stripos($message, 'rate') !== false ? 'rate_limited' : 'invalid_response', 'Upwork MCP: ' . mb_substr($message, 0, 500), self::ms($started));
        }
        $result = (array) ($response['result'] ?? []);
        $text = implode("\n", array_map(fn($c) => (string) ($c['text'] ?? ''), array_filter((array) ($result['content'] ?? []), fn($c) => ($c['type'] ?? '') === 'text')));
        if (!empty($result['isError'])) {
            $limited = stripos($text, 'rate limit') !== false || stripos($text, 'too many') !== false;
            return UpworkApiResult::failure($limited ? 'rate_limited' : 'invalid_response', 'Upwork MCP: ' . mb_substr($text, 0, 500), self::ms($started));
        }
        $data = json_decode($text, true);
        if (!is_array($data)) {
            return UpworkApiResult::failure('invalid_response', 'Unexpected Upwork MCP response.', self::ms($started));
        }
        if (isset($data['status']) && $data['status'] !== 'ok') {
            $message = (string) ($data['error'] ?? $data['message'] ?? $data['status']);
            return UpworkApiResult::failure(stripos($message, 'rate') !== false ? 'rate_limited' : 'invalid_response', 'Upwork MCP: ' . mb_substr($message, 0, 500), self::ms($started));
        }

        return UpworkApiResult::rows([$data], null, self::ms($started));
    }

    /**
     * One JSON-RPC request with session set-up, one token refresh on 401 and one
     * re-initialization when the session expired.
     *
     * @return array|UpworkApiResult decoded JSON-RPC response, or a classified failure
     */
    private function rpcWithRecovery(UpworkApiCredential $credential, string $method, array $params)
    {
        if (!$credential->access_token || !$credential->expires_at || $credential->expires_at->lt(now()->addMinutes(2))) {
            $credential = $this->oauth->refresh($credential);
        }

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            if ($this->session === null) {
                [$status] = $this->rpc($credential, 'initialize', [
                    'protocolVersion' => config('upwork.mcp.protocol_version'),
                    'capabilities' => new \stdClass(),
                    'clientInfo' => ['name' => 'code4each-pms', 'version' => '1.0'],
                ]);
                if ($status === 401 && $attempt === 1) {
                    $credential = $this->oauth->refresh($credential);
                    continue;
                }
                if ($status !== 200) {
                    return $this->httpFailure($status);
                }
                $this->rpc($credential, 'notifications/initialized', [], true);
            }

            [$status, $json] = $this->rpc($credential, $method, $params);
            if ($status === 401 && $attempt === 1) {
                $credential = $this->oauth->refresh($credential);
                $this->session = null;
                continue;
            }
            if (($status === 404 || $status === 400) && $attempt === 1 && $this->session !== null) {
                $this->session = null; // session expired: initialize again
                continue;
            }
            if ($status !== 200) {
                return $this->httpFailure($status);
            }

            return $json ?? [];
        }

        return UpworkApiResult::failure('auth', 'Upwork MCP refused the token after a refresh.');
    }

    private function httpFailure(int $status): UpworkApiResult
    {
        if ($status === 429) {
            return UpworkApiResult::failure('rate_limited', 'Upwork MCP rate limit reached.');
        }
        if ($status === 401 || $status === 403) {
            return UpworkApiResult::failure('auth', "Upwork MCP refused the token (HTTP {$status}). Reconnect under Agent & discovery.");
        }

        return UpworkApiResult::failure($status >= 500 ? 'server_error' : 'invalid_response', "Upwork MCP error (HTTP {$status}).");
    }

    /** @return array{0: int, 1: ?array} */
    private function rpc(UpworkApiCredential $credential, string $method, array $params, bool $notification = false): array
    {
        $message = ['jsonrpc' => '2.0', 'method' => $method] + ($params ? ['params' => $params] : []);
        if (!$notification) {
            $message['id'] = ++$this->requestId;
        }
        $headers = ['Accept' => 'application/json, text/event-stream', 'MCP-Protocol-Version' => (string) config('upwork.mcp.protocol_version')];
        if ($this->session !== null) {
            $headers['Mcp-Session-Id'] = $this->session;
        }

        $response = Http::withToken((string) $credential->access_token)->withHeaders($headers)
            ->timeout((int) config('upwork.mcp.timeout_seconds', 60))
            ->withBody(json_encode($message), 'application/json')
            ->post((string) config('upwork.mcp.url'));

        if ($session = $response->header('Mcp-Session-Id')) {
            $this->session = $session;
        }
        if ($notification || $response->status() !== 200) {
            return [$response->status(), null];
        }

        return [200, self::decode($response->body(), (string) $response->header('Content-Type'))];
    }

    /** Streamable HTTP answers with plain JSON or an SSE stream of "data:" lines. */
    public static function decode(string $body, string $contentType): ?array
    {
        if (stripos($contentType, 'text/event-stream') === false) {
            $json = json_decode($body, true);
            return is_array($json) ? $json : null;
        }
        $found = null;
        foreach (preg_split('/\r?\n/', $body) as $line) {
            if (strpos($line, 'data:') === 0 && is_array($data = json_decode(trim(substr($line, 5)), true)) && array_key_exists('id', $data)) {
                $found = $data;
            }
        }

        return $found;
    }

    private static function ms(float $started): int
    {
        return (int) round((microtime(true) - $started) * 1000);
    }
}
