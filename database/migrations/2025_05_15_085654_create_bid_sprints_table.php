<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBidSprintsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
 public function up()
{
    Schema::create('bid_sprints', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->dateTime('start_date')->nullable();
        $table->dateTime('end_date')->nullable();
        $table->unsignedTinyInteger('status')->default(1);
        $table->longText('description')->nullable();
        $table->timestamps();
        $table->softDeletes(); 
    });
}



    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bid_sprints');
    }
}
