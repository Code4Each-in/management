<?php

namespace Tests\Feature\Upwork;

use App\Models\UpworkOpportunity;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpworkSchemaAndPaginationTest extends UpworkTestCase
{
    public function test_migration_creates_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('upwork_opportunities'));
        $this->assertTrue(Schema::hasTable('upwork_opportunity_logs'));

        foreach ([
            'upwork_job_id', 'upwork_url', 'title', 'description', 'job_type', 'hourly_min', 'hourly_max', 'fixed_budget',
            'experience_level', 'duration', 'client_country', 'client_timezone', 'client_rating', 'client_reviews_count',
            'client_total_spent', 'client_hire_rate', 'client_jobs_posted', 'client_jobs_hired', 'payment_verified',
            'proposals_count', 'interviews_count', 'connects_required', 'posted_at', 'fetched_at',
            'score', 'technical_fit_score', 'experience_fit_score', 'client_quality_score', 'commercial_value_score',
            'long_term_score', 'ai_automation_score', 'competition_score',
            'recommendation', 'match_summary', 'relevant_experience', 'risks', 'technologies',
            'status', 'approved_at', 'approved_by', 'rejected_at', 'rejected_by', 'rejection_reason',
            'proposal_draft', 'proposed_rate', 'estimated_hours', 'screening_answers',
            'proposal_generated_at', 'proposal_edited_at', 'proposal_approved_at', 'raw_data', 'created_at', 'updated_at',
        ] as $column) {
            $this->assertTrue(Schema::hasColumn('upwork_opportunities', $column), "Missing column {$column}");
        }
    }

    public function test_migration_rollback_drops_only_upwork_tables(): void
    {
        require_once database_path('migrations/2026_10_07_100000_create_upwork_opportunities_table.php');
        require_once database_path('migrations/2026_10_07_100100_create_upwork_opportunity_logs_table.php');

        (new \CreateUpworkOpportunityLogsTable)->down();
        (new \CreateUpworkOpportunitiesTable)->down();

        $this->assertFalse(Schema::hasTable('upwork_opportunity_logs'));
        $this->assertFalse(Schema::hasTable('upwork_opportunities'));
        $this->assertTrue(Schema::hasTable('users'));
    }

    public function test_database_enforces_unique_upwork_job_id(): void
    {
        $this->opportunity(['upwork_job_id' => 'dupe-1']);

        $this->expectException(QueryException::class);
        DB::table('upwork_opportunities')->insert([
            'upwork_job_id' => 'dupe-1', 'title' => 'Second', 'status' => 'new',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_new_opportunity_defaults_to_new_status(): void
    {
        $o = UpworkOpportunity::create(['upwork_job_id' => 'defaults', 'title' => 'Defaults']);

        $this->assertSame('new', $o->fresh()->status);
    }

    public function test_list_is_paginated_and_keeps_filters_in_links(): void
    {
        config(['upwork.per_page' => 5]);
        for ($i = 1; $i <= 12; $i++) {
            $this->opportunity(['title' => sprintf('Paged job %02d', $i), 'score' => 100 - $i, 'recommendation' => 'apply']);
        }
        $user = $this->bde();
        $index = route('upwork.opportunities.index');

        $page1 = $this->actingAs($user)->get($index . '?recommendation=apply');
        $page1->assertOk()->assertSee('Paged job 01')->assertSee('Paged job 05')->assertDontSee('Paged job 06')
            ->assertSee('Showing 1–5 of 12')
            ->assertSee('recommendation=apply&amp;page=2', false);

        $this->actingAs($user)->get($index . '?recommendation=apply&page=3')
            ->assertOk()->assertSee('Paged job 11')->assertSee('Paged job 12')->assertDontSee('Paged job 10')
            ->assertSee('Showing 11–12 of 12');
    }
}
