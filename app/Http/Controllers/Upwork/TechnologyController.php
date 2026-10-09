<?php

namespace App\Http\Controllers\Upwork;

use App\Http\Controllers\Controller;
use App\Models\UpworkConfigLog;
use App\Models\UpworkOpportunity;
use App\Models\UpworkRuleset;
use App\Models\UpworkTechnology;
use App\Models\UpworkTechnologyAlias;
use App\Models\UpworkTechnologyRelation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Capability catalog. The slug is fixed once created (rules and the agent use
 * it); technologies are disabled rather than deleted.
 */
class TechnologyController extends Controller
{
    const RELATION_TYPES = ['related' => 'Related', 'uses' => 'Uses', 'parent' => 'Parent', 'alternative' => 'Alternative'];

    public function index(Request $request)
    {
        $this->authorize('viewConfig', UpworkOpportunity::class);

        $filters = $request->validate([
            'tier' => ['nullable', Rule::in(array_keys(UpworkTechnology::TIERS))],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $technologies = UpworkTechnology::withCount(['aliases', 'relations'])
            ->when($filters['tier'] ?? null, fn($q, $tier) => $q->where('tier', $tier))
            ->when($filters['search'] ?? null, fn($q, $s) => $q->where(fn($qq) => $qq->where('name', 'like', "%{$s}%")
                ->orWhereHas('aliases', fn($a) => $a->where('alias_normalized', 'like', '%' . mb_strtolower($s) . '%'))))
            ->orderBy('name')->get();

        return view('upwork.config.technologies.index', compact('technologies', 'filters'));
    }

    public function create()
    {
        $this->authorize('manageConfig', UpworkOpportunity::class);

        return $this->form(new UpworkTechnology(['tier' => 'adjacent', 'enabled' => true, 'search_weight' => 1, 'scoring_weight' => 1]));
    }

    public function store(Request $request)
    {
        $this->authorize('manageConfig', UpworkOpportunity::class);

        $data = $this->validated($request);
        $technology = DB::transaction(function () use ($data) {
            $technology = UpworkTechnology::create($data['technology']);
            $this->syncAliases($technology, $data['aliases']);
            $this->syncRelations($technology, $data['relations']);

            return $technology;
        });
        UpworkConfigLog::record($request->user()->id, 'technology', $technology->id, $technology->name, 'created', ['tier' => [null, $technology->tier]]);

        return redirect()->route('upwork.config.technologies.edit', $technology)->with('success', "\"{$technology->name}\" added to the catalog.");
    }

    public function edit(UpworkTechnology $technology)
    {
        $this->authorize('viewConfig', UpworkOpportunity::class);

        return $this->form($technology);
    }

    public function update(Request $request, UpworkTechnology $technology)
    {
        $this->authorize('manageConfig', UpworkOpportunity::class);

        $data = $this->validated($request, $technology);
        $changes = DB::transaction(function () use ($technology, $data) {
            $technology->fill($data['technology']);
            $changes = UpworkConfigLog::diff($technology);
            $technology->save();

            $beforeAliases = $technology->aliases()->pluck('alias')->sort()->values()->all();
            $this->syncAliases($technology, $data['aliases']);
            $afterAliases = $technology->aliases()->pluck('alias')->sort()->values()->all();
            if ($beforeAliases != $afterAliases) {
                $changes['aliases'] = [$beforeAliases, $afterAliases];
            }

            $beforeRelations = $this->relationSummary($technology);
            $this->syncRelations($technology, $data['relations']);
            $afterRelations = $this->relationSummary($technology);
            if ($beforeRelations != $afterRelations) {
                $changes['relations'] = [$beforeRelations, $afterRelations];
            }

            return $changes;
        });

        if ($changes) {
            UpworkConfigLog::record($request->user()->id, 'technology', $technology->id, $technology->name, 'updated', $changes);
        }

        return redirect()->route('upwork.config.technologies.edit', $technology)->with('success', $changes ? 'Technology saved.' : 'No changes.');
    }

    public function toggle(Request $request, UpworkTechnology $technology)
    {
        $this->authorize('manageConfig', UpworkOpportunity::class);

        $technology->update(['enabled' => !$technology->enabled]);
        UpworkConfigLog::record($request->user()->id, 'technology', $technology->id, $technology->name, $technology->enabled ? 'enabled' : 'disabled');

        return back()->with('success', "\"{$technology->name}\" " . ($technology->enabled ? 'enabled.' : 'disabled.'));
    }

    private function form(UpworkTechnology $technology)
    {
        $others = UpworkTechnology::when($technology->exists, fn($q) => $q->where('id', '!=', $technology->id))->orderBy('name')->get(['id', 'name', 'tier']);
        $relations = $technology->exists ? $technology->relations()->with('relatedTechnology')->get() : collect();
        $usedBy = [];
        if ($technology->exists && ($ruleset = UpworkRuleset::current())) {
            $needle = '"' . $technology->slug . '"';
            $usedBy = $ruleset->rules->filter(fn($r) => str_contains(json_encode($r->conditions), $needle))->pluck('name', 'key')->all();
        }

        return view('upwork.config.technologies.form', [
            'technology' => $technology, 'others' => $others, 'relations' => $relations, 'usedBy' => $usedBy,
            'relationTypes' => self::RELATION_TYPES,
            'aliases' => $technology->exists ? $technology->aliases()->orderBy('alias')->pluck('alias')->all() : [],
        ]);
    }

    private function validated(Request $request, ?UpworkTechnology $technology = null): array
    {
        $v = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => $technology ? ['prohibited'] : ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9-]+$/', Rule::unique('upwork_technologies', 'slug')],
            'category' => ['nullable', 'string', 'max:50'],
            'tier' => ['required', Rule::in(array_keys(UpworkTechnology::TIERS))],
            'evidence' => ['nullable', 'required_if:tier,proven', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'search_weight' => ['required', 'numeric', 'min:0', 'max:10'],
            'scoring_weight' => ['required', 'numeric', 'min:0', 'max:10'],
            'enabled' => ['boolean'],
            'aliases' => ['nullable', 'array', 'max:60'],
            'aliases.*' => ['nullable', 'string', 'max:100'],
            'relations' => ['nullable', 'array', 'max:30'],
            'relations.*.related_technology_id' => ['nullable', 'integer', 'exists:upwork_technologies,id'],
            'relations.*.weight' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'relations.*.relation_type' => ['nullable', Rule::in(array_keys(self::RELATION_TYPES))],
        ], ['evidence.required_if' => 'PROVEN needs recorded evidence (portfolio, profile or codebase) so the system never claims experience Code4Each does not have.']);

        $slug = $technology ? $technology->slug : ($v['slug'] ?? null ?: Str::slug($v['name']));
        if (!$technology && UpworkTechnology::where('slug', $slug)->exists()) {
            throw ValidationException::withMessages(['name' => "A technology with slug \"{$slug}\" already exists."]);
        }

        // Aliases: one alias belongs to one technology only.
        $aliases = array_values(array_unique(array_filter(array_map(fn($a) => trim((string) $a), $v['aliases'] ?? []), fn($a) => $a !== '')));
        $aliasErrors = [];
        foreach (array_merge([$v['name']], $aliases) as $alias) {
            $owner = UpworkTechnologyAlias::where('alias_normalized', UpworkTechnologyAlias::normalize($alias))
                ->when($technology, fn($q) => $q->where('technology_id', '!=', $technology->id))->with('technology')->first();
            if ($owner) {
                $aliasErrors[] = "“{$alias}” already belongs to {$owner->technology->name}.";
            }
        }
        if ($aliasErrors) {
            throw ValidationException::withMessages(['aliases' => $aliasErrors]);
        }

        $relations = [];
        foreach ($v['relations'] ?? [] as $row) {
            $id = (int) ($row['related_technology_id'] ?? 0);
            if ($id && (!$technology || $id !== $technology->id)) {
                $relations[$id] = ['weight' => (float) ($row['weight'] ?? 0.5), 'relation_type' => $row['relation_type'] ?? 'related'];
            }
        }

        return [
            'technology' => [
                'name' => $v['name'], 'slug' => $slug, 'category' => $v['category'] ?? null, 'tier' => $v['tier'],
                'evidence' => $v['evidence'] ?? null, 'notes' => $v['notes'] ?? null,
                'search_weight' => (float) $v['search_weight'], 'scoring_weight' => (float) $v['scoring_weight'],
                'enabled' => $request->boolean('enabled'),
            ],
            'aliases' => $aliases,
            'relations' => $relations,
        ];
    }

    private function syncAliases(UpworkTechnology $technology, array $aliases): void
    {
        $keep = array_map([UpworkTechnologyAlias::class, 'normalize'], array_merge([$technology->name], $aliases));
        $technology->aliases()->whereNotIn('alias_normalized', $keep)->delete();
        foreach (array_merge([$technology->name], $aliases) as $alias) {
            $technology->addAlias($alias);
        }
    }

    private function syncRelations(UpworkTechnology $technology, array $relations): void
    {
        $technology->relations()->whereNotIn('related_technology_id', array_keys($relations) ?: [0])->delete();
        foreach ($relations as $id => $attributes) {
            $technology->relateTo(UpworkTechnology::findOrFail($id), $attributes['weight'], $attributes['relation_type']);
        }
    }

    private function relationSummary(UpworkTechnology $technology): array
    {
        return UpworkTechnologyRelation::where('technology_id', $technology->id)->orderBy('related_technology_id')->get()
            ->map(fn($r) => $r->related_technology_id . ':' . $r->relation_type . ':' . $r->weight)->all();
    }
}
