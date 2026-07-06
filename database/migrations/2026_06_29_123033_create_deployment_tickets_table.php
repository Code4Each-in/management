<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeploymentTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('deployment_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('deployment_code')->unique();
            $table->string('deployment_name');
            $table->foreignId('project_id')->constrained();
            $table->string('related_ticket_ids')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('assigned_developer_id')->nullable()->constrained('users');
            $table->foreignId('qa_id')->nullable()->constrained('users');
            $table->string('priority')->default('Medium');

            $table->text('changes_done')->nullable();
            $table->text('files_modified')->nullable();
            $table->text('modules_affected')->nullable();
            $table->text('testing_done')->nullable();
            $table->text('deployment_notes')->nullable();

            $table->boolean('db_changes_required')->default(false);
            $table->text('migration_details')->nullable();

            $table->string('current_version')->nullable();
            $table->string('new_version')->nullable();
            $table->date('deployment_date')->nullable();

            $table->string('status')->default('draft'); // draft, deplyoment_pending, needs_fix, approved, deployed
            $table->boolean('qa_approved')->default(false);
            $table->unsignedInteger('fix_attempts')->default(0);

            $table->timestamp('first_submitted_at')->nullable();
            $table->timestamp('qa_completed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('deployment_tickets');
    }
}
