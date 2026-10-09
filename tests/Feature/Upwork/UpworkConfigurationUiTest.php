<?php

namespace Tests\Feature\Upwork;

use App\Models\UpworkClassification;
use App\Models\UpworkConfigLog;
use App\Models\UpworkOpportunity;
use App\Models\UpworkRule;
use App\Models\UpworkRuleset;
use App\Models\UpworkSearchProfile;
use App\Models\UpworkSetting;
use App\Models\UpworkTechnology;
use App\Policies\UpworkOpportunityPolicy;
use Database\Seeders\UpworkIntelligenceSeeder;
use Illuminate\Support\Facades\DB;

/**
 * Phase 7: PMS configuration UI - access control, search profiles, technology
 * catalog, classifications, scoring drafts/rules/publishing, agent settings,
 * audit log, and the "no code changes needed" success criteria.
 */
class UpworkConfigurationUiTest extends UpworkTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        (new UpworkIntelligenceSeeder)->run();
    }

    private function admin()
    {
        return $this->superAdmin();
    }

    // ---- Access ------------------------------------------------------------------------

    public function test_viewers_can_read_but_only_managers_can_change(): void
    {
        $pages = [
            route('upwork.config.index'), route('upwork.config.settings.edit'), route('upwork.config.profiles.index'),
            route('upwork.config.profiles.edit', UpworkSearchProfile::first()), route('upwork.config.technologies.index'),
            route('upwork.config.technologies.edit', UpworkTechnology::first()), route('upwork.config.classifications.index'),
            route('upwork.config.rulesets.index'), route('upwork.config.rulesets.show', 1),
        ];

        $bde = $this->bde();
        foreach ($pages as $page) {
            $this->actingAs($bde)->get($page)->assertOk()->assertSee('Read-only');
        }
        $this->actingAs($bde)->get(route('upwork.config.profiles.create'))->assertForbidden();
        $this->actingAs($bde)->put(route('upwork.config.settings.update'), ['agent_interval_minutes' => 30])->assertForbidden();
        $this->actingAs($bde)->post(route('upwork.config.rulesets.draft.create'))->assertForbidden();
        $this->actingAs($bde)->post(route('upwork.config.profiles.toggle', UpworkSearchProfile::first()))->assertForbidden();

        $dev = $this->developer();
        $this->actingAs($dev)->get(route('upwork.config.index'))->assertForbidden();

        $this->actingAs($this->admin())->get(route('upwork.config.index'))->assertOk()->assertDontSee('Read-only');
    }

    public function test_config_module_grant_allows_editing(): void
    {
        $bde = $this->bde();
        $moduleId = DB::table('modules')->insertGetId(['page_id' => 1, 'module_name' => 'Upwork configuration', 'route_name' => UpworkOpportunityPolicy::CONFIG_MODULE_ROUTE]);
        DB::table('role_permissions')->insert(['role_id' => $bde->role_id, 'module_id' => $moduleId]);

        $this->actingAs($bde)->get(route('upwork.config.profiles.create'))->assertOk()->assertDontSee('Read-only');
    }

    public function test_sidebar_groups_upwork_pages(): void
    {
        $this->actingAs($this->bde())->get(route('upwork.opportunities.index'))
            ->assertOk()
            ->assertSee(route('upwork.clients.index'), false)
            ->assertSee(route('upwork.config.index'), false);
    }

    // ---- Search profiles (success criteria) ---------------------------------------------------

    public function test_ai_automation_and_booking_profiles_can_be_created_without_code(): void
    {
        $admin = $this->admin();
        $openai = UpworkTechnology::where('slug', 'openai')->value('id');

        $this->actingAs($admin)->post(route('upwork.config.profiles.store'), [
            'name' => 'AI Automation (custom)', 'priority' => 5, 'frequency_minutes' => 30, 'enabled' => 1, 'tier' => 'hot', 'daily_call_budget' => 40, 'max_results' => 20,
            'keywords' => ['OpenAI', 'ChatGPT', 'Claude', 'Gemini', 'AI agent', 'n8n', 'Make', 'Zapier', 'RAG', 'LLM', 'automation', ' openai '],
            'excluded_keywords' => ['data entry'],
            'technologies' => [$openai],
            'filters' => ['verified_payment_only' => 1, 'sort' => 'recency', 'pages' => 2, 'rate_min' => 25],
        ])->assertRedirect();

        $profile = UpworkSearchProfile::where('name', 'AI Automation (custom)')->first();
        $this->assertSame(['OpenAI', 'ChatGPT', 'Claude', 'Gemini', 'AI agent', 'n8n', 'Make', 'Zapier', 'RAG', 'LLM', 'automation'], $profile->keywords, 'cleaned and de-duplicated');
        $this->assertEquals(['verified_payment_only' => true, 'sort' => 'recency', 'pages' => 2, 'rate_min' => 25], $profile->upwork_filters);
        $this->assertSame([$openai], $profile->technologies()->pluck('upwork_technologies.id')->all());

        $this->actingAs($admin)->post(route('upwork.config.profiles.store'), [
            'name' => 'Booking Platforms (custom)', 'priority' => 6, 'frequency_minutes' => 60, 'enabled' => 1,
            'keywords' => ['booking engine', 'reservation', 'appointment', 'calendar', 'availability', 'scheduling', 'Stripe', 'payment gateway'],
        ])->assertRedirect();
        $this->assertNotNull(UpworkSearchProfile::where('name', 'Booking Platforms (custom)')->first());

        // The new profile is planned for the agent straight away.
        $this->app['auth']->forgetGuards();
        $token = $this->bde()->createToken('agent', ['upwork:ingest'])->plainTextToken;
        $plan = $this->postJson('/api/upwork/agent/runs', ['trigger' => 'manual'], ['Authorization' => "Bearer {$token}", 'Accept' => 'application/json'])
            ->assertStatus(201)->json('data.plan');
        $custom = collect($plan)->firstWhere('slug', 'ai-automation-custom');
        $this->assertSame('hot', $custom['tier']);
        $this->assertSame(25, (int) $custom['queries'][0]['params']['rate_min']);
        $this->assertSame(['hot', 40, 20], [$profile->fresh()->tier, $profile->fresh()->daily_call_budget, $profile->fresh()->max_results]);
    }

    public function test_profile_edits_bump_the_version_and_are_audited(): void
    {
        $admin = $this->admin();
        $profile = UpworkSearchProfile::where('slug', 'laravel-php')->first();
        $form = ['name' => $profile->name, 'priority' => $profile->priority, 'frequency_minutes' => 20, 'enabled' => 1,
            'keywords' => array_merge($profile->keywords, ['Livewire']), 'exact_phrases' => $profile->exact_phrases,
            'excluded_keywords' => $profile->excluded_keywords, 'technologies' => $profile->technologies()->pluck('upwork_technologies.id')->all(),
            'filters' => $profile->upwork_filters];

        $this->actingAs($admin)->put(route('upwork.config.profiles.update', $profile), $form)
            ->assertRedirect()->assertSessionHas('success', 'Saved. Profile is now version 2.');

        $log = UpworkConfigLog::where('entity', 'search_profile')->latest('id')->first();
        $this->assertSame('updated', $log->action);
        $this->assertSame($admin->id, $log->user_id);
        $this->assertContains('Livewire', $log->changes['keywords'][1]);

        $this->actingAs($admin)->post(route('upwork.config.profiles.toggle', $profile))->assertRedirect();
        $this->assertFalse($profile->fresh()->enabled);
        $this->assertSame('disabled', UpworkConfigLog::latest('id')->value('action'));
    }

    public function test_profile_validation(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('upwork.config.profiles.store'), ['name' => 'Empty', 'priority' => 1, 'frequency_minutes' => 20])
            ->assertSessionHasErrors('keywords');
        $this->actingAs($admin)->post(route('upwork.config.profiles.store'), ['name' => 'Aggressive', 'priority' => 1, 'frequency_minutes' => 2, 'keywords' => ['x']])
            ->assertSessionHasErrors('frequency_minutes');
        $this->actingAs($admin)->post(route('upwork.config.profiles.store'), ['name' => 'Laravel / PHP', 'priority' => 1, 'frequency_minutes' => 20, 'keywords' => ['x']])
            ->assertSessionHasErrors('name');
    }

    // ---- Technologies -------------------------------------------------------------------------

    public function test_technologies_are_managed_with_evidence_aliases_and_relations(): void
    {
        $admin = $this->admin();
        $php = UpworkTechnology::where('slug', 'php')->value('id');

        $this->actingAs($admin)->post(route('upwork.config.technologies.store'), ['name' => 'Filament', 'tier' => 'proven', 'search_weight' => 1, 'scoring_weight' => 1])
            ->assertSessionHasErrors('evidence');
        $this->actingAs($admin)->post(route('upwork.config.technologies.store'), ['name' => 'Filament', 'tier' => 'adjacent', 'search_weight' => 1, 'scoring_weight' => 1, 'aliases' => ['chatgpt']])
            ->assertSessionHasErrors('aliases');
        $this->assertStringContainsString('already belongs to OpenAI', session('errors')->first('aliases'));

        $this->actingAs($admin)->post(route('upwork.config.technologies.store'), [
            'name' => 'Filament', 'tier' => 'adjacent', 'category' => 'backend', 'search_weight' => 1, 'scoring_weight' => 1, 'enabled' => 1,
            'aliases' => ['Filament PHP', 'filamentphp'],
            'relations' => [['related_technology_id' => $php, 'weight' => 0.8, 'relation_type' => 'uses']],
        ])->assertRedirect();

        $filament = UpworkTechnology::where('slug', 'filament')->first();
        $this->assertEqualsCanonicalizing(['Filament', 'Filament PHP', 'filamentphp'], $filament->aliases()->pluck('alias')->all());
        $this->assertSame('php', $filament->relatedTechnologies()->first()->slug);
        $this->assertArrayHasKey('filament', (new \App\Services\Upwork\Scoring\TechnologyMatcher())->detect('Admin panel in FilamentPHP')['direct'], 'detected without code changes');

        $this->actingAs($admin)->put(route('upwork.config.technologies.update', $filament), ['name' => 'Filament', 'slug' => 'renamed', 'tier' => 'adjacent', 'search_weight' => 1, 'scoring_weight' => 1])
            ->assertSessionHasErrors('slug');

        $this->actingAs($admin)->put(route('upwork.config.technologies.update', $filament), [
            'name' => 'Filament', 'tier' => 'proven', 'evidence' => 'Code4Each internal admin panels', 'search_weight' => 1, 'scoring_weight' => 1, 'enabled' => 1,
            'aliases' => ['Filament PHP'],
        ])->assertRedirect();
        $filament->refresh();
        $this->assertSame('proven', $filament->tier);
        $this->assertEqualsCanonicalizing(['Filament', 'Filament PHP'], $filament->aliases()->pluck('alias')->all());
        $this->assertSame(0, $filament->relations()->count(), 'clearing the rows removes the relationship');
        $this->assertSame(['adjacent', 'proven'], UpworkConfigLog::where('entity', 'technology')->latest('id')->first()->changes['tier']);
    }

    // ---- Classifications ----------------------------------------------------------------------

    public function test_classifications_can_be_added_and_edited_but_codes_are_fixed(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('upwork.config.classifications.store'), ['code' => 'mobile_backend', 'label' => 'Mobile Backend', 'kind' => 'category', 'color' => '#123456', 'sort' => 5, 'enabled' => 1])
            ->assertRedirect();
        $c = UpworkClassification::where('code', 'MOBILE_BACKEND')->first();
        $this->assertNotNull($c);

        $this->actingAs($admin)->put(route('upwork.config.classifications.update', $c), ['code' => 'OTHER', 'label' => 'Mobile APIs', 'kind' => 'category', 'color' => '#123456', 'sort' => 5, 'enabled' => 1, 'surface' => 1])
            ->assertRedirect();
        $c->refresh();
        $this->assertSame('MOBILE_BACKEND', $c->code);
        $this->assertSame('Mobile APIs', $c->label);
        $this->assertTrue($c->surface);

        $this->actingAs($admin)->get(route('upwork.config.classifications.index'))->assertOk()->assertSee('no rule assigns it');
    }

    // ---- Scoring (success criteria) ------------------------------------------------------------

    public function test_weights_and_rules_change_from_the_pms_and_publish_as_a_new_version(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('upwork.config.rulesets.draft.create'))->assertRedirect(route('upwork.config.rulesets.draft.edit'));

        $this->actingAs($admin)->get(route('upwork.config.rulesets.draft.edit'))
            ->assertOk()->assertSee('Draft based on v1')->assertSee('mentions Laravel')->assertSee('Publish as next version');

        // Weights + thresholds + a setting.
        $draftForm = [
            'weights' => ['technical_fit' => 25, 'experience_fit' => 20, 'client_quality' => 20, 'commercial_value' => 15, 'long_term' => 10, 'ai_automation' => 15, 'competition' => 5],
            'thresholds' => ['apply' => 78, 'review' => 68],
            'settings' => ['agency_mode' => 1, 'min_hourly' => 30, 'min_hourly_hard' => 12, 'min_fixed_hard' => 150, 'min_fixed' => 500, 'quick_win_max_fixed' => 3000, 'strategic_min' => 65, 'strategic_min_weeks' => 12],
            'advanced_settings' => '{"ev_hours_per_week": {"full_time": 40}}',
            'notes' => 'More weight on AI.',
        ];
        $this->actingAs($admin)->put(route('upwork.config.rulesets.draft.update'), $draftForm)->assertRedirect();

        // A new positive rule and a new negative rule, without code changes.
        $this->actingAs($admin)->post(route('upwork.config.rulesets.rules.store'), [
            'key' => 'tech_kendo', 'name' => 'Kendo UI', 'type' => 'score', 'component' => 'experience_fit', 'points' => 6, 'enabled' => 1,
            'reason' => 'Kendo UI is portfolio-proven (360Alumni).', 'conditions' => '{"op": "has_any_tech", "value": ["kendo-ui"]}',
        ])->assertRedirect(route('upwork.config.rulesets.draft.edit'));
        $this->actingAs($admin)->post(route('upwork.config.rulesets.rules.store'), [
            'key' => 'crypto_only', 'name' => 'Crypto-only payment', 'type' => 'score', 'component' => 'client_quality', 'points' => -8, 'enabled' => 1,
            'conditions' => '{"field": "job.text", "op": "contains_any", "value": ["paid in crypto", "crypto only"]}',
        ])->assertRedirect();

        $draft = UpworkRuleset::draft();
        $this->assertSame(['apply' => 78, 'review' => 68], $draft->thresholds);
        $this->assertSame(40, $draft->settings['ev_hours_per_week']['full_time']);
        $this->assertSame(30, $draft->settings['min_hourly']);

        $this->actingAs($admin)->post(route('upwork.config.rulesets.draft.publish'))->assertRedirect(route('upwork.config.rulesets.show', 2));

        $v2 = UpworkRuleset::current();
        $this->assertSame(2, $v2->version);
        $this->assertEqualsWithDelta(25 * 100 / 110, $v2->normalizedWeights()['technical_fit'], 0.01);
        $this->assertSame(10, UpworkRuleset::where('version', 1)->first()->weights['ai_automation'], 'v1 is unchanged');

        $log = UpworkConfigLog::where('action', 'published')->latest('id')->first();
        $this->assertSame('v2', $log->label);
        $this->assertEqualsCanonicalizing(['tech_kendo', 'crypto_only'], $log->changes['rules'][1]['added']);

        // The new rules score real jobs immediately.
        $this->app['auth']->forgetGuards();
        $token = $this->bde()->createToken('agent', ['upwork:ingest'])->plainTextToken;
        $this->postJson('/api/upwork/opportunities', [
            'upwork_job_id' => '~02kendo', 'upwork_url' => 'https://www.upwork.com/jobs/~02kendo', 'title' => 'Symfony app with Kendo UI grids',
            'description' => 'Extend our Symfony app with Kendo UI grids. We are paid in crypto only.', 'job_type' => 'hourly', 'hourly_min' => 30, 'hourly_max' => 50,
            'duration' => '1 to 3 months', 'contractor_type' => 'All freelancers', 'payment_verified' => true,
        ], ['Authorization' => "Bearer {$token}", 'Accept' => 'application/json'])->assertStatus(201);

        $o = UpworkOpportunity::first();
        $this->assertSame(2, $o->ruleset_version);
        $this->assertContains('tech_kendo', array_column($o->score_explanation['components']['experience_fit']['rules'], 'key'));
        $this->assertContains('crypto_only', array_column($o->score_explanation['components']['client_quality']['rules'], 'key'));
    }

    public function test_rule_and_draft_validation(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('upwork.config.rulesets.draft.create'));

        $base = ['key' => 'bad_rule', 'name' => 'Bad', 'type' => 'score', 'component' => 'technical_fit', 'points' => 1, 'enabled' => 1];
        $this->actingAs($admin)->post(route('upwork.config.rulesets.rules.store'), $base + ['conditions' => '{not json'])->assertSessionHasErrors('conditions');
        $this->actingAs($admin)->post(route('upwork.config.rulesets.rules.store'), $base + ['conditions' => '{"field": "job.title", "op": "eval", "value": "x"}'])
            ->assertSessionHasErrors('conditions');
        $this->assertStringContainsString("unknown operator 'eval'", session('errors')->first('conditions'));
        $this->actingAs($admin)->post(route('upwork.config.rulesets.rules.store'), ['type' => 'hard_filter', 'key' => 'hf', 'name' => 'HF', 'conditions' => '{"field": "job.title", "op": "is_null"}'])
            ->assertSessionHasErrors('reason');
        $this->actingAs($admin)->post(route('upwork.config.rulesets.rules.store'), ['type' => 'classification', 'key' => 'cl', 'name' => 'CL', 'classification' => 'NOPE', 'conditions' => '{"field": "job.title", "op": "is_null"}'])
            ->assertSessionHasErrors('classification');
        $this->actingAs($admin)->post(route('upwork.config.rulesets.rules.store'), array_merge($base, ['key' => 'tech_laravel', 'conditions' => '{"field": "job.title", "op": "is_null"}']))
            ->assertSessionHasErrors('key');

        $weights = array_fill_keys(array_keys(UpworkRuleset::COMPONENTS), 10);
        $this->actingAs($admin)->put(route('upwork.config.rulesets.draft.update'), ['weights' => $weights, 'thresholds' => ['apply' => 60, 'review' => 70]])
            ->assertSessionHasErrors('thresholds.review');
        $this->actingAs($admin)->put(route('upwork.config.rulesets.draft.update'), ['weights' => $weights, 'thresholds' => ['apply' => 80, 'review' => 70], 'advanced_settings' => '[1,2]'])
            ->assertSessionHasErrors('advanced_settings');
        $this->actingAs($admin)->put(route('upwork.config.rulesets.draft.update'), ['weights' => array_fill_keys(array_keys(UpworkRuleset::COMPONENTS), 0), 'thresholds' => ['apply' => 80, 'review' => 70]])
            ->assertSessionHasErrors('weights');
    }

    public function test_rules_can_be_edited_disabled_and_removed_in_the_draft_only(): void
    {
        $admin = $this->admin();
        $published = UpworkRule::where('key', 'tech_wordpress')->first();
        $this->actingAs($admin)->get(route('upwork.config.rulesets.rules.edit', $published))->assertNotFound();

        $this->actingAs($admin)->post(route('upwork.config.rulesets.draft.create'));
        $rule = UpworkRuleset::draft()->rules()->where('key', 'tech_wordpress')->first();

        $this->actingAs($admin)->get(route('upwork.config.rulesets.rules.edit', $rule))->assertOk()->assertSee('Reads as:')->assertSee('mentions WordPress');
        $this->actingAs($admin)->put(route('upwork.config.rulesets.rules.update', $rule), [
            'name' => 'WordPress', 'type' => 'score', 'component' => 'technical_fit', 'points' => 7, 'enabled' => 1, 'sort' => 1,
            'conditions' => '{"op": "has_any_tech", "value": ["wordpress", "woocommerce"]}',
        ])->assertRedirect();
        $this->assertEquals(7, $rule->fresh()->points);
        $this->assertSame(5.0, (float) $published->fresh()->points, 'published rule untouched');

        $this->actingAs($admin)->post(route('upwork.config.rulesets.rules.toggle', $rule))->assertRedirect();
        $this->assertFalse($rule->fresh()->enabled);
        $this->actingAs($admin)->delete(route('upwork.config.rulesets.rules.destroy', $rule))->assertRedirect();
        $this->assertNull(UpworkRule::find($rule->id));

        $this->actingAs($admin)->delete(route('upwork.config.rulesets.draft.discard'))->assertRedirect(route('upwork.config.rulesets.index'));
        $this->assertNull(UpworkRuleset::draft());
        $this->assertSame(1, UpworkRuleset::current()->version);
        $this->actingAs($admin)->get(route('upwork.config.rulesets.draft.edit'))->assertRedirect(route('upwork.config.rulesets.index'));
    }

    // ---- Agent settings & overview ---------------------------------------------------------------

    public function test_agent_settings_are_validated_saved_and_audited(): void
    {
        $admin = $this->admin();
        $form = ['agent_enabled' => 1, 'agent_interval_minutes' => 30, 'agent_lease_minutes' => 15, 'agent_max_backoff_minutes' => 240,
            'agent_enrich_limit_per_run' => 10, 'discovery_max_queries_per_profile' => 3, 'discovery_max_queries_per_run' => 25,
            'discovery_enrich_min_score' => 60, 'discovery_max_searches_per_day' => 400, 'discovery_known_ids_per_profile' => 50, 'notification_channels' => ['log', 'mail']];

        $this->actingAs($admin)->put(route('upwork.config.settings.update'), array_merge($form, ['agent_interval_minutes' => 2]))
            ->assertSessionHasErrors('agent_interval_minutes');

        $this->actingAs($admin)->put(route('upwork.config.settings.update'), $form)->assertSessionHas('success', 'Settings saved.');
        $this->assertTrue(UpworkSetting::get('agent.enabled'));
        $this->assertSame(30, UpworkSetting::get('agent.interval_minutes'));
        $this->assertSame(['log', 'mail'], UpworkSetting::get('notifications.channels'));
        $this->assertSame([20, 30], UpworkConfigLog::where('entity', 'setting')->latest('id')->first()->changes['agent.interval_minutes']);

        $this->actingAs($admin)->get(route('upwork.config.index'))->assertOk()->assertSee('Enabled')->assertSee('Every 30 min')->assertSee('Agent &amp; discovery settings', false);
    }
}
