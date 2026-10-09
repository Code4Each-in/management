<?php

namespace App\Console\Commands;

use App\Services\Upwork\MetricsService;
use App\Services\Upwork\RadarService;
use Illuminate\Console\Command;

/** Print pipeline cost and efficiency for a period. Read-only; makes no Upwork or AI calls. */
class UpworkMetrics extends Command
{
    protected $signature = 'upwork:metrics {--range=7d : today, 7d or 30d}';
    protected $description = 'Show Upwork discovery, AI cost and efficiency metrics (no external calls)';

    public function handle(MetricsService $metrics)
    {
        $range = array_key_exists($this->option('range'), RadarService::RANGES) ? $this->option('range') : '7d';
        [$from, $to] = RadarService::window($range);
        $m = $metrics->summary($from, $to);

        $this->line("Upwork metrics, {$from->format('d M H:i')} – {$to->format('d M H:i')}");
        $rows = [];
        foreach (['discovery', 'ai', 'outcomes', 'efficiency'] as $group) {
            foreach ($m[$group] as $key => $value) {
                $rows[] = [$group, $key, is_float($value) ? rtrim(rtrim(number_format($value, 6), '0'), '.') : ($value ?? '—')];
            }
        }
        $this->table(['Group', 'Metric', 'Value'], $rows);

        return 0;
    }
}
