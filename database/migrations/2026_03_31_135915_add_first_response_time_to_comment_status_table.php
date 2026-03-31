<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFirstResponseTimeToCommentStatusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
       Schema::table('comment_status', function (Blueprint $table) {
            $table->bigInteger('first_response_time_seconds')->nullable()->after('replied_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('comment_status', function (Blueprint $table) {
            $table->dropColumn('first_response_time_seconds');
        });
    }
}
