<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('email_message_map', function (Blueprint $table) {

            $table->id();

            $table->unsignedBigInteger('ticket_comment_id');

            $table->string('recipient_email')->nullable();

            $table->string('email_message_id')->unique();

            $table->timestamps();


            $table->index('ticket_comment_id');

        });
    }

    public function down()
    {
        Schema::dropIfExists('email_message_map');
    }
};
