<?php

namespace App\Services\Upwork\Api;

use App\Services\Upwork\Api\Contracts\UpworkClientInterface;
use App\Services\Upwork\UpworkOpportunityService;

/**
 * Development and test client: serves stored real job data (by default the
 * Test 7 fixture) and never makes a network call. Refused in production
 * (UpworkClientFactory). Search rows get shortened descriptions and no
 * contractor type, like real search results.
 */
class FixtureUpworkClient implements UpworkClientInterface
{
    /** @var array[] */
    private $jobs;
    /** @var string|null */
    private $mode;
    /** @var int */
    public $calls = 0;

    public function __construct(?array $jobs = null, ?string $mode = null)
    {
        $this->jobs = $jobs ?? self::load((string) config('upwork.api.fixture_path'));
        $this->mode = $mode;
    }

    public static function load(string $path): array
    {
        $data = is_file($path) ? json_decode((string) file_get_contents($path), true) : null;
        $jobs = is_array($data) ? ($data['jobs'] ?? $data) : [];

        return array_values(array_map(fn($j) => array_diff_key($j, array_flip(['test7', 'expect'])), array_filter($jobs, 'is_array')));
    }

    public function name(): string
    {
        return 'fixture';
    }

    public function isConfigured(): bool
    {
        return true;
    }

    public function search(array $params, ?string $cursor = null): UpworkApiResult
    {
        $this->calls++;
        if ($this->mode === 'rate_limited') {
            return UpworkApiResult::failure('rate_limited', 'Fixture: rate limited.');
        }
        if ($this->mode === 'error') {
            return UpworkApiResult::failure('server_error', 'Fixture: server error.');
        }

        $term = mb_strtolower(trim((string) ($params['title'] ?? $params['query'] ?? '')));
        $field = isset($params['title']) ? ['title'] : ['title', 'description'];
        $matches = array_values(array_filter($this->jobs, function ($job) use ($term, $field) {
            if ($term === '') {
                return true;
            }
            foreach ($field as $f) {
                if (mb_strpos(mb_strtolower((string) ($job[$f] ?? '')), $term) !== false) {
                    return true;
                }
            }
            return false;
        }));

        $limit = max(1, (int) ($params['limit'] ?? 10));
        $offset = (int) ($cursor ?? 0);
        $page = array_slice($matches, $offset, $limit);
        $rows = array_map(function ($job) {
            $row = array_diff_key($job, array_flip(['contractor_type', 'upwork_client_id', 'client_work_history', 'client_open_jobs', 'job_activity', 'interviews_count']));
            $row['description'] = mb_substr((string) ($job['description'] ?? ''), 0, 300);
            return $row;
        }, $page);

        return UpworkApiResult::rows($rows, $offset + $limit < count($matches) ? (string) ($offset + $limit) : null);
    }

    public function job(string $jobId): UpworkApiResult
    {
        $this->calls++;
        if ($this->mode === 'rate_limited') {
            return UpworkApiResult::failure('rate_limited', 'Fixture: rate limited.');
        }

        $id = UpworkOpportunityService::normalizeJobId($jobId);
        foreach ($this->jobs as $job) {
            if (UpworkOpportunityService::normalizeJobId((string) $job['upwork_job_id']) === $id) {
                return UpworkApiResult::rows([$job]);
            }
        }

        return UpworkApiResult::failure('not_found', "Fixture has no job {$jobId}.");
    }
}
