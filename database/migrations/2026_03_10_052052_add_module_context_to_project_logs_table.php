<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddModuleContextToProjectLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('project_logs', function (Blueprint $table) {

            $table->string('module')->nullable()->after('type');

            $table->text('message')->change();

            $table->json('context')->nullable()->after('message');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('project_logs', function (Blueprint $table) {

            $table->dropColumn('module');

            $table->dropColumn('context');

        });
    }
}
