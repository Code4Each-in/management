<?php

namespace App\Http\Controllers\Upwork;

use App\Http\Controllers\Controller;
use App\Models\UpworkConfigLog;
use App\Models\UpworkOpportunity;
use App\Models\UpworkSearchProfile;
use App\Models\UpworkTechnology;
use App\Services\Upwork\Discovery\QueryPlanner;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Search profiles: what the agent searches Upwork for. Profiles are disabled,
 * never deleted, because sightings and discovery history reference them.
 */
class SearchProfileController extends Controller
{
    const LIST_FIELDS = [
        'keywords' => ['Keywords', 'Each becomes a broad Upwork search (whole posting, semantic matching).'],
        'exact_phrases' => ['Title phrases', 'Each becomes a title search: every word must appear in the job title. Keep to 1-3 words.'],
        'required_keywords' => ['Required keywords', 'After fetching: the job must mention at least one, or this profile ignores it.'],
        'optional_keywords' => ['Optional keywords', 'Related terms (documentation for reviewers and the agent).'],
        'excluded_keywords' => ['Excluded keywords', 'After fetching: jobs mentioning any of these are ignored by this profile.'],
        'excluded_phrases' => ['Excluded phrases', 'Like excluded keywords, for multi-word phrases.'],
    ];

    public function index()
    {
        $this->authorize('viewConfig', UpworkOpportunity::class);

        $profiles = UpworkSearchProfile::ordered()->withCount('technologies')->get();
        $sightings = \App\Models\UpworkOpportunitySighting::selectRaw('search_profile_id, count(*) as c')->groupBy('search_profile_id')->pluck('c', 'search_profile_id');

        return view('upwork.config.profiles.index', compact('profiles', 'sightings'));
    }

    public function create()
    {
        $this->authorize('manageConfig', UpworkOpportunity::class);

        return $this->form(new UpworkSearchProfile(['enabled' => true, 'priority' => 100, 'tier' => 'normal', 'frequency_minutes' => 120,
            'upwork_filters' => ['verified_payment_only' => true, 'sort' => 'recency', 'pages' => 1],
            'excluded_keywords' => ['junior', 'internship', 'unpaid', 'training']]));
    }

    public function store(Request $request)
    {
        $this->authorize('manageConfig', UpworkOpportunity::class);

        $data = $this->validated($request);
        $profile = UpworkSearchProfile::create($data['profile'] + ['created_by' => $request->user()->id, 'updated_by' => $request->user()->id]);
        $profile->technologies()->sync($data['technologies']);
        UpworkConfigLog::record($request->user()->id, 'search_profile', $profile->id, $profile->name, 'created');

        return redirect()->route('upwork.config.profiles.edit', $profile)->with('success', "Search profile \"{$profile->name}\" created.");
    }

    public function edit(UpworkSearchProfile $profile)
    {
        $this->authorize('viewConfig', UpworkOpportunity::class);

        return $this->form($profile);
    }

    public function update(Request $request, UpworkSearchProfile $profile)
    {
        $this->authorize('manageConfig', UpworkOpportunity::class);

        $data = $this->validated($request, $profile);
        $profile->fill($data['profile'] + ['updated_by' => $request->user()->id]);
        $changes = UpworkConfigLog::diff($profile, ['updated_at', 'updated_by']);
        $profile->save();

        $before = $profile->technologies()->pluck('upwork_technologies.id')->sort()->values()->all();
        $profile->syncTechnologies($data['technologies']);
        $after = collect($data['technologies'])->sort()->values()->all();
        if ($before != $after) {
            $changes['technologies'] = [$before, $after];
        }

        if ($changes) {
            UpworkConfigLog::record($request->user()->id, 'search_profile', $profile->id, $profile->name, 'updated', $changes);
        }

        return redirect()->route('upwork.config.profiles.edit', $profile)
            ->with('success', $changes ? "Saved. Profile is now version {$profile->fresh()->version}." : 'No changes.');
    }

    public function toggle(Request $request, UpworkSearchProfile $profile)
    {
        $this->authorize('manageConfig', UpworkOpportunity::class);

        $profile->update(['enabled' => !$profile->enabled, 'updated_by' => $request->user()->id]);
        UpworkConfigLog::record($request->user()->id, 'search_profile', $profile->id, $profile->name, $profile->enabled ? 'enabled' : 'disabled');

        return back()->with('success', "\"{$profile->name}\" " . ($profile->enabled ? 'enabled.' : 'disabled.'));
    }

    private function form(UpworkSearchProfile $profile)
    {
        $tierOrder = array_flip(array_keys(UpworkTechnology::TIERS));
        $technologies = UpworkTechnology::orderBy('name')->get()->sortBy(fn($t) => $tierOrder[$t->tier] ?? 9)->values();
        $selected = $profile->exists ? $profile->technologies()->pluck('upwork_technologies.id')->all() : [];
        $preview = $profile->exists ? (new QueryPlanner())->terms($profile) : [];

        return view('upwork.config.profiles.form', [
            'profile' => $profile, 'technologies' => $technologies, 'selected' => $selected,
            'listFields' => self::LIST_FIELDS, 'preview' => $preview,
        ]);
    }

    private function validated(Request $request, ?UpworkSearchProfile $profile = null): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:100', Rule::unique('upwork_search_profiles', 'name')->ignore($profile)],
            'description' => ['nullable', 'string', 'max:2000'],
            'enabled' => ['boolean'],
            'priority' => ['required', 'integer', 'min:1', 'max:1000'],
            'category' => ['nullable', 'string', 'max:50'],
            'frequency_minutes' => ['required', 'integer', 'min:10', 'max:1440'],
            'tier' => ['nullable', Rule::in(array_keys(UpworkSearchProfile::TIERS))],
            'daily_call_budget' => ['nullable', 'integer', 'min:0', 'max:500'],
            'max_results' => ['nullable', 'integer', 'min:1', 'max:30'],
            'search_related' => ['boolean'],
            'min_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'technologies' => ['nullable', 'array'],
            'technologies.*' => ['integer', 'exists:upwork_technologies,id'],
            'filters.job_type' => ['nullable', Rule::in(['hourly', 'fixed'])],
            'filters.experience_level' => ['nullable', Rule::in(['entry_level', 'intermediate', 'expert'])],
            'filters.verified_payment_only' => ['boolean'],
            'filters.rate_min' => ['nullable', 'numeric', 'gt:0', 'max:1000'],
            'filters.budget_min' => ['nullable', 'numeric', 'gt:0'],
            'filters.proposals_max' => ['nullable', 'integer', 'min:0', 'max:100'],
            'filters.workload' => ['nullable', Rule::in(['full_time', 'part_time', 'as_needed'])],
            'filters.sort' => ['nullable', Rule::in(['recency', 'client_total_charge', 'client_rating'])],
            'filters.pages' => ['nullable', 'integer', 'min:1', 'max:' . QueryPlanner::MAX_PAGES],
        ];
        foreach (array_keys(self::LIST_FIELDS) as $field) {
            $rules[$field] = ['nullable', 'array', 'max:50'];
            $rules["{$field}.*"] = ['nullable', 'string', 'max:100'];
        }
        $v = $request->validate($rules);

        if (empty(array_filter((array) ($v['keywords'] ?? []))) && empty(array_filter((array) ($v['exact_phrases'] ?? []))) && empty($v['technologies'])) {
            throw \Illuminate\Validation\ValidationException::withMessages(['keywords' => 'Add at least one keyword, title phrase or related technology, or the profile searches for nothing.']);
        }

        $filters = array_filter((array) ($v['filters'] ?? []), fn($x) => $x !== null && $x !== '');
        $filters['verified_payment_only'] = $request->boolean('filters.verified_payment_only');
        foreach (['rate_min', 'budget_min'] as $n) {
            if (isset($filters[$n])) {
                $filters[$n] = (float) $filters[$n];
            }
        }
        foreach (['proposals_max', 'pages'] as $n) {
            if (isset($filters[$n])) {
                $filters[$n] = (int) $filters[$n];
            }
        }

        $profile = [
            'name' => $v['name'], 'description' => $v['description'] ?? null, 'enabled' => $request->boolean('enabled'),
            'priority' => (int) $v['priority'], 'category' => $v['category'] ?? null, 'frequency_minutes' => (int) $v['frequency_minutes'],
            'min_score' => $v['min_score'] ?? null, 'notes' => $v['notes'] ?? null, 'upwork_filters' => $filters,
            'tier' => $v['tier'] ?? ($profile->tier ?? 'normal'),
            'daily_call_budget' => (int) ($v['daily_call_budget'] ?? ($profile->daily_call_budget ?? 30)),
            'max_results' => (int) ($v['max_results'] ?? ($profile->max_results ?? 10)),
            'search_related' => $request->boolean('search_related'),
        ];
        foreach (array_keys(self::LIST_FIELDS) as $field) {
            $profile[$field] = $v[$field] ?? null;
        }

        return ['profile' => $profile, 'technologies' => array_map('intval', $v['technologies'] ?? [])];
    }
}
