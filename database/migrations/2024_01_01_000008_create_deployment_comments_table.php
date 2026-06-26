<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeploymentCommentsTable extends Migration
{
    public function up()
    {
        Schema::create('deployment_comments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('deployment_ticket_id');
            $table->unsignedBigInteger('deployment_bug_id')->nullable(); // null = ticket-level comment
            $table->unsignedBigInteger('user_id');
            $table->longText('comment');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('deployment_ticket_id')->references('id')->on('deployment_tickets')->onDelete('cascade');
            $table->foreign('deployment_bug_id')->references('id')->on('deployment_bugs')->onDelete('cascade');
            $table->index('deployment_ticket_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('deployment_comments');
    }
}
