<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stored AI qualifications (every answer is kept, so a regenerated analysis
 * never overwrites the previous one) and the opportunity's current AI state.
 * Additive only.
 */
class CreateUpworkAiAnalysesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('upwork_ai_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opportunity_id')->constrained('upwork_opportunities')->onDelete('cascade');
            $table->foreignId('usage_id')->nullable()->constrained('upwork_ai_usage')->nullOnDelete(); // the successful call
            $table->string('request_type', 30);      // qualify, deep
            $table->string('provider', 30);
            $table->string('model', 60);
            $table->string('prompt_version', 30);
            $table->char('input_hash', 64);          // prompt version + model + job input; same hash = same answer
            $table->string('status', 20);            // succeeded, failed
            $table->json('result')->nullable();      // validated structured answer
            $table->decimal('confidence', 4, 3)->nullable();
            $table->unsignedTinyInteger('fit_score')->nullable();
            $table->string('recommendation', 10)->nullable();
            $table->string('error_code', 40)->nullable();
            $table->text('error')->nullable();
            $table->unsignedBigInteger('requested_by')->nullable(); // null = automatic
            $table->timestamps();

            $table->index(['opportunity_id', 'request_type', 'input_hash'], 'upwork_ai_analyses_opp_type_hash_index');
        });

        Schema::table('upwork_opportunities', function (Blueprint $table) {
            $table->string('ai_status', 20)->nullable()->after('notified_level'); // queued, analyzed, skipped, failed, blocked
            $table->string('ai_status_reason', 255)->nullable()->after('ai_status');
            $table->unsignedBigInteger('ai_analysis_id')->nullable()->after('ai_status_reason');
            $table->decimal('ai_confidence', 4, 3)->nullable()->after('ai_analysis_id');
            $table->timestamp('ai_analyzed_at')->nullable()->after('ai_confidence');
            $table->char('ai_input_hash', 64)->nullable()->after('ai_analyzed_at');
            $table->index('ai_status');
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
            $table->dropIndex(['ai_status']);
            $table->dropColumn(['ai_status', 'ai_status_reason', 'ai_analysis_id', 'ai_confidence', 'ai_analyzed_at', 'ai_input_hash']);
        });
        Schema::dropIfExists('upwork_ai_analyses');
    }
}
