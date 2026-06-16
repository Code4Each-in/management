<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddResponseby extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('comment_status', function (Blueprint $table) {
          $table->unsignedBigInteger('response_by')->nullable()->after('acknowledged_at');        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('comment_status', function (Blueprint $table) {
            $table->dropForeign(['response_by']);
        });
    }
}
