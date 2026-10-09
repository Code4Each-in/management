<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Discovery engine: audited runs (with a lease so runs never overlap), every
 * query the agent executed, and per-profile sightings so a job found by
 * several searches stays one opportunity. Additive only.
 */
class CreateUpworkDiscoveryTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('upwork_discovery_runs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('status', 20)->default('running')->index(); // running, completed, failed, abandoned
            $table->string('trigger', 20)->default('scheduled');        // scheduled, manual
            $table->string('agent_host', 100)->nullable();
            $table->unsignedInteger('ruleset_version')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('heartbeat_at')->nullable();
            $table->timestamp('lease_expires_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->json('plan')->nullable();  // profiles + queries handed to the agent
            $table->json('stats')->nullable(); // queries, found, new, existing, duplicates, ignored, hard_rejected, enriched, errors
            $table->text('error')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // token owner
            $table->timestamps();
        });

        Schema::create('upwork_discovery_queries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_id')->constrained('upwork_discovery_runs')->onDelete('cascade');
            $table->foreignId('search_profile_id')->nullable()->constrained('upwork_search_profiles')->nullOnDelete();
            $table->unsignedInteger('profile_version')->nullable();
            $table->string('query_type', 20);   // query, title
            $table->string('query_text', 255);
            $table->json('params')->nullable(); // Upwork search filters used
            $table->unsignedInteger('page')->default(1);
            $table->string('status', 20)->default('ok'); // ok, error, rate_limited
            $table->unsignedInteger('results_count')->default(0);
            $table->unsignedInteger('new_count')->default(0);       // first time the PMS saw the job
            $table->unsignedInteger('existing_count')->default(0);  // already stored before this run
            $table->unsignedInteger('duplicate_count')->default(0); // already returned earlier in this run
            $table->unsignedInteger('ignored_count')->default(0);   // profile exclusions / missing required keywords
            $table->unsignedInteger('hard_rejected_count')->default(0);
            $table->text('error')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['search_profile_id', 'created_at']);
        });

        Schema::create('upwork_opportunity_sightings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opportunity_id')->constrained('upwork_opportunities')->onDelete('cascade');
            $table->foreignId('search_profile_id')->constrained('upwork_search_profiles')->onDelete('cascade');
            $table->unsignedInteger('profile_version')->nullable();
            $table->foreignId('last_run_id')->nullable()->constrained('upwork_discovery_runs')->nullOnDelete();
            // useCurrent(): MySQL 5.7 strict mode rejects a second NOT NULL timestamp without a default.
            $table->timestamp('first_seen_at')->useCurrent();
            $table->timestamp('last_seen_at')->useCurrent();
            $table->unsignedInteger('times_seen')->default(1);
            $table->boolean('ignored')->default(false);
            $table->string('ignore_reason', 255)->nullable();

            $table->unique(['opportunity_id', 'search_profile_id'], 'upwork_sightings_pair_unique');
            $table->index(['search_profile_id', 'first_seen_at'], 'upwork_sightings_profile_first_seen_idx'); // default name exceeds MySQL's 64 chars
        });

        Schema::table('upwork_opportunities', function (Blueprint $table) {
            $table->string('stage', 20)->default('enriched')->after('status')->index(); // discovered (search row) | enriched (full detail)
            $table->boolean('discovery_ignored')->default(false)->after('stage');       // every profile that found it excluded it
            $table->timestamp('first_seen_at')->nullable()->after('fetched_at');
            $table->timestamp('last_seen_at')->nullable()->after('first_seen_at');
            $table->unsignedInteger('times_seen')->default(0)->after('last_seen_at');
        });

        Schema::table('upwork_search_profiles', function (Blueprint $table) {
            $table->unsignedInteger('query_cursor')->default(0)->after('last_error'); // rotation through the query plan
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
            $table->dropColumn('query_cursor');
        });

        Schema::table('upwork_opportunities', function (Blueprint $table) {
            $table->dropIndex(['stage']);
            $table->dropColumn(['stage', 'discovery_ignored', 'first_seen_at', 'last_seen_at', 'times_seen']);
        });

        Schema::dropIfExists('upwork_opportunity_sightings');
        Schema::dropIfExists('upwork_discovery_queries');
        Schema::dropIfExists('upwork_discovery_runs');
    }
}
