<?php

namespace App\Http\Controllers\Upwork;

use App\Http\Controllers\Controller;
use App\Models\UpworkClassification;
use App\Models\UpworkConfigLog;
use App\Models\UpworkOpportunity;
use App\Models\UpworkRule;
use App\Models\UpworkRuleset;
use App\Models\UpworkTechnology;
use App\Services\Upwork\Scoring\ConditionDescriber;
use App\Services\Upwork\Scoring\RuleConditions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Scoring configuration. Published versions are read-only; all edits happen in
 * a single draft that is published as the next version (or discarded).
 */
class RulesetController extends Controller
{
    /** Settings with their own form fields: key => [label, type, help]. Everything else is "advanced" JSON. */
    const KEY_SETTINGS = [
        'agency_mode' => ['Agency mode', 'bool', 'Hard-reject jobs that only accept individual freelancers.'],
        'min_hourly_hard' => ['Hard floor: hourly max ($)', 'number', 'Below this the job is hard-rejected.'],
        'min_fixed_hard' => ['Hard floor: fixed budget ($)', 'number', 'Below this a fixed-price job is hard-rejected.'],
        'min_hourly' => ['Minimum hourly ($)', 'number', 'Below this the "low hourly" penalty applies.'],
        'min_fixed' => ['Minimum fixed budget ($)', 'number', 'Below this the "low fixed" penalty applies.'],
        'quick_win_max_fixed' => ['Quick-win max fixed budget ($)', 'number', null],
        'strategic_min' => ['Strategic value needed for STRATEGIC', 'number', '0-100.'],
        'strategic_min_weeks' => ['Weeks needed for STRATEGIC', 'number', null],
    ];

    public function index()
    {
        $this->authorize('viewConfig', UpworkOpportunity::class);

        $current = UpworkRuleset::current();
        $draft = UpworkRuleset::draft();
        $versions = UpworkRuleset::whereNotNull('version')->with('publisher')->orderByDesc('version')->get();
        $scored = UpworkOpportunity::selectRaw('ruleset_version, count(*) as c')->whereNotNull('ruleset_version')->groupBy('ruleset_version')->pluck('c', 'ruleset_version');

        return view('upwork.config.rulesets.index', compact('current', 'draft', 'versions', 'scored'));
    }

    public function show(int $version)
    {
        $this->authorize('viewConfig', UpworkOpportunity::class);

        $ruleset = UpworkRuleset::where('version', $version)->with('rules', 'publisher')->firstOrFail();

        return view('upwork.config.rulesets.show', ['ruleset' => $ruleset, 'describer' => $this->describer($ruleset)]);
    }

    public function createDraft(Request $request)
    {
        $this->authorize('manageConfig', UpworkOpportunity::class);

        $existing = UpworkRuleset::draft();
        $draft = UpworkRuleset::draftOrCreate($request->user()->id);
        if (!$existing) {
            UpworkConfigLog::record($request->user()->id, 'ruleset', $draft->id, 'Draft from v' . optional($draft->basedOn)->version, 'created');
        }

        return redirect()->route('upwork.config.rulesets.draft.edit');
    }

    public function editDraft()
    {
        $this->authorize('manageConfig', UpworkOpportunity::class);

        $draft = UpworkRuleset::draft();
        if (!$draft) {
            return redirect()->route('upwork.config.rulesets.index')->with('error', 'There is no draft. Create one from the current version first.');
        }
        $draft->load('rules', 'basedOn');

        $advanced = array_diff_key($draft->settings ?? [], self::KEY_SETTINGS);

        return view('upwork.config.rulesets.draft', [
            'draft' => $draft,
            'describer' => $this->describer($draft),
            'keySettings' => self::KEY_SETTINGS,
            'advanced' => $advanced ? json_encode($advanced, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '',
            'errorsPreview' => $draft->validationErrors(),
        ]);
    }

    public function updateDraft(Request $request)
    {
        $this->authorize('manageConfig', UpworkOpportunity::class);
        $draft = $this->draftOrFail();

        $rules = [
            'thresholds.apply' => ['required', 'numeric', 'min:0', 'max:100'],
            'thresholds.review' => ['required', 'numeric', 'min:0', 'max:100', 'lt:thresholds.apply'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'advanced_settings' => ['nullable', 'string', 'max:20000'],
        ];
        foreach (array_keys(UpworkRuleset::COMPONENTS) as $component) {
            $rules["weights.{$component}"] = ['required', 'numeric', 'min:0', 'max:100'];
        }
        foreach (self::KEY_SETTINGS as $key => [, $type]) {
            $rules["settings.{$key}"] = $type === 'bool' ? ['boolean'] : ['nullable', 'numeric', 'min:0'];
        }
        $v = $request->validate($rules, ['thresholds.review.lt' => 'The REVIEW threshold must be below the APPLY threshold.']);

        $weights = array_map('floatval', $v['weights']);
        if (array_sum($weights) <= 0) {
            throw ValidationException::withMessages(['weights' => 'At least one weight must be greater than 0.']);
        }

        $advanced = [];
        if (trim((string) ($v['advanced_settings'] ?? '')) !== '') {
            $advanced = json_decode($v['advanced_settings'], true);
            if (!is_array($advanced) || ($advanced !== [] && array_keys($advanced) === range(0, count($advanced) - 1))) {
                throw ValidationException::withMessages(['advanced_settings' => 'Advanced settings must be a JSON object, e.g. {"strategic_weights": {"engagement": 30}}.']);
            }
        }
        $settings = $advanced;
        foreach (self::KEY_SETTINGS as $key => [, $type]) {
            if ($type === 'bool') {
                $settings[$key] = $request->boolean("settings.{$key}");
            } elseif (isset($v['settings'][$key]) && $v['settings'][$key] !== '') {
                $settings[$key] = $v['settings'][$key] + 0;
            }
        }

        $draft->fill([
            'weights' => $weights,
            'thresholds' => ['apply' => $v['thresholds']['apply'] + 0, 'review' => $v['thresholds']['review'] + 0],
            'settings' => $settings,
            'notes' => $v['notes'] ?? null,
        ]);
        $changes = UpworkConfigLog::diff($draft);
        $draft->save();
        if ($changes) {
            UpworkConfigLog::record($request->user()->id, 'ruleset', $draft->id, 'Draft', 'updated', $changes);
        }

        return redirect()->route('upwork.config.rulesets.draft.edit')->with('success', $changes ? 'Draft saved. Publish it to start scoring with it.' : 'No changes.');
    }

    public function discardDraft(Request $request)
    {
        $this->authorize('manageConfig', UpworkOpportunity::class);
        $draft = $this->draftOrFail();

        DB::transaction(function () use ($draft) {
            $draft->rules()->get()->each->delete();
            $draft->delete();
        });
        UpworkConfigLog::record($request->user()->id, 'ruleset', null, 'Draft', 'discarded');

        return redirect()->route('upwork.config.rulesets.index')->with('success', 'Draft discarded. The published version is unchanged.');
    }

    public function publish(Request $request)
    {
        $this->authorize('manageConfig', UpworkOpportunity::class);
        $draft = $this->draftOrFail()->load('rules', 'basedOn.rules');

        $summary = $this->changeSummary($draft);
        try {
            $draft->publish($request->user()->id);
        } catch (\DomainException $e) {
            return redirect()->route('upwork.config.rulesets.draft.edit')->with('error', $e->getMessage());
        }
        UpworkConfigLog::record($request->user()->id, 'ruleset', $draft->id, "v{$draft->version}", 'published', $summary);

        return redirect()->route('upwork.config.rulesets.show', $draft->version)
            ->with('success', "Published ruleset v{$draft->version}. New and rediscovered jobs are scored with it; stored scores keep their version.");
    }

    // ---- Rules ------------------------------------------------------------------------

    public function createRule(Request $request)
    {
        $this->authorize('manageConfig', UpworkOpportunity::class);
        $draft = $this->draftOrFail();

        $rule = new UpworkRule([
            'type' => in_array($request->query('type'), array_keys(UpworkRule::TYPES), true) ? $request->query('type') : UpworkRule::TYPE_SCORE,
            'enabled' => true, 'points' => 0, 'sort' => (int) $draft->rules()->max('sort') + 10,
        ]);

        return $this->ruleForm($draft, $rule);
    }

    public function storeRule(Request $request)
    {
        $this->authorize('manageConfig', UpworkOpportunity::class);
        $draft = $this->draftOrFail();

        $data = $this->validatedRule($request, $draft);
        try {
            $rule = $draft->rules()->create($data);
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages(['conditions' => $e->getMessage()]);
        }
        UpworkConfigLog::record($request->user()->id, 'rule', $rule->id, "{$rule->key} (draft)", 'created', ['rule' => [null, $data]]);

        return redirect()->route('upwork.config.rulesets.draft.edit')->with('success', "Rule \"{$rule->name}\" added to the draft.");
    }

    public function editRule(UpworkRule $rule)
    {
        $this->authorize('manageConfig', UpworkOpportunity::class);
        $draft = $this->draftOrFail();
        abort_unless($rule->ruleset_id === $draft->id, 404);

        return $this->ruleForm($draft, $rule);
    }

    public function updateRule(Request $request, UpworkRule $rule)
    {
        $this->authorize('manageConfig', UpworkOpportunity::class);
        $draft = $this->draftOrFail();
        abort_unless($rule->ruleset_id === $draft->id, 404);

        $rule->fill($this->validatedRule($request, $draft, $rule));
        $changes = UpworkConfigLog::diff($rule);
        try {
            $rule->save();
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages(['conditions' => $e->getMessage()]);
        }
        if ($changes) {
            UpworkConfigLog::record($request->user()->id, 'rule', $rule->id, "{$rule->key} (draft)", 'updated', $changes);
        }

        return redirect()->route('upwork.config.rulesets.draft.edit')->with('success', "Rule \"{$rule->name}\" saved.");
    }

    public function toggleRule(Request $request, UpworkRule $rule)
    {
        $this->authorize('manageConfig', UpworkOpportunity::class);
        abort_unless($rule->ruleset_id === $this->draftOrFail()->id, 404);

        $rule->update(['enabled' => !$rule->enabled]);
        UpworkConfigLog::record($request->user()->id, 'rule', $rule->id, "{$rule->key} (draft)", $rule->enabled ? 'enabled' : 'disabled');

        return back()->with('success', "Rule \"{$rule->name}\" " . ($rule->enabled ? 'enabled.' : 'disabled.'));
    }

    public function destroyRule(Request $request, UpworkRule $rule)
    {
        $this->authorize('manageConfig', UpworkOpportunity::class);
        abort_unless($rule->ruleset_id === $this->draftOrFail()->id, 404);

        $rule->delete();
        UpworkConfigLog::record($request->user()->id, 'rule', $rule->id, "{$rule->key} (draft)", 'deleted', ['rule' => [$rule->only(['key', 'name', 'type', 'points', 'conditions']), null]]);

        return redirect()->route('upwork.config.rulesets.draft.edit')->with('success', "Rule \"{$rule->name}\" removed from the draft.");
    }

    // ---- Helpers ----------------------------------------------------------------------

    private function ruleForm(UpworkRuleset $draft, UpworkRule $rule)
    {
        return view('upwork.config.rulesets.rule-form', [
            'draft' => $draft,
            'rule' => $rule,
            'catalog' => UpworkClassification::ordered()->get(),
            'fields' => RuleConditions::FIELDS,
            'fieldLabels' => ConditionDescriber::FIELD_LABELS,
            'operators' => [
                'Compare' => RuleConditions::COMPARISON_OPS, 'Lists' => RuleConditions::VALUE_OPS, 'Yes / no / unknown' => RuleConditions::UNARY_OPS,
                'Text' => RuleConditions::TEXT_OPS, 'Technologies (no field)' => RuleConditions::TECH_OPS,
            ],
            'technologies' => UpworkTechnology::orderBy('name')->get(['slug', 'name', 'tier']),
            'settingKeys' => array_keys($draft->settings ?? []),
            'conditionsJson' => old('conditions', $rule->conditions ? json_encode($rule->conditions, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : ''),
            'preview' => $rule->conditions && RuleConditions::isValid($rule->conditions) ? $this->describer($draft)->describe($rule->conditions) : null,
        ]);
    }

    private function validatedRule(Request $request, UpworkRuleset $draft, ?UpworkRule $rule = null): array
    {
        $v = $request->validate([
            'key' => $rule ? ['prohibited'] : ['required', 'string', 'regex:/^[a-z0-9_]{2,64}$/', Rule::unique('upwork_rules', 'key')->where('ruleset_id', $draft->id)],
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', Rule::in(array_keys(UpworkRule::TYPES))],
            'component' => ['nullable', 'required_if:type,score', Rule::in(array_keys(UpworkRuleset::COMPONENTS))],
            'points' => ['nullable', 'required_if:type,score', 'numeric', 'min:-100', 'max:100'],
            'classification' => ['nullable', 'required_if:type,classification', Rule::exists('upwork_classifications', 'code')],
            'reason' => ['nullable', 'required_if:type,hard_filter', 'string', 'max:255'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'enabled' => ['boolean'],
            'conditions' => ['required', 'string', 'max:20000'],
        ], ['key.regex' => 'Key: 2-64 characters of a-z, 0-9 and _ (it identifies the rule across versions).']);

        $conditions = json_decode($v['conditions'], true);
        if (!is_array($conditions)) {
            throw ValidationException::withMessages(['conditions' => 'Conditions must be valid JSON (' . json_last_error_msg() . ').']);
        }
        if ($errors = RuleConditions::validate($conditions)) {
            throw ValidationException::withMessages(['conditions' => $errors]);
        }

        $type = $v['type'];

        return array_filter([
            'key' => $rule ? null : $v['key'],
            'name' => $v['name'],
            'type' => $type,
            'component' => $type === UpworkRule::TYPE_SCORE ? $v['component'] : null,
            'points' => $type === UpworkRule::TYPE_SCORE ? (float) $v['points'] : 0,
            'classification' => $type === UpworkRule::TYPE_CLASSIFICATION ? $v['classification'] : null,
            'reason' => $v['reason'] ?? null,
            'sort' => (int) ($v['sort'] ?? 0),
            'enabled' => $request->boolean('enabled'),
            'conditions' => $conditions,
        ], fn($value, $key) => !($key === 'key' && $value === null), ARRAY_FILTER_USE_BOTH);
    }

    private function draftOrFail(): UpworkRuleset
    {
        $draft = UpworkRuleset::draft();
        abort_unless($draft, 404, 'There is no scoring draft. Create one first.');

        return $draft;
    }

    private function describer(UpworkRuleset $ruleset): ConditionDescriber
    {
        return new ConditionDescriber(UpworkTechnology::pluck('name', 'slug')->all(), $ruleset->settings ?? []);
    }

    /** What changes compared to the version the draft is based on (for the audit log). */
    private function changeSummary(UpworkRuleset $draft): array
    {
        $base = $draft->basedOn;
        if (!$base) {
            return ['based_on' => [null, null]];
        }

        $changes = ['based_on' => ["v{$base->version}", 'next version']];
        foreach (['weights', 'thresholds', 'settings'] as $field) {
            if ($base->{$field} != $draft->{$field}) {
                $changes[$field] = [$base->{$field}, $draft->{$field}];
            }
        }

        $old = $base->rules->keyBy('key');
        $new = $draft->rules->keyBy('key');
        $added = $new->keys()->diff($old->keys())->values()->all();
        $removed = $old->keys()->diff($new->keys())->values()->all();
        $modified = $new->filter(function ($rule, $key) use ($old) {
            $fields = ['name', 'type', 'component', 'points', 'classification', 'conditions', 'reason', 'enabled'];
            return isset($old[$key]) && $old[$key]->only($fields) != $rule->only($fields);
        })->keys()->values()->all();

        if ($added || $removed || $modified) {
            $changes['rules'] = [null, compact('added', 'removed', 'modified')];
        }

        return $changes;
    }
}
