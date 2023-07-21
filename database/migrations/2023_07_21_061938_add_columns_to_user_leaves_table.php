<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToUserLeavesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_leaves', function (Blueprint $table) {
            $table->string('half_day')->nullable();
            $table->float('leave_day_count',8,1)->nullable();
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
            $table->dropColumn('half_day');
            $table->dropColumn('leave_day_count');

        });
    }
}
