#!/usr/bin/env php
<?php
/**
 * Code4Each Upwork discovery worker (Mac mini).
 *
 *   php agent/upwork-agent.php            scheduled cycle (does nothing unless enabled & due in the PMS)
 *   php agent/upwork-agent.php --manual   one manual cycle (allowed while the agent is disabled; for testing)
 *   php agent/upwork-agent.php --check    print the resolved setup (no PMS writes, no Upwork calls)
 *
 * Upwork access is READ-ONLY: Claude may only use find_jobs (search/get) and list_accounts.
 */

namespace Code4Each\UpworkAgent;

foreach (['Logger', 'Config', 'PmsClient', 'ClaudeRunner', 'Worker'] as $class) {
    require __DIR__ . "/src/{$class}.php";
}

$args = array_slice($argv, 1);
$config = Config::load(getenv('UPWORK_AGENT_ENV') ?: __DIR__ . '/agent.env');
$log = new Logger($config->get('LOG_DIR', sys_get_temp_dir() . '/code4each-upwork-agent'), in_array('--verbose', $args, true) || in_array('--check', $args, true));

try {
    if (in_array('--check', $args, true)) {
        $claude = new ClaudeRunner($config, $log);
        $cmd = $claude->command('<prompt>', ['type' => 'object']);
        $log->info('check', [
            'pms_url' => $config->require('PMS_URL'),
            'credential_source' => $config->get('PMS_TOKEN_COMMAND') ? 'PMS_TOKEN_COMMAND' : 'PMS_TOKEN',
            'org_uid_set' => (bool) $config->get('ORG_UID'),
            'claude' => $cmd[0],
            'allowed_tools' => ClaudeRunner::ALLOWED_TOOLS,
            'disallowed_tool_count' => count(ClaudeRunner::DISALLOWED_TOOLS),
        ]);
        $pms = new PmsClient($config->require('PMS_URL'), $config->pmsToken(), $log, 0);
        $response = $pms->get('/api/upwork/agent/config');
        $log->info('check.pms', ['status' => $response['status'], 'agent_enabled' => $response['body']['data']['agent']['enabled'] ?? null,
            'profiles' => count($response['body']['data']['profiles'] ?? []), 'mode' => $response['body']['data']['upwork']['mode'] ?? null]);
        exit($response['status'] === 200 ? 0 : 1);
    }

    $pms = new PmsClient($config->require('PMS_URL'), $config->pmsToken(), $log);
    $worker = new Worker($config, $pms, new ClaudeRunner($config, $log), $log);
    exit($worker->run(in_array('--manual', $args, true) ? 'manual' : 'scheduled'));
} catch (\Throwable $e) {
    $log->error('agent.fatal', ['error' => $e->getMessage()]);
    exit(1);
}
