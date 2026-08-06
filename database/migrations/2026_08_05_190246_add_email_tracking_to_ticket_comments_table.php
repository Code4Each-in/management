<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEmailTrackingToTicketCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
        public function up()
        {
            Schema::table('ticket_comments', function (Blueprint $table) {

                $table->string('reply_token',100)
                    ->nullable()
                    ->after('reply_to');

                $table->string('email_message_id',255)
                    ->nullable()
                    ->after('reply_token');

            });
        }


        public function down()
        {
            Schema::table('ticket_comments', function (Blueprint $table) {

                $table->dropColumn([
                    'reply_token',
                    'email_message_id'
                ]);

            });
        }
}
