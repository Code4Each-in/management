<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOutTimeDateToUserAttendancesTemporaryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_attendances_temporary', function (Blueprint $table) {
            $table->dateTime('out_time_date')->nullable()->after('in_time');
        });
    }

    /**
     * Reverse the migrations.  
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_attendances_temporary', function (Blueprint $table) {
            $table->dropColumn('out_time_date');
        });
    }
}
