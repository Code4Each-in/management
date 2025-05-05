<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReminderDateToRemindersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('reminders', function (Blueprint $table) {
            $table->timestamp('reminder_date')->nullable();  // Adjust the column type if needed
        });
    }

    public function down()
    {
        Schema::table('reminders', function (Blueprint $table) {
            $table->dropColumn('reminder_date');
        });
    }
}
