<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectLogsTable extends Migration
{
    public function up()
    {
        Schema::create('project_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->string('type'); 
            $table->text('message');
            $table->timestamp('logged_at')->nullable();
            $table->timestamps();

            $table->index('project_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('project_logs');
    }
}
