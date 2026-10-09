<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Versioned scoring configuration. Admins edit a draft ruleset; publishing
 * freezes it (rules + snapshot) under a new version number. Opportunities keep
 * the version they were scored with, so old scores are never reinterpreted.
 */
class CreateUpworkScoringTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('upwork_rulesets', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('version')->nullable()->unique(); // null while draft
            $table->string('status', 20)->default('draft')->index(); // draft, published, archived
            $table->json('weights');     // component => max points
            $table->json('thresholds');  // apply, review
            $table->json('settings')->nullable(); // agency_mode, budget floors, heuristics, ...
            $table->json('snapshot')->nullable(); // full frozen copy (weights, thresholds, settings, rules) on publish
            $table->text('notes')->nullable();
            $table->foreignId('based_on_id')->nullable()->constrained('upwork_rulesets')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('upwork_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ruleset_id')->constrained('upwork_rulesets')->onDelete('cascade');
            $table->string('key', 64); // stable identifier across versions
            $table->string('name', 150);
            $table->string('type', 20); // score, hard_filter, classification
            $table->string('component', 40)->nullable(); // score rules: which component the points go to
            $table->decimal('points', 6, 2)->default(0); // signed; negative rules use negative points
            $table->string('classification', 50)->nullable(); // classification rules: label assigned
            $table->json('conditions'); // structured {all|any: [...]} tree, never code
            $table->string('reason', 255)->nullable(); // shown in the score explanation / hard-filter record
            $table->boolean('enabled')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->unique(['ruleset_id', 'key']);
            $table->index(['ruleset_id', 'type', 'enabled']);
        });

        Schema::create('upwork_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->json('value')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('upwork_settings');
        Schema::dropIfExists('upwork_rules');
        Schema::dropIfExists('upwork_rulesets');
    }
}
