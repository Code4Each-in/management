<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeploymentBugHistoryTable extends Migration
{
    public function up()
    {
        Schema::create('deployment_bug_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('deployment_bug_id');
            $table->unsignedBigInteger('changed_by');
            $table->string('old_status')->nullable();
            $table->string('new_status');
            $table->longText('remarks')->nullable();
            $table->timestamps();

            $table->foreign('deployment_bug_id')->references('id')->on('deployment_bugs')->onDelete('cascade');
            $table->index('deployment_bug_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('deployment_bug_history');
    }
}
