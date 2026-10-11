<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Configurable discovery search profiles. The Mac mini agent reads these from
 * the PMS and runs them against the read-only Upwork MCP. Run state lives on
 * the profile so the agent never needs its own copy of business config.
 */
class CreateUpworkSearchProfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('upwork_search_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->boolean('enabled')->default(true)->index();
            $table->unsignedInteger('priority')->default(100); // lower runs first
            $table->string('category', 50)->nullable();

            $table->json('keywords')->nullable();          // each becomes a search query
            $table->json('exact_phrases')->nullable();     // title searches (all words must be in the title)
            $table->json('required_keywords')->nullable(); // post-fetch: job must contain at least one
            $table->json('optional_keywords')->nullable(); // post-fetch: relevance boost only
            $table->json('excluded_keywords')->nullable(); // post-fetch: job ignored if any match
            $table->json('excluded_phrases')->nullable();
            $table->json('upwork_filters')->nullable();    // job_type, verified_payment_only, rate_min, ...

            $table->decimal('min_score', 5, 2)->nullable();
            $table->unsignedInteger('frequency_minutes')->default(20);
            $table->text('notes')->nullable();
            $table->unsignedInteger('version')->default(1); // bumped on every config change

            // Run state (written by discovery, never by the editor).
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('last_success_at')->nullable();
            $table->unsignedInteger('consecutive_failures')->default(0);
            $table->timestamp('backoff_until')->nullable();
            $table->text('last_error')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('upwork_search_profile_technology', function (Blueprint $table) {
            $table->foreignId('search_profile_id')->constrained('upwork_search_profiles')->onDelete('cascade');
            $table->foreignId('technology_id')->constrained('upwork_technologies')->onDelete('cascade');

            $table->primary(['search_profile_id', 'technology_id'], 'upwork_profile_tech_primary');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('upwork_search_profile_technology');
        Schema::dropIfExists('upwork_search_profiles');
    }
}
