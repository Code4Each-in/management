<?php

namespace Tests\Feature\Upwork;

use App\Models\UpworkOpportunity;
use App\Policies\UpworkOpportunityPolicy;
use Illuminate\Support\Facades\DB;

class UpworkOpportunityWebTest extends UpworkTestCase
{
    public function test_guests_are_redirected_to_login(): void
    {
        $o = $this->opportunity();

        $this->get(route('upwork.opportunities.index'))->assertRedirect(route('login'));
        $this->post(route('upwork.opportunities.approve', $o))->assertRedirect(route('login'));
        $this->assertSame('new', $o->fresh()->status);
    }

    public function test_users_without_access_are_forbidden(): void
    {
        $o = $this->opportunity();
        $dev = $this->developer();

        $this->actingAs($dev)->get(route('upwork.opportunities.index'))->assertForbidden();
        $this->actingAs($dev)->get(route('upwork.opportunities.show', $o))->assertForbidden();
        $this->actingAs($dev)->post(route('upwork.opportunities.approve', $o))->assertForbidden();
        $this->actingAs($dev)->post(route('upwork.opportunities.reject', $o))->assertForbidden();

        $this->assertSame('new', $o->fresh()->status);
    }

    public function test_role_granted_the_module_via_permissions_gets_access(): void
    {
        $dev = $this->developer();
        $moduleId = DB::table('modules')->insertGetId(['page_id' => 1, 'module_name' => 'Listing', 'route_name' => UpworkOpportunityPolicy::MODULE_ROUTE]);
        DB::table('role_permissions')->insert(['role_id' => $dev->role_id, 'module_id' => $moduleId]);

        $this->actingAs($dev)->get(route('upwork.opportunities.index'))->assertOk();
    }

    public function test_bde_and_super_admin_can_view_list_and_detail(): void
    {
        $o = $this->opportunity(['title' => 'Build a Laravel CRM', 'description' => '<script>alert(1)</script>', 'upwork_url' => 'https://www.upwork.com/jobs/~01x']);

        foreach ([$this->bde(), $this->superAdmin()] as $user) {
            $this->actingAs($user)->get(route('upwork.opportunities.index'))
                ->assertOk()
                ->assertSee('Build a Laravel CRM')
                ->assertSee('Upwork Opportunities');

            $this->actingAs($user)->get(route('upwork.opportunities.show', $o))
                ->assertOk()
                ->assertSee('Build a Laravel CRM')
                ->assertSee('Open on Upwork')
                ->assertDontSee('<script>alert(1)</script>', false); // escaped
        }
    }

    public function test_approve_sets_status_timestamp_user_and_log(): void
    {
        $o = $this->opportunity();
        $user = $this->bde();

        $this->actingAs($user)->from(route('upwork.opportunities.show', $o))
            ->post(route('upwork.opportunities.approve', $o))
            ->assertRedirect(route('upwork.opportunities.show', $o))
            ->assertSessionHas('success');

        $o->refresh();
        $this->assertSame(UpworkOpportunity::STATUS_APPROVED, $o->status);
        $this->assertNotNull($o->approved_at);
        $this->assertSame($user->id, $o->approved_by);
        $this->assertDatabaseHas('upwork_opportunity_logs', [
            'upwork_opportunity_id' => $o->id, 'user_id' => $user->id, 'action' => 'approved',
            'old_status' => 'new', 'new_status' => 'approved',
        ]);
    }

    public function test_reject_with_reason(): void
    {
        $o = $this->opportunity();
        $user = $this->bde();

        $this->actingAs($user)->post(route('upwork.opportunities.reject', $o), ['rejection_reason' => 'Budget too low'])
            ->assertSessionHas('success');

        $o->refresh();
        $this->assertSame(UpworkOpportunity::STATUS_REJECTED, $o->status);
        $this->assertNotNull($o->rejected_at);
        $this->assertSame($user->id, $o->rejected_by);
        $this->assertSame('Budget too low', $o->rejection_reason);
        $this->assertDatabaseHas('upwork_opportunity_logs', ['upwork_opportunity_id' => $o->id, 'action' => 'rejected', 'note' => 'Budget too low']);
    }

    public function test_reject_without_reason_and_reason_length_validation(): void
    {
        $o = $this->opportunity();
        $user = $this->bde();

        $this->actingAs($user)->post(route('upwork.opportunities.reject', $o), ['rejection_reason' => str_repeat('x', 2001)])
            ->assertSessionHasErrors('rejection_reason');
        $this->assertSame('new', $o->fresh()->status);

        $this->actingAs($user)->post(route('upwork.opportunities.reject', $o))->assertSessionHas('success');
        $o->refresh();
        $this->assertSame('rejected', $o->status);
        $this->assertNull($o->rejection_reason);
    }

    public function test_invalid_transitions_are_refused(): void
    {
        $won = $this->opportunity(['status' => 'won']);
        $user = $this->bde();

        $this->actingAs($user)->post(route('upwork.opportunities.approve', $won))->assertSessionHas('error');
        $this->actingAs($user)->post(route('upwork.opportunities.reject', $won))->assertSessionHas('error');
        $this->assertSame('won', $won->fresh()->status);
    }

    public function test_rejected_opportunity_can_be_re_approved_and_rejection_cleared(): void
    {
        $o = $this->opportunity();
        $user = $this->bde();

        $this->actingAs($user)->post(route('upwork.opportunities.reject', $o), ['rejection_reason' => 'nope']);
        $this->actingAs($user)->post(route('upwork.opportunities.approve', $o));

        $o->refresh();
        $this->assertSame('approved', $o->status);
        $this->assertNull($o->rejection_reason);
        $this->assertNull($o->rejected_at);
    }

    public function test_filtering_by_status_recommendation_min_score_and_search(): void
    {
        $this->opportunity(['title' => 'Alpha Laravel', 'score' => 90, 'recommendation' => 'apply']);
        $this->opportunity(['title' => 'Beta WordPress', 'score' => 72, 'recommendation' => 'review', 'status' => 'approved']);
        $this->opportunity(['title' => 'Gamma Data entry', 'score' => 30, 'recommendation' => 'skip', 'status' => 'rejected']);
        $user = $this->bde();
        $index = route('upwork.opportunities.index');

        $this->actingAs($user)->get($index . '?status=approved')
            ->assertSee('Beta WordPress')->assertDontSee('Alpha Laravel')->assertDontSee('Gamma Data entry');

        $this->actingAs($user)->get($index . '?recommendation=apply')
            ->assertSee('Alpha Laravel')->assertDontSee('Beta WordPress');

        $this->actingAs($user)->get($index . '?min_score=70')
            ->assertSee('Alpha Laravel')->assertSee('Beta WordPress')->assertDontSee('Gamma Data entry');

        $this->actingAs($user)->get($index . '?search=wordpress')
            ->assertSee('Beta WordPress')->assertDontSee('Alpha Laravel');

        // Invalid filter values are ignored (no redirect, no flashed error leaking to the next page).
        $this->actingAs($user)->get($index . '?status=bogus&min_score=abc')
            ->assertOk()->assertSessionHasNoErrors()
            ->assertSee('Alpha Laravel')->assertSee('Beta WordPress')->assertSee('Gamma Data entry');
    }

    public function test_dashboard_stats_count_by_status_and_high_score(): void
    {
        $this->opportunity(['score' => 85]);
        $this->opportunity(['score' => 90, 'status' => 'approved']);
        $this->opportunity(['score' => 50, 'status' => 'rejected']);
        $this->opportunity(['score' => 60, 'status' => 'won']);

        $response = $this->actingAs($this->bde())->get(route('upwork.opportunities.index'))->assertOk();
        $stats = $response->viewData('stats');

        $this->assertSame(1, $stats['new']);
        $this->assertSame(1, $stats['approved']);
        $this->assertSame(1, $stats['rejected']);
        $this->assertSame(1, $stats['won']);
        $this->assertSame(0, $stats['lost']);
        $this->assertSame(2, $stats['high_score']);
    }
}
