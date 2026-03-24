<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProjectIdToScheduledEmailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('scheduled_emails', function (Blueprint $table) {
            $table->foreignId('project_id')
                  ->nullable()
                  ->after('template_id')
                  ->constrained('projects')
                  ->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('scheduled_emails', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropColumn('project_id');
        });
    }
}
