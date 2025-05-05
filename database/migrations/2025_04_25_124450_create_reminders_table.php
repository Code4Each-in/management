<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRemindersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['daily', 'weekly', 'monthly']);
            $table->unsignedTinyInteger('daily_hour')->nullable();
            $table->unsignedTinyInteger('daily_minute')->nullable();

            $table->string('weekly_day')->nullable();
            $table->unsignedTinyInteger('weekly_hour')->nullable();
            $table->unsignedTinyInteger('weekly_minute')->nullable();

            $table->unsignedTinyInteger('monthly_date')->nullable();
            $table->unsignedTinyInteger('monthly_hour')->nullable();
            $table->unsignedTinyInteger('monthly_minute')->nullable();

            $table->text('description');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reminders');
    }
}
