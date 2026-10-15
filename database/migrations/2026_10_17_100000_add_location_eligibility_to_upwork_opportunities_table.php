<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Location eligibility: can an India-based freelancer apply? Stores the
 * freelancer-location text Upwork gave, what the rules made of it, and an
 * optional human override (which never replaces the original text or rule).
 * Additive and nullable: existing rows stay unclassified until a backfill
 * (upwork:classify-locations --apply) or their next sighting.
 */
class AddLocationEligibilityToUpworkOpportunitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('upwork_opportunities', function (Blueprint $table) {
            $table->string('location_text', 500)->nullable()->after('archived_from_status');      // as given, never rewritten by an override
            $table->string('location_source', 100)->nullable()->after('location_text');           // raw_data path it came from
            $table->string('location_eligibility', 20)->nullable()->after('location_source')->index(); // effective: eligible | restricted | unknown
            $table->string('location_auto_eligibility', 20)->nullable()->after('location_eligibility'); // what the rules said
            $table->string('location_rule', 50)->nullable()->after('location_auto_eligibility');
            $table->string('location_reason', 255)->nullable()->after('location_rule');
            $table->json('location_details')->nullable()->after('location_reason');
            $table->unsignedSmallInteger('location_rules_version')->nullable()->after('location_details');
            $table->timestamp('location_classified_at')->nullable()->after('location_rules_version');
            $table->string('location_prior_recommendation', 20)->nullable()->after('location_classified_at'); // scoring's recommendation while held at SKIP
            $table->string('location_override', 20)->nullable()->after('location_prior_recommendation');
            $table->unsignedBigInteger('location_override_by')->nullable()->after('location_override');
            $table->timestamp('location_override_at')->nullable()->after('location_override_by');
            $table->string('location_override_note', 500)->nullable()->after('location_override_at');
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
            $table->dropIndex(['location_eligibility']);
            $table->dropColumn([
                'location_text', 'location_source', 'location_eligibility', 'location_auto_eligibility', 'location_rule',
                'location_reason', 'location_details', 'location_rules_version', 'location_classified_at',
                'location_prior_recommendation', 'location_override', 'location_override_by', 'location_override_at',
                'location_override_note',
            ]);
        });
    }
}
