<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FixStatusEnumInScheduledEmails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
public function up()
{
    DB::statement("ALTER TABLE scheduled_emails MODIFY COLUMN status ENUM('scheduled','sent','failed','cancelled') NOT NULL DEFAULT 'scheduled'");
}

public function down()
{
    DB::statement("ALTER TABLE scheduled_emails MODIFY COLUMN status ENUM('scheduled','sent','failed') NOT NULL DEFAULT 'scheduled'");
}
}
