<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCommentStatusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('comment_status', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('comment_id'); // client comment
            $table->unsignedBigInteger('ticket_id');

            $table->enum('status', ['pending','replied','acknowledged'])
                ->default('pending');

            $table->unsignedBigInteger('replied_by')->nullable();
            $table->timestamp('replied_at')->nullable();

            $table->unsignedBigInteger('acknowledged_by')->nullable();
            $table->timestamp('acknowledged_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('comment_status');
    }
}
