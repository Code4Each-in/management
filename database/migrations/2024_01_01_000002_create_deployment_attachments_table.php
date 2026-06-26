<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeploymentAttachmentsTable extends Migration
{
    public function up()
    {
        Schema::create('deployment_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('deployment_ticket_id');
            $table->enum('type', ['Screenshot', 'Document', 'SQL', 'Other'])->default('Other');
            $table->string('original_name');
            $table->string('file_path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('uploaded_by');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('deployment_ticket_id')->references('id')->on('deployment_tickets')->onDelete('cascade');
            $table->index('deployment_ticket_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('deployment_attachments');
    }
}
