<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeploymentActivityLogsTable extends Migration
{
    public function up()
    {
        Schema::create('deployment_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('deployment_ticket_id'); // always tied back to the parent ticket
            $table->string('loggable_type')->nullable(); // e.g. DeploymentTicket, DeploymentBug
            $table->unsignedBigInteger('loggable_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->string('action'); // e.g. "Deployment Created", "Review Approved"
            $table->longText('old_value')->nullable();
            $table->longText('new_value')->nullable();
            $table->longText('description')->nullable();
            $table->timestamps();

            $table->foreign('deployment_ticket_id')->references('id')->on('deployment_tickets')->onDelete('cascade');
            $table->index('deployment_ticket_id');
            $table->index(['loggable_type', 'loggable_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('deployment_activity_logs');
    }
}
