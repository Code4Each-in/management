<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketComments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ticket_comments', function (Blueprint $table) {
            $table->id();
            $table->integer('ticket_id')->constrained();
            $table->text('comments')->nullable();
            $table->integer('comment_by')->constrained();
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
        Schema::dropIfExists('ticket_comments');
    }
}