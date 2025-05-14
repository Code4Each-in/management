<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFromAndToToUserLeavesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_leaves', function (Blueprint $table) {
            $table->time('from_time')->nullable()->after('half_day');
            $table->time('to_time')->nullable()->after('from_time');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_leaves', function (Blueprint $table) {
            $table->dropColumn(['from_time', 'to_time']);
        });
    }
}
