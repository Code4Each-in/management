<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeploymentBugsTable extends Migration
{
    public function up()
    {
        Schema::create('deployment_bugs', function (Blueprint $table) {
            $table->id();
            $table->string('bug_code')->unique(); // e.g. BUG-0001
            $table->unsignedBigInteger('deployment_ticket_id');
            $table->string('title');
            $table->longText('description')->nullable();
            $table->enum('severity', ['Low', 'Medium', 'High', 'Critical'])->default('Medium');
            $table->unsignedBigInteger('assigned_developer_id')->nullable();
            $table->unsignedBigInteger('reported_by'); // QA tester
            $table->longText('steps_to_reproduce')->nullable();
            $table->string('screenshot_path')->nullable();
            $table->enum('status', [
                'Open',
                'In Progress',
                'Fixed',
                'Ready For Retest',
                'Retest Required',
                'Closed',
                'Reopened',
            ])->default('Open');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('deployment_ticket_id')->references('id')->on('deployment_tickets')->onDelete('cascade');
            $table->index('deployment_ticket_id');
            $table->index('assigned_developer_id');
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('deployment_bugs');
    }
}
