<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeploymentNotificationsTable extends Migration
{
    public function up()
    {
        Schema::create('deployment_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('deployment_ticket_id')->nullable();
            $table->unsignedBigInteger('deployment_bug_id')->nullable();
            $table->unsignedBigInteger('user_id'); // recipient
            $table->string('type'); // e.g. "review_approved", "bug_assigned"
            $table->string('title');
            $table->longText('message')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index('deployment_ticket_id');
            $table->index('user_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('deployment_notifications');
    }
}
