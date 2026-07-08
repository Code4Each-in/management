<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeploymentAttachmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('deployment_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deployment_ticket_id')->constrained()->onDelete('cascade');
            $table->string('type'); // Screenshot, Document, SQL, Other
            $table->string('file_path');
            $table->string('original_name')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('deployment_attachments');
    }
}
