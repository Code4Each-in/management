<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRequestColumnsToProjectLogSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
   public function up()
{
    Schema::table('project_log_settings', function (Blueprint $table) {

        $table->enum('request_status', ['pending','approved','rejected'])
              ->default('approved')
              ->after('enabled');

        $table->boolean('requested_enabled')
              ->nullable()
              ->after('request_status');

    });
}


    /**
     * Reverse the migrations.
     *
     * @return void
     */
public function down()
{
    Schema::table('project_log_settings', function (Blueprint $table) {

        $table->dropColumn('request_status');
        $table->dropColumn('requested_enabled');

    });
}
}
