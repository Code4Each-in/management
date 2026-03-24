<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReplyToInTicketComments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ticket_comments', function (Blueprint $table) {
            $table->unsignedBigInteger('reply_to')->nullable()->after('comment_by');
        });
    }

    public function down()
    {
        Schema::table('ticket_comments', function (Blueprint $table) {
            $table->dropColumn('reply_to');
        });
    }
}
