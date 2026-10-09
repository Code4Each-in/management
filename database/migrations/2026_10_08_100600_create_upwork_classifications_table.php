<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Classification catalog (labels that classification rules may assign) and the
 * Strategic Value score, which is kept separate from the Opportunity Score.
 * Additive only.
 */
class CreateUpworkClassificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('upwork_classifications', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();     // QUICK_WIN, STRATEGIC, LARAVEL, ...
            $table->string('label', 100);
            $table->string('kind', 20)->default('category'); // archetype (kind of opportunity) | category (technology / domain)
            $table->text('description')->nullable();
            $table->string('color', 20)->default('#065f46');
            $table->boolean('enabled')->default(true);
            $table->boolean('surface')->default(false); // keep in the default list even when the score says SKIP (e.g. quick wins)
            $table->unsignedInteger('sort')->default(100);
            $table->timestamps();
        });

        Schema::table('upwork_opportunities', function (Blueprint $table) {
            $table->decimal('strategic_score', 5, 2)->nullable()->after('score')->index();
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
            $table->dropIndex(['strategic_score']);
            $table->dropColumn('strategic_score');
        });
        Schema::dropIfExists('upwork_classifications');
    }
}
