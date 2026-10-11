<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Columns for PMS-side scoring: the job facts the engine reads, the agent's
 * semantic signals, and the explainable, versioned scoring result.
 * Additive only; existing rows keep NULLs.
 */
class AddScoringColumnsToUpworkOpportunitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('upwork_opportunities', function (Blueprint $table) {
            $table->string('proposals_tier', 20)->nullable()->after('proposals_count'); // "50+", "20 to 50" when no exact count
            $table->string('contractor_type', 50)->nullable()->after('experience_level'); // "Individuals only", "All freelancers"
            $table->string('workload', 30)->nullable()->after('duration');               // full_time, part_time, as_needed
            $table->json('job_activity')->nullable()->after('connects_required');        // invites, interviews, offers, hires for this posting
            $table->json('agent_signals')->nullable()->after('technologies');            // LLM judgement: tech slugs, scope size, clarity
            $table->unsignedInteger('ruleset_version')->nullable()->after('scoring_version')->index();
            $table->json('score_explanation')->nullable()->after('score_breakdown');     // baselines, rules fired, hard filters
            $table->json('classifications')->nullable()->after('recommendation');        // QUICK_WIN, STRATEGIC, AI_AUTOMATION, ...
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('upwork_opportunities', function (Blueprint $table) {
            $table->dropIndex(['ruleset_version']);
            $table->dropColumn([
                'proposals_tier', 'contractor_type', 'workload', 'job_activity', 'agent_signals',
                'ruleset_version', 'score_explanation', 'classifications',
            ]);
        });
    }
}
