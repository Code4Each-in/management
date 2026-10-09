<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Commercial outlook estimates: heuristic win probability, estimated contract
 * value and expected value. These are estimates, not facts; their basis is
 * stored in score_explanation. Additive only.
 */
class AddOutlookColumnsToUpworkOpportunitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('upwork_opportunities', function (Blueprint $table) {
            $table->decimal('win_probability', 5, 2)->nullable()->after('strategic_score'); // percent, 0-100
            $table->decimal('contract_value_estimate', 12, 2)->nullable()->after('win_probability');
            $table->decimal('expected_value', 12, 2)->nullable()->after('contract_value_estimate')->index();
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
            $table->dropIndex(['expected_value']);
            $table->dropColumn(['win_probability', 'contract_value_estimate', 'expected_value']);
        });
    }
}
