<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Search-profile tiers and per-profile budgets for cost-efficient, rotating discovery. Additive only. */
class AddTiersToUpworkSearchProfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('upwork_search_profiles', function (Blueprint $table) {
            $table->string('tier', 20)->default('normal')->after('priority')->index(); // hot, normal, deep, exploratory
            $table->unsignedInteger('daily_call_budget')->default(30)->after('frequency_minutes');
            $table->unsignedInteger('max_results')->default(10)->after('daily_call_budget'); // per search
            $table->boolean('search_related')->default(false)->after('max_results');      // also search related technology names
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('upwork_search_profiles', function (Blueprint $table) {
            $table->dropIndex(['tier']);
            $table->dropColumn(['tier', 'daily_call_budget', 'max_results', 'search_related']);
        });
    }
}
