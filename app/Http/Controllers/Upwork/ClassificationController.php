<?php

namespace App\Http\Controllers\Upwork;

use App\Http\Controllers\Controller;
use App\Models\UpworkClassification;
use App\Models\UpworkConfigLog;
use App\Models\UpworkOpportunity;
use App\Models\UpworkRuleset;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Classification catalog. Codes are fixed after creation; entries are disabled, not deleted. */
class ClassificationController extends Controller
{
    public function index()
    {
        $this->authorize('viewConfig', UpworkOpportunity::class);

        $classifications = UpworkClassification::ordered()->get();
        $ruleset = UpworkRuleset::current();
        $ruleCounts = $ruleset ? $ruleset->rules->where('type', 'classification')->countBy('classification')->all() : [];
        $jobCounts = [];
        foreach ($classifications as $c) {
            $jobCounts[$c->code] = UpworkOpportunity::where('classifications', 'like', '%"' . $c->code . '"%')->count();
        }

        return view('upwork.config.classifications.index', compact('classifications', 'ruleCounts', 'jobCounts'));
    }

    public function create()
    {
        $this->authorize('manageConfig', UpworkOpportunity::class);

        return view('upwork.config.classifications.form', ['classification' => new UpworkClassification(['kind' => 'archetype', 'color' => '#065f46', 'enabled' => true, 'sort' => 100])]);
    }

    public function store(Request $request)
    {
        $this->authorize('manageConfig', UpworkOpportunity::class);

        $data = $this->validated($request);
        $data['code'] = strtoupper($request->validate(['code' => ['required', 'string', 'regex:/^[A-Za-z0-9_]{2,50}$/', Rule::unique('upwork_classifications', 'code')]])['code']);
        $classification = UpworkClassification::create($data);
        UpworkConfigLog::record($request->user()->id, 'classification', $classification->id, $classification->code, 'created');

        return redirect()->route('upwork.config.classifications.index')->with('success', "Classification {$classification->code} created. Add a classification rule in a scoring draft to start assigning it.");
    }

    public function edit(UpworkClassification $classification)
    {
        $this->authorize('viewConfig', UpworkOpportunity::class);

        return view('upwork.config.classifications.form', compact('classification'));
    }

    public function update(Request $request, UpworkClassification $classification)
    {
        $this->authorize('manageConfig', UpworkOpportunity::class);

        $classification->fill($this->validated($request));
        $changes = UpworkConfigLog::diff($classification);
        $classification->save();
        if ($changes) {
            UpworkConfigLog::record($request->user()->id, 'classification', $classification->id, $classification->code, 'updated', $changes);
        }

        return redirect()->route('upwork.config.classifications.index')->with('success', $changes ? "{$classification->code} saved." : 'No changes.');
    }

    private function validated(Request $request): array
    {
        $v = $request->validate([
            'label' => ['required', 'string', 'max:100'],
            'kind' => ['required', Rule::in([UpworkClassification::KIND_ARCHETYPE, UpworkClassification::KIND_CATEGORY])],
            'description' => ['nullable', 'string', 'max:1000'],
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'sort' => ['required', 'integer', 'min:0', 'max:10000'],
            'enabled' => ['boolean'],
            'surface' => ['boolean'],
        ]);

        return ['enabled' => $request->boolean('enabled'), 'surface' => $request->boolean('surface')] + $v;
    }
}
