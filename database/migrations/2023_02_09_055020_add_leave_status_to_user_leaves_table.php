<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLeaveStatusToUserLeavesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_leaves', function (Blueprint $table) {
        	$table->after('notes', function ($table) {
            $table->integer('status_change_by')->nullable()->constrained();
            $table->string('leave_status')->nullable();
           
        });
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
            $table->dropColumn(['status_change_by','leave_status']);
            //
        });
    }
}