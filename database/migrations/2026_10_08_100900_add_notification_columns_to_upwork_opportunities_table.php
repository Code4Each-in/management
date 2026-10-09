<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Remember which notification level an opportunity already triggered, so nobody is notified twice. Additive only. */
class AddNotificationColumnsToUpworkOpportunitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('upwork_opportunities', function (Blueprint $table) {
            $table->timestamp('notified_at')->nullable()->after('times_seen');
            $table->string('notified_level', 10)->nullable()->after('notified_at'); // review | apply
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
            $table->dropColumn(['notified_at', 'notified_level']);
        });
    }
}
