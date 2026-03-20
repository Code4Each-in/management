<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTimestampsToScheduledEmailRecipients extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
public function up()
{
    Schema::table('scheduled_email_recipients', function (Blueprint $table) {
        $table->timestamps();
    });
}

public function down()
{
    Schema::table('scheduled_email_recipients', function (Blueprint $table) {
        $table->dropTimestamps();
    });
}
}
