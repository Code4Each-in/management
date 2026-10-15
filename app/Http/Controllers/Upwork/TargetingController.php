<?php

namespace App\Http\Controllers\Upwork;

use App\Http\Controllers\Controller;
use App\Models\UpworkConfigLog;
use App\Models\UpworkOpportunity;
use App\Models\UpworkRuleset;
use App\Models\UpworkSetting;
use App\Models\UpworkTechnology;
use App\Services\Upwork\Scoring\TargetProfileScorer;
use App\Services\Upwork\Targeting\TargetProfiles;
use Illuminate\Http\Request;

/**
 * Dual-track targeting: individual freelancer profiles (PMS writing personas)
 * and a read-only view of the track weights / preferences, which live in the
 * versioned ruleset (`target_profiles`) so every change is published and logged.
 * Nothing here is linked to an Upwork account.
 */
class TargetingController extends Controller
{
    public function edit()
    {
        $this->authorize('viewConfig', UpworkOpportunity::class);

        $ruleset = UpworkRuleset::current();
        $config = TargetProfileScorer::config($ruleset->settings ?? []);
        $rulesetWeights = $ruleset ? $ruleset->normalizedWeights() : UpworkRuleset::defaultWeights();

        return view('upwork.config.targeting', [
            'individuals' => array_values(TargetProfiles::individuals()),
            'agencyProfile' => trim((string) UpworkSetting::get('proposals.profile')),
            'technologies' => UpworkTechnology::where('enabled', true)->orderBy('name')->get(['slug', 'name', 'tier']),
            'ruleset' => $ruleset,
            'config' => $config,
            'weights' => [
                'agency' => ($config['weights']['agency'] ?? null) ?: $rulesetWeights,
                'individual' => ($config['weights']['individual'] ?? null) ?: TargetProfileScorer::DEFAULT_INDIVIDUAL_WEIGHTS,
            ],
            'customised' => isset(($ruleset->settings ?? [])['target_profiles']),
            'counts' => UpworkOpportunity::selectRaw('target_profile, count(*) as c')->whereNotNull('target_profile')->groupBy('target_profile')->pluck('c', 'target_profile'),
        ]);
    }

    public function update(Request $request)
    {
        $this->authorize('manageConfig', UpworkOpportunity::class);

        $v = $request->validate([
            'profiles' => ['nullable', 'array', 'max:' . (TargetProfiles::MAX_PROFILES + 1)],
            'profiles.*.key' => ['nullable', 'string', 'max:60'],
            'profiles.*.name' => ['nullable', 'string', 'max:100'],
            'profiles.*.summary' => ['nullable', 'string', 'max:4000'],
            'profiles.*.technologies' => ['nullable', 'array'],
            'profiles.*.technologies.*' => ['string', 'max:100'],
            'profiles.*.remove' => ['nullable', 'boolean'],
        ]);

        $old = array_values(TargetProfiles::individuals());
        $new = TargetProfiles::normalize($v['profiles'] ?? []);
        if ($new == $old) {
            return redirect()->route('upwork.config.targeting.edit')->with('success', 'No changes.');
        }

        UpworkSetting::set(TargetProfiles::SETTING, $new, $request->user()->id);
        UpworkConfigLog::record($request->user()->id, 'setting', null, 'Individual profiles', 'updated', [TargetProfiles::SETTING => [$old, $new]]);

        return redirect()->route('upwork.config.targeting.edit')->with('success', 'Individual profiles saved. New and re-scored jobs use them.');
    }
}
