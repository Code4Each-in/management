<?php

namespace App\Console\Commands;

use App\Models\UpworkOpportunity;
use App\Models\UpworkRuleset;
use App\Services\Upwork\Scoring\ScoringEngine;
use Illuminate\Console\Command;

/**
 * Dry-run: score a stored opportunity (or a JSON payload file) with the current
 * or a given ruleset version and print the explanation. Nothing is saved.
 */
class ExplainUpworkScore extends Command
{
    protected $signature = 'upwork:explain
        {opportunity? : Opportunity id or Upwork job id}
        {--file= : Score a JSON job payload file instead of a stored opportunity}
        {--ruleset= : Ruleset version to use (default: current published)}';

    protected $description = 'Explain how the PMS scoring engine scores an Upwork opportunity (dry run, saves nothing)';

    public function handle()
    {
        $ruleset = $this->option('ruleset')
            ? UpworkRuleset::where('version', (int) $this->option('ruleset'))->first()
            : UpworkRuleset::current();
        if (!$ruleset) {
            $this->error('No such published ruleset. Seed with: php artisan db:seed --class=UpworkIntelligenceSeeder');
            return 1;
        }

        if ($file = $this->option('file')) {
            $attributes = json_decode((string) @file_get_contents($file), true);
            if (!is_array($attributes)) {
                $this->error("Could not read JSON from {$file}.");
                return 1;
            }
            $label = $attributes['title'] ?? $file;
        } else {
            $id = (string) $this->argument('opportunity');
            $opportunity = UpworkOpportunity::where('id', $id)->orWhere('upwork_job_id', \App\Services\Upwork\UpworkOpportunityService::normalizeJobId($id))->first();
            if (!$opportunity) {
                $this->error('Opportunity not found. Pass an id, an Upwork job id, or --file=payload.json.');
                return 1;
            }
            $attributes = $opportunity->attributesToArray();
            $label = "#{$opportunity->id} {$opportunity->title}";
        }

        $result = (new ScoringEngine())->score($attributes, $ruleset->load('rules'));

        $this->line("<info>{$label}</info>");
        $this->line("Ruleset v{$ruleset->version} · {$result['scoring_version']}");

        if ($result['hard_filters']) {
            $this->line('<error> HARD REJECT </error> (no score)');
            foreach ($result['hard_filters'] as $filter) {
                $this->line("  - [{$filter['key']}] {$filter['reason']}");
            }
        } else {
            $this->line(sprintf('Score <info>%s</info> / 100 → <info>%s</info>', $result['score'], strtoupper($result['recommendation'])));
            $rows = [];
            foreach ($result['components'] as $c) {
                $rules = implode("\n", array_map(fn($r) => sprintf('%+g %s', $r['points'], $r['name']), $c['rules']));
                $rows[] = [$c['label'], "{$c['points']} / {$c['max']}", "{$c['baseline']} — {$c['baseline_detail']}", $rules ?: '—'];
            }
            $this->table(['Component', 'Points', 'Baseline', 'Rules fired'], $rows);
            $this->line('Classifications: ' . ($result['classifications'] ? implode(', ', $result['classifications']) : '—'));
            $this->line("Strategic value: {$result['strategic']['score']}/100");
            $wp = $result['outlook']['win_probability'];
            $this->line("Win probability (ESTIMATE, {$wp['model']}): ~{$wp['value']}% = " . implode(' ', array_map(fn($c) => sprintf('%+g%% %s', $c['value'], $c['label']), $wp['contributions'])) . ($wp['clamped'] ? " ({$wp['clamped']})" : ''));
            $v = $result['outlook']['value'];
            $this->line($v['contract_value'] !== null
                ? sprintf('Expected value (ESTIMATE): ~$%s of ~$%s contract value [%s]', number_format($v['expected_value']), number_format($v['contract_value']), implode('; ', $v['assumptions']))
                : "Expected value: not estimated ({$v['reason']})");
        }

        $direct = array_map(fn($t) => "{$t['name']} (" . strtoupper($t['tier']) . ')', $result['technologies']['direct']);
        $this->line('Technologies: ' . ($direct ? implode(', ', $direct) : '—'));
        $this->line("Client quality: {$result['client']['score']}/100" . ($result['client']['unknown'] ? ' (unknown: ' . implode(', ', $result['client']['unknown']) . ')' : ''));

        return 0;
    }
}
