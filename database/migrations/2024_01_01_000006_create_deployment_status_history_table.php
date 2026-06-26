<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeploymentStatusHistoryTable extends Migration
{
    public function up()
    {
        Schema::create('deployment_status_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('deployment_ticket_id');
            $table->unsignedBigInteger('changed_by');
            $table->string('field_changed'); // e.g. status, reviewer_id, assigned_developer_id, qa_tester_id
            $table->string('old_value')->nullable();
            $table->string('new_value')->nullable();
            $table->timestamps();

            $table->foreign('deployment_ticket_id')->references('id')->on('deployment_tickets')->onDelete('cascade');
            $table->index('deployment_ticket_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('deployment_status_history');
    }
}
