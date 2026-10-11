<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUpworkOpportunitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('upwork_opportunities', function (Blueprint $table) {
            $table->id();

            // Job
            $table->string('upwork_job_id', 64)->unique();
            $table->string('upwork_url', 500)->nullable();
            $table->string('title');
            $table->longText('description')->nullable();
            $table->string('job_type', 20)->nullable(); // hourly, fixed
            $table->decimal('hourly_min', 10, 2)->nullable();
            $table->decimal('hourly_max', 10, 2)->nullable();
            $table->decimal('fixed_budget', 12, 2)->nullable();
            $table->string('experience_level', 50)->nullable();
            $table->string('duration', 100)->nullable();

            // Client
            $table->string('client_country', 100)->nullable();
            $table->string('client_timezone', 100)->nullable();
            $table->decimal('client_rating', 3, 2)->nullable();
            $table->unsignedInteger('client_reviews_count')->nullable();
            $table->decimal('client_total_spent', 14, 2)->nullable();
            $table->decimal('client_hire_rate', 5, 2)->nullable(); // percent
            $table->unsignedInteger('client_jobs_posted')->nullable();
            $table->unsignedInteger('client_jobs_hired')->nullable();
            $table->boolean('payment_verified')->nullable();

            // Competition
            $table->unsignedInteger('proposals_count')->nullable();
            $table->unsignedInteger('interviews_count')->nullable();
            $table->unsignedSmallInteger('connects_required')->nullable();

            $table->timestamp('posted_at')->nullable();
            $table->timestamp('fetched_at')->nullable();

            // Scoring (computed by the agent; weights live in config/upwork.php)
            $table->decimal('score', 5, 2)->nullable();
            $table->decimal('technical_fit_score', 5, 2)->nullable();
            $table->decimal('experience_fit_score', 5, 2)->nullable();
            $table->decimal('client_quality_score', 5, 2)->nullable();
            $table->decimal('commercial_value_score', 5, 2)->nullable();
            $table->decimal('long_term_score', 5, 2)->nullable();
            $table->decimal('ai_automation_score', 5, 2)->nullable();
            $table->decimal('competition_score', 5, 2)->nullable();
            $table->json('score_breakdown')->nullable(); // any extra/future components
            $table->string('scoring_version', 50)->nullable();
            $table->json('hard_filters')->nullable(); // triggered hard-filter reasons

            // Analysis
            $table->string('recommendation', 20)->nullable(); // apply, review, skip
            $table->text('match_summary')->nullable();
            $table->text('relevant_experience')->nullable();
            $table->text('risks')->nullable();
            $table->json('technologies')->nullable();

            // State
            $table->string('status', 30)->default('new');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();

            // Proposal preparation (never submitted to Upwork by the PMS)
            $table->longText('proposal_draft')->nullable();
            $table->decimal('proposed_rate', 10, 2)->nullable();
            $table->decimal('estimated_hours', 8, 2)->nullable();
            $table->json('screening_answers')->nullable();
            $table->timestamp('proposal_generated_at')->nullable();
            $table->timestamp('proposal_edited_at')->nullable();
            $table->foreignId('proposal_edited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('proposal_approved_at')->nullable();
            $table->foreignId('proposal_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();

            $table->json('raw_data')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('recommendation');
            $table->index('score');
            $table->index('posted_at');
            $table->index(['status', 'score']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('upwork_opportunities');
    }
}
