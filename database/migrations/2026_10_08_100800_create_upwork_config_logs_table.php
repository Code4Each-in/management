<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Audit trail for configuration changes made in the PMS (who changed what, when). Additive only. */
class CreateUpworkConfigLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('upwork_config_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('entity', 40);              // search_profile, technology, ruleset, rule, classification, setting
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('label', 150)->nullable();  // human-readable name at the time of the change
            $table->string('action', 30);              // created, updated, enabled, disabled, deleted, published, discarded
            $table->json('changes')->nullable();       // {field: [old, new]}
            $table->timestamp('created_at')->useCurrent();

            $table->index(['entity', 'entity_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('upwork_config_logs');
    }
}
