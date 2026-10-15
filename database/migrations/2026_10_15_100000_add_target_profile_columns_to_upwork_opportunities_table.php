<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dual-track targeting (agency profile / individual freelancer profiles).
 * Engine-owned: the best-fit track and a fit score per track. Reviewer-owned:
 * the profile and proposal angle chosen at approval. Additive only: every
 * column is nullable, nothing is backfilled here, and no existing column,
 * score or recommendation changes.
 */
class AddTargetProfileColumnsToUpworkOpportunitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('upwork_opportunities', function (Blueprint $table) {
            $table->string('target_profile', 20)->nullable()->after('classifications')->index(); // agency, individual, both
            $table->decimal('agency_fit_score', 5, 2)->nullable()->after('target_profile');
            $table->decimal('individual_fit_score', 5, 2)->nullable()->after('agency_fit_score');
            $table->string('selected_profile', 100)->nullable()->after('approved_by');      // "agency" or "individual:<key>"
            $table->string('proposal_angle', 40)->nullable()->after('selected_profile');
            $table->string('proposal_angle_note', 500)->nullable()->after('proposal_angle');
            $table->unsignedBigInteger('profile_selected_by')->nullable()->after('proposal_angle_note');
            $table->timestamp('profile_selected_at')->nullable()->after('profile_selected_by');
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
            $table->dropIndex(['target_profile']);
            $table->dropColumn([
                'target_profile', 'agency_fit_score', 'individual_fit_score', 'selected_profile',
                'proposal_angle', 'proposal_angle_note', 'profile_selected_by', 'profile_selected_at',
            ]);
        });
    }
}
