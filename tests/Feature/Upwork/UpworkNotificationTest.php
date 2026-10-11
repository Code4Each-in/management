<?php

namespace Tests\Feature\Upwork;

use App\Models\UpworkOpportunity;
use App\Models\UpworkSetting;
use App\Services\Upwork\Notifications\LogNotifier;
use App\Services\Upwork\Notifications\OpportunityNotifier;
use App\Services\Upwork\UpworkOpportunityService;
use Database\Seeders\UpworkIntelligenceSeeder;

/**
 * Phase 9: notifications for new APPLY / REVIEW opportunities, and one
 * canonical id per Upwork job (numeric id == "~02" ciphertext).
 */
class UpworkNotificationTest extends UpworkTestCase
{
    /** @var array */
    private $sent = [];

    protected function setUp(): void
    {
        parent::setUp();
        (new UpworkIntelligenceSeeder)->run();

        $test = $this;
        $this->app->instance(LogNotifier::class, new class($test) implements OpportunityNotifier {
            private $test;
            public function __construct($test) { $this->test = $test; }
            public function send(array $message): void { $this->test->record($message); }
        });
    }

    public function record(array $message): void
    {
        $this->sent[] = $message;
    }

    /** Agent-scored payload, so the recommendation is exactly what the test needs. */
    private function ingest(string $id, string $recommendation, array $extra = []): UpworkOpportunity
    {
        [$o] = app(UpworkOpportunityService::class)->ingest($this->payload(array_merge([
            'upwork_job_id' => $id, 'upwork_url' => "https://www.upwork.com/jobs/~02{$id}",
            'recommendation' => $recommendation, 'score' => $recommendation === 'apply' ? 88 : ($recommendation === 'review' ? 74 : 40),
        ], $extra)));

        return $o->fresh();
    }

    public function test_apply_and_review_notify_once_skip_never(): void
    {
        $apply = $this->ingest('n1', 'apply');
        $this->ingest('n1', 'apply');           // rediscovered: no second notification
        $this->ingest('n2', 'review');
        $this->ingest('n3', 'skip');

        $this->assertSame(['🔥 New high-priority Upwork opportunity', 'New Upwork opportunity for review'], array_column($this->sent, 'subject'));
        $this->assertSame('apply', $apply->notified_level);
        $this->assertNotNull($apply->notified_at);
        $this->assertSame(route('upwork.opportunities.show', $apply), $this->sent[0]['url']);
        $this->assertStringContainsString('Score: 88/100 (APPLY)', implode("\n", $this->sent[0]['lines']));
    }

    public function test_review_upgraded_to_apply_notifies_again_but_not_the_reverse(): void
    {
        $this->ingest('u1', 'review');
        $this->ingest('u1', 'apply');
        $this->ingest('u1', 'review');

        $this->assertSame(['review', 'apply'], array_column($this->sent, 'level'));
    }

    public function test_decided_rejected_or_excluded_jobs_do_not_notify(): void
    {
        $o = $this->ingest('d1', 'skip');
        app(UpworkOpportunityService::class)->approve($o, $this->bde()->id);
        $this->ingest('d1', 'apply');                                           // already approved by a human
        $this->ingest('d2', 'apply', ['hard_filters' => ['Already hired']]);    // hard-rejected

        $this->assertSame([], $this->sent);
    }

    public function test_search_rows_never_notify_until_full_details_confirm_the_job(): void
    {
        $service = app(UpworkOpportunityService::class);
        $row = ['upwork_job_id' => '2107393100064249043', 'title' => 'Excellent Laravel/ReactJS developer required',
            'description' => 'Senior Laravel and ReactJS developer for our SaaS platform with REST APIs.', 'job_type' => 'hourly',
            'hourly_min' => 40, 'hourly_max' => 70, 'duration' => 'More than 6 months', 'payment_verified' => true,
            'client_rating' => 4.98, 'client_reviews_count' => 41, 'client_total_spent' => 527959, 'proposals_tier' => '5 to 10'];

        [$discovered] = $service->ingestSighting($row);
        $this->assertSame('discovered', $discovered->stage);
        $this->assertSame([], $this->sent, 'no notification from a search row');

        // Full details reveal "Individuals only": hard-rejected, still no notification.
        $service->ingest($row + ['upwork_url' => 'https://www.upwork.com/jobs/~022107393100064249043', 'contractor_type' => 'Individuals only']);
        $this->assertSame('skip', UpworkOpportunity::first()->recommendation);
        $this->assertSame([], $this->sent);
    }

    public function test_a_failing_channel_never_breaks_ingestion(): void
    {
        $this->app->instance(LogNotifier::class, new class implements OpportunityNotifier {
            public function send(array $message): void { throw new \RuntimeException('channel down'); }
        });

        $o = $this->ingest('f1', 'apply');

        $this->assertNotNull($o->id);
        $this->assertSame('apply', $o->notified_level, 'recorded so it is not retried every cycle');
    }

    public function test_mail_channel_uses_configured_recipients(): void
    {
        config(['mail.default' => 'array', 'mail.from.address' => 'pms@example.test']);
        UpworkSetting::set('notifications.channels', ['mail']);
        UpworkSetting::set('notifications.mail_to', ['bde@example.test']);

        $this->ingest('m1', 'apply');
        UpworkSetting::set('notifications.mail_to', []);
        $this->ingest('m2', 'apply'); // no recipients: skipped, ingestion still fine

        $messages = app('mailer')->getSwiftMailer()->getTransport()->messages();
        $this->assertCount(1, $messages);
        $this->assertSame(['bde@example.test'], array_keys($messages[0]->getTo()));
        $this->assertStringStartsWith('🔥 New high-priority Upwork opportunity: ', $messages[0]->getSubject());
        $this->assertStringContainsString('Nothing has been sent to Upwork', $messages[0]->getBody());
        $this->assertSame([], $this->sent, 'log channel was not selected');
    }

    public function test_settings_page_saves_and_validates_recipients(): void
    {
        $admin = $this->superAdmin();
        $form = ['agent_enabled' => 0, 'agent_interval_minutes' => 20, 'agent_lease_minutes' => 15, 'agent_max_backoff_minutes' => 240,
            'agent_enrich_limit_per_run' => 15, 'discovery_max_queries_per_profile' => 4, 'discovery_max_queries_per_run' => 30,
            'discovery_enrich_min_score' => 55, 'discovery_max_searches_per_day' => 400, 'discovery_known_ids_per_profile' => 50, 'notification_channels' => ['log', 'mail']];

        $this->actingAs($admin)->put(route('upwork.config.settings.update'), $form + ['notification_mail_to' => 'bde@example.test, not-an-email'])
            ->assertSessionHasErrors('notification_mail_to');
        $this->actingAs($admin)->put(route('upwork.config.settings.update'), $form + ['notification_mail_to' => "bde@example.test, sales@example.test\nbde@example.test"])
            ->assertSessionHas('success');

        $this->assertSame(['bde@example.test', 'sales@example.test'], UpworkSetting::get('notifications.mail_to'));
    }

    public function test_numeric_id_and_ciphertext_are_the_same_job(): void
    {
        $service = app(UpworkOpportunityService::class);

        $this->assertSame('2107561771980939922', UpworkOpportunityService::normalizeJobId('~022107561771980939922'));
        $this->assertSame('2107561771980939922', UpworkOpportunityService::normalizeJobId('2107561771980939922'));
        $this->assertSame('01abc123def456', UpworkOpportunityService::normalizeJobId('~01abc123def456'), 'legacy ids only lose the ~');

        $service->ingestSighting(['upwork_job_id' => '2107561771980939922', 'title' => 'From search', 'description' => 'Laravel job']);
        $service->ingest($this->payload(['upwork_job_id' => '~022107561771980939922', 'upwork_url' => 'https://www.upwork.com/jobs/~022107561771980939922', 'title' => 'From detail']));

        $this->assertSame(1, UpworkOpportunity::count());
        $this->assertSame('From detail', UpworkOpportunity::first()->title);
    }
}
