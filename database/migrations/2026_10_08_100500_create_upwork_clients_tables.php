<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Client intelligence: Upwork clients identified by Upwork's own client id,
 * a snapshot each time their reported figures change, and a link from each
 * opportunity. (Separate from the PMS `clients` table, which holds Code4Each
 * customers.) Additive only.
 */
class CreateUpworkClientsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('upwork_clients', function (Blueprint $table) {
            $table->id();
            $table->string('external_id', 64)->unique(); // Upwork clientCompanyPublic.id
            $table->string('country', 100)->nullable();
            $table->string('timezone', 100)->nullable();
            // Latest figures Upwork reported (overwritten on each enrichment; history is in snapshots).
            $table->decimal('rating', 3, 2)->nullable();
            $table->unsignedInteger('reviews_count')->nullable();
            $table->decimal('total_spent', 14, 2)->nullable();
            $table->decimal('hire_rate', 5, 2)->nullable();
            $table->unsignedInteger('jobs_posted')->nullable();
            $table->unsignedInteger('jobs_hired')->nullable();
            $table->boolean('payment_verified')->nullable();
            $table->unsignedInteger('open_jobs')->nullable();
            $table->json('work_history')->nullable(); // Upwork-reported recent contracts: title, type, status, dates, score (no reviewer names)
            $table->decimal('last_client_score', 5, 2)->nullable();
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });

        Schema::create('upwork_client_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('upwork_client_id')->constrained('upwork_clients')->onDelete('cascade');
            $table->foreignId('opportunity_id')->nullable()->constrained('upwork_opportunities')->nullOnDelete();
            $table->decimal('rating', 3, 2)->nullable();
            $table->unsignedInteger('reviews_count')->nullable();
            $table->decimal('total_spent', 14, 2)->nullable();
            $table->decimal('hire_rate', 5, 2)->nullable();
            $table->unsignedInteger('jobs_posted')->nullable();
            $table->unsignedInteger('jobs_hired')->nullable();
            $table->timestamp('recorded_at')->useCurrent();

            $table->index(['upwork_client_id', 'recorded_at']);
        });

        Schema::table('upwork_opportunities', function (Blueprint $table) {
            $table->foreignId('upwork_client_id')->nullable()->after('upwork_url')->constrained('upwork_clients')->nullOnDelete();
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
            $table->dropForeign(['upwork_client_id']);
            $table->dropColumn('upwork_client_id');
        });
        Schema::dropIfExists('upwork_client_snapshots');
        Schema::dropIfExists('upwork_clients');
    }
}
