<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('ticket_comments', function (Blueprint $table) {

            if(Schema::hasColumn('ticket_comments', 'email_message_id'))
            {
                $table->dropColumn('email_message_id');
            }

        });
    }

    public function down()
    {
        Schema::table('ticket_comments', function (Blueprint $table) {

            $table->string('email_message_id')->nullable();

        });
    }
};
