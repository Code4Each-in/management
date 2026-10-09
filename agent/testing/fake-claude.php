#!/usr/bin/env php
<?php
/**
 * Stand-in for `claude` in dry runs: never contacts Upwork. Replays the Test 7
 * fixtures in the same JSON envelope `claude -p --output-format json` uses.
 *
 *   CLAUDE_BIN=agent/testing/fake-claude.php
 *   FAKE_CLAUDE_FIXTURES=tests/Fixtures/Upwork/test7_jobs.json   (default)
 *   FAKE_CLAUDE_MODE=ok | rate_limit | error
 */
$args = array_slice($argv, 1);
$prompt = '';
foreach ($args as $i => $arg) {
    if ($arg === '-p') {
        $prompt = $args[$i + 1] ?? '';
    }
}

$mode = getenv('FAKE_CLAUDE_MODE') ?: 'ok';
if ($mode === 'rate_limit') {
    fwrite(STDERR, "API Error: 429 Too Many Requests (rate limit)\n");
    echo json_encode(['type' => 'result', 'subtype' => 'error_during_execution', 'is_error' => true, 'result' => 'Rate limit reached (429)']);
    exit(1);
}
if ($mode === 'error') {
    echo json_encode(['type' => 'result', 'subtype' => 'error_during_execution', 'is_error' => true, 'result' => 'MCP server unavailable']);
    exit(1);
}

$fixtures = getenv('FAKE_CLAUDE_FIXTURES') ?: __DIR__ . '/../../tests/Fixtures/Upwork/test7_jobs.json';
$jobs = json_decode(file_get_contents($fixtures), true)['jobs'];
$strip = fn(array $j) => array_diff_key($j, array_flip(['test7', 'expect']));
$withUrl = function (array $j) {
    $j['upwork_url'] = 'https://www.upwork.com/jobs/~02' . $j['upwork_job_id'];
    return $j;
};

if (strpos($prompt, 'MODE: search') !== false) {
    // One page per listed search; the fixture jobs are spread across the searches.
    preg_match_all('/^(\d+)\. type=/m', $prompt, $m);
    $indexes = array_map('intval', $m[1]);
    $searchRow = function (array $j) use ($strip, $withUrl) {
        $row = $withUrl($strip($j));
        unset($row['contractor_type'], $row['job_activity'], $row['client_hire_rate'], $row['client_jobs_hired']); // detail-only fields
        return $row;
    };
    $pages = [];
    foreach ($indexes as $n => $index) {
        $slice = array_values(array_filter($jobs, fn($j, $k) => $k % max(1, count($indexes)) === $n, ARRAY_FILTER_USE_BOTH));
        $pages[] = ['index' => $index, 'page' => 1, 'status' => 'ok', 'error' => null, 'jobs' => array_map($searchRow, $slice)];
    }
    $output = ['queries' => $pages];
} else {
    preg_match('/JOB IDS: (\[.*?\])/', $prompt, $m);
    $ids = json_decode($m[1] ?? '[]', true);
    $byId = [];
    foreach ($jobs as $j) {
        $byId['02' . $j['upwork_job_id']] = $j;
        $byId[$j['upwork_job_id']] = $j;
    }
    $output = ['jobs' => array_map(function ($id) use ($byId, $strip, $withUrl) {
        $id = ltrim($id, '~');
        return isset($byId[$id])
            ? ['upwork_job_id' => $id, 'ok' => true, 'error' => null, 'payload' => $withUrl($strip($byId[$id])) + ['agent_signals' => ['scope_size' => null]]]
            : ['upwork_job_id' => $id, 'ok' => false, 'error' => 'not in fixtures', 'payload' => null];
    }, $ids)];
}

echo json_encode(['type' => 'result', 'subtype' => 'success', 'is_error' => false, 'total_cost_usd' => 0, 'result' => '', 'structured_output' => $output]);
