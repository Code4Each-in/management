<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Thirty-minute review SLA (deadline, reminders, per-job snooze) and the AI
 * proposal-draft status. Additive only: every column is nullable, nothing is
 * backfilled, so existing jobs never enter the SLA retroactively.
 */
class AddReviewSlaColumnsToUpworkOpportunitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('upwork_opportunities', function (Blueprint $table) {
            $table->timestamp('review_due_at')->nullable()->after('notified_level');      // set once, never moved
            $table->timestamp('review_overdue_at')->nullable()->after('review_due_at');   // first time it was seen overdue
            $table->unsignedSmallInteger('review_reminder_stage')->nullable()->after('review_overdue_at'); // last reminder sent
            $table->timestamp('review_reminded_at')->nullable()->after('review_reminder_stage');
            $table->timestamp('review_snoozed_until')->nullable()->after('review_reminded_at');
            $table->unsignedBigInteger('review_snoozed_by')->nullable()->after('review_snoozed_until');
            $table->timestamp('reviewed_at')->nullable()->after('review_snoozed_by');     // first explicit approve/reject
            $table->string('proposal_ai_status', 20)->nullable()->after('proposal_generated_at'); // queued, ready, failed, blocked, skipped
            $table->string('proposal_ai_status_reason', 255)->nullable()->after('proposal_ai_status');

            $table->index(['status', 'review_due_at'], 'upwork_opps_status_review_due_idx');
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
            $table->dropIndex('upwork_opps_status_review_due_idx');
            $table->dropColumn([
                'review_due_at', 'review_overdue_at', 'review_reminder_stage', 'review_reminded_at',
                'review_snoozed_until', 'review_snoozed_by', 'reviewed_at', 'proposal_ai_status', 'proposal_ai_status_reason',
            ]);
        });
    }
}
