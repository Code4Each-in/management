<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeploymentBugsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('deployment_bugs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deployment_ticket_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('severity')->default('Medium'); // Low, Medium, High
            $table->string('screenshot')->nullable();
            $table->string('status')->default('open'); // open, fixed, closed
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('deployment_bugs');
    }
}
