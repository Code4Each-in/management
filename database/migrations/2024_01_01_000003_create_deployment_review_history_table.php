<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeploymentReviewHistoryTable extends Migration
{
    public function up()
    {
        Schema::create('deployment_review_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('deployment_ticket_id');
            $table->unsignedBigInteger('reviewer_id');
            $table->enum('action', ['Submitted', 'Approved', 'Rejected', 'Changes Requested']);
            $table->longText('comments')->nullable(); // permanent reviewer comments
            $table->unsignedInteger('attempt_number')->default(1);
            $table->unsignedInteger('time_spent_minutes')->nullable(); // time spent in this review pass
            $table->timestamps();

            $table->foreign('deployment_ticket_id')->references('id')->on('deployment_tickets')->onDelete('cascade');
            $table->index('deployment_ticket_id');
            $table->index('reviewer_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('deployment_review_history');
    }
}
