<?php

namespace Tests\Feature\Upwork;

use App\Models\UpworkRule;
use App\Models\UpworkRuleset;
use App\Models\UpworkSearchProfile;
use App\Models\UpworkSetting;
use App\Models\UpworkTechnology;
use App\Models\UpworkTechnologyAlias;
use Database\Seeders\UpworkIntelligenceSeeder;
use Illuminate\Database\QueryException;

/**
 * Phase 1: configuration data model (catalog, search profiles, versioned rulesets, settings).
 */
class UpworkConfigurationModelTest extends UpworkTestCase
{
    private function seedDefaults(): void
    {
        (new UpworkIntelligenceSeeder)->run();
    }

    private function scoreRule(array $overrides = []): array
    {
        return array_merge([
            'key' => 'test_rule', 'name' => 'Test', 'type' => 'score', 'component' => 'technical_fit', 'points' => 5,
            'conditions' => ['op' => 'has_any_tech', 'value' => ['laravel']],
        ], $overrides);
    }

    // ---- Seeder -------------------------------------------------------------

    public function test_seeder_creates_default_configuration(): void
    {
        $this->seedDefaults();

        $this->assertGreaterThan(40, UpworkTechnology::count());
        $this->assertSame(11, UpworkSearchProfile::count());
        $this->assertSame(1, UpworkRuleset::where('status', 'published')->count());

        $current = UpworkRuleset::current();
        $this->assertSame(1, $current->version);
        $this->assertSame(['apply' => 80, 'review' => 70], $current->thresholds);
        $this->assertSame(UpworkRuleset::defaultWeights(), $current->weights);
        $this->assertEqualsWithDelta(100, array_sum($current->normalizedWeights()), 0.001);
        $this->assertNotEmpty($current->snapshot['rules']);

        foreach (['hard_filter', 'score', 'classification'] as $type) {
            $this->assertTrue($current->rules->where('type', $type)->isNotEmpty(), "missing {$type} rules");
        }

        $this->assertFalse(UpworkSetting::get('agent.enabled'));
        $this->assertSame(20, UpworkSetting::get('agent.interval_minutes'));
    }

    public function test_seeder_is_idempotent_and_never_overwrites_admin_edits(): void
    {
        $this->seedDefaults();
        $counts = [UpworkTechnology::count(), UpworkTechnologyAlias::count(), UpworkSearchProfile::count(), UpworkRule::count()];

        $laravel = UpworkTechnology::where('slug', 'laravel')->first();
        $laravel->update(['tier' => 'adjacent', 'notes' => 'edited by admin']);
        UpworkSearchProfile::where('slug', 'ai-automation')->first()->update(['keywords' => ['custom']]);
        UpworkSetting::set('agent.interval_minutes', 45);

        $this->seedDefaults();

        $this->assertSame($counts, [UpworkTechnology::count(), UpworkTechnologyAlias::count(), UpworkSearchProfile::count(), UpworkRule::count()]);
        $this->assertSame('adjacent', $laravel->fresh()->tier);
        $this->assertSame(['custom'], UpworkSearchProfile::where('slug', 'ai-automation')->first()->keywords);
        $this->assertSame(45, UpworkSetting::get('agent.interval_minutes'));
        $this->assertSame(1, UpworkRuleset::count());
    }

    public function test_every_seeded_proven_technology_has_evidence(): void
    {
        $this->seedDefaults();

        $proven = UpworkTechnology::where('tier', 'proven')->get();
        $this->assertNotEmpty($proven);
        foreach ($proven as $technology) {
            $this->assertNotEmpty(trim((string) $technology->evidence), "{$technology->name} is PROVEN without evidence");
        }

        // The four tiers are all represented.
        $this->assertEqualsCanonicalizing(array_keys(UpworkTechnology::TIERS), UpworkTechnology::distinct()->pluck('tier')->all());
    }

    // ---- Technologies ---------------------------------------------------------

    public function test_proven_tier_requires_evidence(): void
    {
        $this->expectException(\DomainException::class);

        UpworkTechnology::create(['name' => 'Elixir', 'tier' => 'proven']);
    }

    public function test_unknown_tier_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        UpworkTechnology::create(['name' => 'Elixir', 'tier' => 'expert']);
    }

    public function test_aliases_are_normalized_and_unique_across_technologies(): void
    {
        $openai = UpworkTechnology::create(['name' => 'OpenAI', 'tier' => 'emerging']);
        $alias = $openai->addAlias('  Chat   GPT ');

        $this->assertSame('Chat GPT', $alias->alias);
        $this->assertSame('chat gpt', $alias->alias_normalized);
        $this->assertTrue($openai->addAlias('chat gpt')->is($alias), 'adding the same alias twice is a no-op');

        $other = UpworkTechnology::create(['name' => 'Other', 'tier' => 'emerging']);
        $this->expectException(QueryException::class);
        $other->addAlias('CHAT GPT');
    }

    public function test_relationships_are_weighted_and_cannot_point_to_self(): void
    {
        $laravel = UpworkTechnology::create(['name' => 'Laravel', 'tier' => 'proven', 'evidence' => 'x']);
        $php = UpworkTechnology::create(['name' => 'PHP', 'tier' => 'proven', 'evidence' => 'x']);

        $laravel->relateTo($php, 1.7, 'uses');
        $related = $laravel->relatedTechnologies()->first();

        $this->assertTrue($related->is($php));
        $this->assertEquals(1.0, $related->pivot->weight, 'weight is clamped to 0..1');
        $this->assertSame('uses', $related->pivot->relation_type);

        $this->expectException(\InvalidArgumentException::class);
        $laravel->relateTo($laravel);
    }

    // ---- Search profiles ------------------------------------------------------

    public function test_search_profile_lists_are_cleaned_and_filters_whitelisted(): void
    {
        $profile = UpworkSearchProfile::create([
            'name' => 'Booking Platforms',
            'keywords' => [' booking engine ', 'Booking Engine', '', 'reservation'],
            'excluded_keywords' => ['  junior'],
            'upwork_filters' => ['verified_payment_only' => true, 'submit_proposal' => true],
        ]);

        $this->assertSame('booking-platforms', $profile->slug);
        $this->assertSame(['booking engine', 'reservation'], $profile->keywords);
        $this->assertSame(['junior'], $profile->excluded_keywords);
        $this->assertSame(['verified_payment_only' => true], $profile->upwork_filters);
        $this->assertSame(1, $profile->version);
    }

    public function test_search_profile_version_bumps_only_on_search_changes(): void
    {
        $profile = UpworkSearchProfile::create(['name' => 'AI Automation', 'keywords' => ['OpenAI']]);

        $profile->update(['enabled' => false, 'priority' => 5, 'notes' => 'x', 'last_run_at' => now()]);
        $this->assertSame(1, $profile->fresh()->version, 'enable/priority/notes/run state do not change search behaviour');

        $profile->update(['keywords' => ['OpenAI', 'n8n']]);
        $this->assertSame(2, $profile->fresh()->version);

        $profile->update(['excluded_phrases' => ['data entry']]);
        $this->assertSame(3, $profile->fresh()->version);

        $tech = UpworkTechnology::create(['name' => 'n8n', 'tier' => 'emerging']);
        $profile->fresh()->syncTechnologies([$tech->id]);
        $this->assertSame(4, $profile->fresh()->version);

        $profile->fresh()->syncTechnologies([$tech->id]);
        $this->assertSame(4, $profile->fresh()->version, 'unchanged technology set does not bump');
    }

    // ---- Rulesets and rules ---------------------------------------------------

    public function test_published_ruleset_and_its_rules_are_immutable(): void
    {
        $this->seedDefaults();
        $current = UpworkRuleset::current();

        try {
            $current->update(['thresholds' => ['apply' => 90, 'review' => 70]]);
            $this->fail('published ruleset was edited');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('cannot be changed', $e->getMessage());
        }

        try {
            $current->rules()->create($this->scoreRule());
            $this->fail('rule added to a published ruleset');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('draft', $e->getMessage());
        }

        try {
            $current->rules->first()->update(['points' => 99]);
            $this->fail('rule of a published ruleset was edited');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('draft', $e->getMessage());
        }

        $this->expectException(\DomainException::class);
        $current->delete();
    }

    public function test_draft_copies_current_version_and_publishing_creates_next_version(): void
    {
        $this->seedDefaults();
        $v1 = UpworkRuleset::current();

        $draft = UpworkRuleset::draftOrCreate();
        $this->assertTrue($draft->is(UpworkRuleset::draftOrCreate()), 'only one draft at a time');
        $this->assertSame($v1->rules->count(), $draft->rules()->count());
        $this->assertSame($v1->id, $draft->based_on_id);

        $draft->update(['weights' => array_merge($v1->weights, ['ai_automation' => 20])]);
        $draft->rules()->create($this->scoreRule(['key' => 'tech_symfony', 'conditions' => ['op' => 'has_any_tech', 'value' => ['symfony']]]));
        $draft->publish(null);

        $this->assertSame(2, $draft->fresh()->version);
        $this->assertTrue(UpworkRuleset::current()->is($draft));
        $this->assertSame('archived', $v1->fresh()->status);
        $this->assertSame(1, $v1->fresh()->version, 'old version keeps its number');
        $this->assertSame(10, $v1->fresh()->weights['ai_automation'], 'old version keeps its weights');
        $this->assertContains('tech_symfony', array_column($draft->fresh()->snapshot['rules'], 'key'));
    }

    public function test_weights_are_normalized_to_100(): void
    {
        $ruleset = UpworkRuleset::create([
            'weights' => ['technical_fit' => 40, 'experience_fit' => 40, 'client_quality' => 40, 'commercial_value' => 30,
                'long_term' => 20, 'ai_automation' => 20, 'competition' => 10],
            'thresholds' => ['apply' => 80, 'review' => 70],
        ]);

        $normalized = $ruleset->normalizedWeights();

        $this->assertEqualsWithDelta(100, array_sum($normalized), 0.001);
        $this->assertEqualsWithDelta(20, $normalized['technical_fit'], 0.001);
        $this->assertEqualsWithDelta(5, $normalized['competition'], 0.001);
    }

    public function test_publish_is_blocked_by_invalid_weights_or_thresholds(): void
    {
        $ruleset = UpworkRuleset::create([
            'weights' => array_merge(UpworkRuleset::defaultWeights(), ['technical_fit' => -5]),
            'thresholds' => ['apply' => 60, 'review' => 70],
        ]);

        try {
            $ruleset->publish(null);
            $this->fail('invalid ruleset was published');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('technical_fit', $e->getMessage());
            $this->assertStringContainsString('Thresholds', $e->getMessage());
        }

        $this->assertNull($ruleset->fresh()->version);
    }

    public function test_invalid_rules_are_rejected_on_save(): void
    {
        $draft = UpworkRuleset::create(['weights' => UpworkRuleset::defaultWeights(), 'thresholds' => ['apply' => 80, 'review' => 70]]);

        $invalid = [
            'unknown operator' => $this->scoreRule(['conditions' => ['field' => 'job.title', 'op' => 'eval', 'value' => 'x']]),
            'unknown field' => $this->scoreRule(['conditions' => ['field' => 'job.secret', 'op' => 'eq', 'value' => 1]]),
            'unknown component' => $this->scoreRule(['component' => 'vibes']),
            'hard filter without reason' => $this->scoreRule(['type' => 'hard_filter', 'reason' => '']),
            'classification without label' => $this->scoreRule(['type' => 'classification', 'classification' => 'quick win']),
            'bad key' => $this->scoreRule(['key' => 'Bad Key!']),
        ];

        foreach ($invalid as $case => $attributes) {
            try {
                $draft->rules()->create($attributes);
                $this->fail("accepted: {$case}");
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString('Invalid rule', $e->getMessage(), $case);
            }
        }

        $this->assertSame(0, $draft->rules()->count());
    }

    // ---- Rule conditions ------------------------------------------------------

    public function test_condition_validator_accepts_structured_trees(): void
    {
        $valid = ['all' => [
            ['field' => 'client.hire_rate', 'op' => 'lt', 'value' => 20],
            ['field' => 'job.hourly_max', 'op' => 'lt', 'value' => ['setting' => 'min_hourly']],
            ['any' => [
                ['field' => 'job.text', 'op' => 'contains_any', 'value' => ['cheap developer']],
                ['not' => ['op' => 'has_tier', 'value' => 'proven']],
                ['op' => 'tier_count_gte', 'value' => ['tier' => 'proven', 'count' => 2]],
            ]],
            ['field' => 'job.fixed_budget', 'op' => 'between', 'value' => [500, 3000]],
            ['field' => 'client.payment_verified', 'op' => 'is_false'],
        ]];

        $this->assertSame([], \App\Services\Upwork\Scoring\RuleConditions::validate($valid));
    }

    public function test_condition_validator_rejects_malformed_or_unsafe_input(): void
    {
        $validate = fn($node) => \App\Services\Upwork\Scoring\RuleConditions::validate($node);

        $this->assertNotEmpty($validate('system("rm -rf /")'), 'a string is not a condition');
        $this->assertNotEmpty($validate([]));
        $this->assertNotEmpty($validate(['all' => []]));
        $this->assertNotEmpty($validate(['all' => [['field' => 'job.title', 'op' => 'eq', 'value' => 'x']], 'any' => []]));
        $this->assertNotEmpty($validate(['field' => 'job.title', 'op' => 'eq', 'value' => 'x', 'php' => 'x']));
        $this->assertNotEmpty($validate(['field' => 'job.title', 'op' => 'in', 'value' => ['setting' => 'x']]));
        $this->assertNotEmpty($validate(['field' => 'job.title', 'op' => 'has_any_tech', 'value' => ['laravel']]));
        $this->assertNotEmpty($validate(['field' => 'job.title', 'op' => 'is_null', 'value' => 1]));
        $this->assertNotEmpty($validate(['op' => 'has_tier', 'value' => 'expert']));

        $deep = ['field' => 'job.title', 'op' => 'not_null'];
        for ($i = 0; $i < 6; $i++) {
            $deep = ['not' => $deep];
        }
        $this->assertNotEmpty($validate($deep), 'depth is limited');
    }

    // ---- Settings -------------------------------------------------------------

    public function test_settings_fall_back_to_defaults_and_keep_falsy_values(): void
    {
        $this->assertSame(20, UpworkSetting::get('agent.interval_minutes'));
        $this->assertNull(UpworkSetting::get('unknown.key'));

        UpworkSetting::set('agent.enabled', false);
        UpworkSetting::set('agent.interval_minutes', 30);

        $this->assertFalse(UpworkSetting::get('agent.enabled'));
        $this->assertSame(30, UpworkSetting::get('agent.interval_minutes'));
        $this->assertSame(['log'], UpworkSetting::allWithDefaults()['notifications.channels']);
    }
}
