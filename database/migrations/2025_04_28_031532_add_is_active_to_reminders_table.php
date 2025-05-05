<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsActiveToRemindersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('reminders', function (Blueprint $table) {
            $table->boolean('is_active')->default(false);
        });
    }

    public function down()
    {
        Schema::table('reminders', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }

}
