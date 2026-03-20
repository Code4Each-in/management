<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateScheduledEmailRecipientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('scheduled_email_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scheduled_email_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->enum('status', ['pending','sent','failed'])->default('pending');
            $table->dateTime('sent_at')->nullable();
            $table->text('error')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('scheduled_email_recipients');
    }
}
