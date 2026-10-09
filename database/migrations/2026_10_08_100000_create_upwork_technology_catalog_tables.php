<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Technology / capability catalog for the Upwork intelligence engine:
 * technologies with a capability tier, their aliases, and a weighted
 * relationship graph used for search expansion and partial scoring credit.
 */
class CreateUpworkTechnologyCatalogTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('upwork_technologies', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->string('category', 50)->nullable(); // backend, frontend, cms, ai, automation, domain, devops, ...
            $table->string('tier', 20)->default('adjacent')->index(); // proven, adjacent, emerging, excluded
            $table->decimal('search_weight', 5, 2)->default(1);
            $table->decimal('scoring_weight', 5, 2)->default(1);
            $table->text('evidence')->nullable(); // what backs a PROVEN claim (portfolio, profile, codebase)
            $table->text('notes')->nullable();
            $table->boolean('enabled')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('upwork_technology_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('technology_id')->constrained('upwork_technologies')->onDelete('cascade');
            $table->string('alias', 100);
            $table->string('alias_normalized', 100)->unique(); // one alias -> one technology
            $table->timestamps();
        });

        Schema::create('upwork_technology_relations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('technology_id')->constrained('upwork_technologies')->onDelete('cascade');
            $table->foreignId('related_technology_id')->constrained('upwork_technologies')->onDelete('cascade');
            $table->string('relation_type', 30)->default('related'); // related, parent, uses, alternative
            $table->decimal('weight', 4, 2)->default(0.5); // 0..1, credit carried across the edge
            $table->timestamps();

            $table->unique(['technology_id', 'related_technology_id'], 'upwork_tech_relations_pair_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('upwork_technology_relations');
        Schema::dropIfExists('upwork_technology_aliases');
        Schema::dropIfExists('upwork_technologies');
    }
}
