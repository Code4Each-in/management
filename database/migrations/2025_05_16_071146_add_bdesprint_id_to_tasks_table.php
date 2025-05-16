<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBdesprintIdToTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
      public function up()
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->unsignedBigInteger('bdesprint_id')->after('id')->nullable();

            $table->foreign('bdesprint_id')->references('id')->on('bid_sprints')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
     public function down()
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['bdesprint_id']);
            $table->dropColumn('bdesprint_id');
        });
    }
}
