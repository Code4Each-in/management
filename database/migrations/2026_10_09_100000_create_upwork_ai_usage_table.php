<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every AI request: reserved worst-case cost before the call, actual tokens and
 * estimated cost after it. The budget guard sums this table, so it is the
 * source of truth for AI limits and cost reporting. Additive only.
 */
class CreateUpworkAiUsageTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('upwork_ai_usage', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 30);
            $table->string('model', 60);
            $table->string('request_type', 40);              // qualify, deep_analysis, ...
            $table->string('prompt_version', 40)->nullable();
            $table->foreignId('opportunity_id')->nullable()->constrained('upwork_opportunities')->nullOnDelete();
            $table->string('status', 20)->default('reserved'); // reserved, succeeded, failed, abandoned
            $table->unsignedTinyInteger('attempt')->default(1);
            $table->decimal('reserved_cost_usd', 12, 6)->default(0);   // worst case, held until the call finishes
            $table->unsignedInteger('reserved_tokens')->default(0);
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('cached_input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->unsignedInteger('reasoning_tokens')->nullable();
            $table->decimal('estimated_cost_usd', 12, 6)->nullable(); // from actual tokens x configured prices
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('error_code', 60)->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index('created_at');
            $table->index('status');
            $table->index(['opportunity_id', 'request_type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('upwork_ai_usage');
    }
}
