<?php

namespace App\Services\Upwork\Ai\Qualification;

use App\Jobs\Upwork\QualifyOpportunity;
use App\Models\UpworkAiAnalysis;
use App\Models\UpworkOpportunity;
use App\Models\UpworkSetting;
use App\Services\Upwork\Ai\AiBudgetGuard;
use App\Services\Upwork\Ai\AiGateway;
use App\Services\Upwork\UpworkOpportunityService;
use Illuminate\Support\Facades\Cache;

/**
 * AI qualification of opportunities that local scoring already found
 * promising. Local scoring always runs first and always stands on its own:
 * AI problems (disabled, budget, errors) only change ai_status.
 *
 * Cost controls, in order: AI switched on -> local score >= ai.min_local_score
 * -> not hard-rejected -> same input never paid twice (input hash) -> per-job
 * daily cap -> AiGateway budget reservation (daily/monthly cost, requests, tokens).
 */
class QualificationService
{
    /** @var AiGateway|null */
    private $gateway;
    /** @var QualificationPrompt */
    private $prompt;
    /** @var array|null */
    private $settings;

    public function __construct(?AiGateway $gateway = null, ?QualificationPrompt $prompt = null)
    {
        $this->gateway = $gateway;
        $this->prompt = $prompt ?? new QualificationPrompt();
    }

    private function setting(string $key)
    {
        $this->settings = $this->settings ?? UpworkSetting::allWithDefaults();

        return $this->settings["ai.{$key}"] ?? null;
    }

    private function gateway(): AiGateway
    {
        return $this->gateway = $this->gateway ?? AiGateway::fromSettings();
    }

    // ---- Automatic path ---------------------------------------------------------------------

    /**
     * Called after full job details are stored. Queues the job for AI when it
     * qualifies; records why when it does not. Never calls AI itself.
     */
    public function queueIfEligible(UpworkOpportunity $o): bool
    {
        if (!$this->setting('enabled') || !$this->setting('auto_analyze') || $o->stage !== UpworkOpportunity::STAGE_ENRICHED) {
            return false;
        }

        if ($reason = $this->skipReason($o)) {
            if ($o->ai_status === null || $o->ai_status === UpworkOpportunity::AI_SKIPPED) {
                $this->mark($o, UpworkOpportunity::AI_SKIPPED, $reason);
            }
            return false;
        }

        $request = $this->prompt->build($o, (int) $this->setting('max_input_tokens'), (int) $this->setting('max_output_tokens'));
        $hash = QualificationPrompt::inputHash($request, (string) $this->setting('model'));
        if ($o->ai_input_hash === $hash && in_array($o->ai_status, [UpworkOpportunity::AI_ANALYZED, UpworkOpportunity::AI_QUEUED, UpworkOpportunity::AI_FAILED], true)) {
            return false; // same question already answered, waiting, or failed (a human can retry)
        }

        $blocked = $this->gateway()->status()['blocked'] ?? null;
        if ($blocked) {
            $this->mark($o, UpworkOpportunity::AI_BLOCKED, $blocked);
            return false;
        }

        $this->dispatch($o, UpworkAiAnalysis::TYPE_QUALIFY, false, null, $hash);

        return true;
    }

    /** Why an automatic analysis is not worth paying for (null = worth it). */
    public function skipReason(UpworkOpportunity $o): ?string
    {
        if ($o->isLocationRestricted()) {
            return \App\Services\Upwork\Eligibility\LocationEligibility::SKIP_REASON . '.';
        }
        if ($o->hard_filters) {
            return 'Hard-rejected by local rules.';
        }
        if ($o->status !== UpworkOpportunity::STATUS_NEW) {
            return 'Already handled by a person.';
        }
        $min = (float) $this->setting('min_local_score');
        if ($o->score === null || $o->score < $min) {
            return 'Local score ' . ($o->score === null ? 'unknown' : round($o->score)) . " is below the AI threshold ({$min}).";
        }

        return null;
    }

    /** Manual "Analyze" / "Regenerate" from the UI: queued like the automatic path. */
    public function request(UpworkOpportunity $o, int $userId, bool $force = false, string $type = UpworkAiAnalysis::TYPE_QUALIFY): string
    {
        if (!$this->setting('enabled')) {
            return 'AI is switched off (Upwork → Configuration → AI).';
        }
        if ($blocked = $this->gateway()->status()['blocked'] ?? null) {
            return $blocked;
        }
        if ($o->ai_status === UpworkOpportunity::AI_QUEUED && $o->updated_at && $o->updated_at->gt(now()->subMinutes(10))) {
            return 'Already queued.';
        }

        $this->dispatch($o, $type, $force, $userId, null);

        return 'queued';
    }

    private function dispatch(UpworkOpportunity $o, string $type, bool $force, ?int $userId, ?string $hash): void
    {
        $this->mark($o, UpworkOpportunity::AI_QUEUED, $userId ? 'Requested by a person.' : null, $hash);
        QualifyOpportunity::dispatch($o->id, $type, $force, $userId);
    }

    // ---- The analysis itself (runs in the queue) --------------------------------------------

    /**
     * Analyse one opportunity. Idempotent: an identical input that was already
     * answered is reused, never paid for twice (unless $force, i.e. Regenerate).
     *
     * @return array{status: string, message: ?string, analysis: ?UpworkAiAnalysis, reused: bool}
     */
    public function analyze(UpworkOpportunity $o, string $type = UpworkAiAnalysis::TYPE_QUALIFY, bool $force = false, ?int $userId = null): array
    {
        $out = ['status' => 'skipped', 'message' => null, 'analysis' => null, 'reused' => false];

        if (!$this->setting('enabled')) {
            $this->mark($o, UpworkOpportunity::AI_SKIPPED, 'AI is switched off.');
            return ['message' => 'AI is switched off.'] + $out;
        }

        // One worker per opportunity at a time (shared hosting may overlap workers).
        $lock = Cache::lock("upwork:ai:opportunity:{$o->id}", 300);
        if (!$lock->get()) {
            return ['status' => 'busy', 'message' => 'Another worker is analysing this job.'] + $out;
        }

        try {
            $o->refresh();
            $model = (string) $this->setting('model');
            $request = $this->buildRequest($o, $type);
            $hash = QualificationPrompt::inputHash($request, $model);

            if (!$force && ($existing = UpworkAiAnalysis::where('opportunity_id', $o->id)->where('request_type', $type)
                    ->where('input_hash', $hash)->where('status', UpworkAiAnalysis::STATUS_SUCCEEDED)->latest('id')->first())) {
                $this->useAnalysis($o, $existing);
                return ['status' => 'analyzed', 'analysis' => $existing, 'reused' => true] + $out;
            }

            $today = UpworkAiAnalysis::where('opportunity_id', $o->id)->whereIn('request_type', [UpworkAiAnalysis::TYPE_QUALIFY, UpworkAiAnalysis::TYPE_DEEP])
                ->where('created_at', '>=', (new AiBudgetGuard())->dayStart())->count();
            if ($today >= (int) $this->setting('max_analyses_per_job_per_day')) {
                $message = 'Per-job AI limit reached for today.';
                $this->mark($o, UpworkOpportunity::AI_BLOCKED, $message);
                return ['status' => 'blocked', 'message' => $message] + $out;
            }

            $result = $this->gateway()->run($request, $o->id);

            if ($result['blocked']) {
                $this->mark($o, UpworkOpportunity::AI_BLOCKED, $result['error']);
                return ['status' => 'blocked', 'message' => $result['error']] + $out;
            }

            $provider = $this->gateway()->provider()->name();
            $analysis = new UpworkAiAnalysis([
                'opportunity_id' => $o->id,
                'usage_id' => $result['usage_ids'] ? end($result['usage_ids']) : null,
                'request_type' => $type,
                'provider' => $provider,
                'model' => $model,
                'prompt_version' => $request->promptVersion,
                'input_hash' => $hash,
                'requested_by' => $userId,
            ]);

            if (!$result['ok']) {
                $analysis->fill(['status' => UpworkAiAnalysis::STATUS_FAILED, 'error_code' => $result['error_code'], 'error' => mb_substr((string) $result['error'], 0, 2000)])->save();
                $this->mark($o, UpworkOpportunity::AI_FAILED, 'AI failed (' . $result['error_code'] . '); local scoring stands.', $hash);
                $o->log('ai_failed', $userId, null, null, "AI analysis failed: {$result['error_code']}");
                return ['status' => 'failed', 'message' => $result['error'], 'analysis' => $analysis] + $out;
            }

            $data = self::verify($result['data'], $request->dynamicContext);
            $analysis->fill([
                'status' => UpworkAiAnalysis::STATUS_SUCCEEDED,
                'result' => $data,
                'confidence' => round((float) $data['confidence'], 3),
                'fit_score' => (int) $data['fit_score'],
                'recommendation' => $data['recommendation'],
            ])->save();

            $this->useAnalysis($o, $analysis);
            $o->log('ai_analyzed', $userId, null, null, sprintf('AI %s: fit %d, %s, confidence %.2f (%s)',
                $type, $data['fit_score'], strtoupper($data['recommendation']), $data['confidence'], $model));

            return ['status' => 'analyzed', 'analysis' => $analysis] + $out;
        } finally {
            $lock->release();
        }
    }

    private function buildRequest(UpworkOpportunity $o, string $type): \App\Services\Upwork\Ai\AiRequest
    {
        if ($type === UpworkAiAnalysis::TYPE_DEEP) {
            return (new DeepAnalysisPrompt())->build($o, (int) $this->setting('deep_max_input_tokens'), (int) $this->setting('deep_max_output_tokens'));
        }

        return $this->prompt->build($o, (int) $this->setting('max_input_tokens'), (int) $this->setting('max_output_tokens'));
    }

    /** Point the opportunity at an analysis and re-score, so AI signals reach the rules. */
    private function useAnalysis(UpworkOpportunity $o, UpworkAiAnalysis $analysis): void
    {
        $o->forceFill([
            'ai_status' => UpworkOpportunity::AI_ANALYZED,
            'ai_status_reason' => null,
            'ai_analysis_id' => $analysis->id,
            'ai_confidence' => $analysis->confidence,
            'ai_analyzed_at' => $analysis->created_at ?? now(),
            'ai_input_hash' => $analysis->input_hash,
        ])->save();

        app(UpworkOpportunityService::class)->rescore($o->fresh(), null, 'Re-scored with AI signals');
    }

    private function mark(UpworkOpportunity $o, string $status, ?string $reason, ?string $hash = null): void
    {
        $o->forceFill(array_filter([
            'ai_status' => $status,
            'ai_status_reason' => $reason === null ? null : mb_substr($reason, 0, 255),
            'ai_input_hash' => $hash,
        ], fn($v, $k) => $k !== 'ai_input_hash' || $v !== null, ARRAY_FILTER_USE_BOTH))->save();
    }

    // ---- Facts versus AI judgement ----------------------------------------------------------

    /**
     * A FACT must be backed by words that are really in the input. Anything the
     * model labelled FACT without a matching quote is downgraded to INFERENCE.
     */
    public static function verify(array $data, string $source): array
    {
        $haystack = self::normalize($source);
        $checked = $downgraded = 0;

        foreach (['technologies', 'claims'] as $list) {
            foreach ($data[$list] ?? [] as $i => $item) {
                if (($item['kind'] ?? null) !== 'FACT') {
                    continue;
                }
                $checked++;
                $quote = self::normalize(trim((string) ($item['evidence'] ?? ''), " \t\"'“”‘’"));
                if ($quote === '' || mb_strpos($haystack, $quote) === false) {
                    $data[$list][$i]['kind'] = 'INFERENCE';
                    $data[$list][$i]['downgraded'] = true;
                    $downgraded++;
                }
            }
        }
        $data['verification'] = ['facts_checked' => $checked, 'facts_downgraded' => $downgraded];

        return $data;
    }

    private static function normalize(string $text): string
    {
        return trim(preg_replace('/\s+/u', ' ', mb_strtolower($text)));
    }

    /**
     * Signals the scoring rules may use (ai.* fields), or null when there is no
     * usable analysis or its confidence is below ai.min_confidence.
     */
    public static function signalsFor(UpworkOpportunity $o): ?array
    {
        if (!$o->ai_analysis_id) {
            return null;
        }
        $analysis = $o->relationLoaded('aiAnalysis') ? $o->aiAnalysis : UpworkAiAnalysis::find($o->ai_analysis_id);
        if (!$analysis || $analysis->status !== UpworkAiAnalysis::STATUS_SUCCEEDED || !is_array($analysis->result)) {
            return null;
        }
        $min = (float) UpworkSetting::get('ai.min_confidence');
        if ((float) $analysis->confidence < $min) {
            return null;
        }

        $r = $analysis->result;

        return [
            'analysis_id' => $analysis->id,
            'fit_score' => (int) $r['fit_score'],
            'confidence' => (float) $r['confidence'],
            'recommendation' => $r['recommendation'],
            'long_term_potential' => $r['signals']['long_term_potential'] ?? null,
            'ai_component' => $r['signals']['ai_component'] ?? null,
            'budget_realistic' => $r['signals']['budget_realistic'] ?? null,
            'urgency' => $r['signals']['urgency'] ?? null,
            'agency_friendly' => $r['signals']['agency_friendly'] ?? null,
            'complexity' => $r['scope']['complexity'] ?? null,
            'high_risk_count' => count(array_filter($r['risks'] ?? [], fn($x) => ($x['severity'] ?? null) === 'high')),
            'estimated_hours_min' => $r['scope']['estimated_hours_min'] ?? null,
            'estimated_hours_max' => $r['scope']['estimated_hours_max'] ?? null,
            'hours_basis' => $r['scope']['estimate_basis'] ?? null,
        ];
    }
}
