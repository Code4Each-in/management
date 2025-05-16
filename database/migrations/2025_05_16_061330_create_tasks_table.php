<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
   public function up()
{
   Schema::create('tasks', function (Blueprint $table) {
    $table->id();
    $table->string('job_title');
    $table->string('job_link')->nullable();
    $table->string('source')->nullable();
    $table->string('profile')->nullable();
    $table->enum('status', ['applied', 'viewed', 'replied', 'success'])->default('applied');
    $table->timestamps();
    $table->softDeletes(); // ← Soft delete column
});
}

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tasks');
    }
}
