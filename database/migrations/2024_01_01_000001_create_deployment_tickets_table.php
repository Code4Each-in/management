<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeploymentTicketsTable extends Migration
{
    public function up()
    {
        Schema::create('deployment_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('deployment_code')->unique(); // Auto generated e.g. DEP-0001

            // Basic Information
            $table->string('deployment_name');
            $table->unsignedBigInteger('project_id');
            $table->string('related_ticket_ids')->nullable(); // comma separated / free text ref to PMS tickets/tasks
            $table->unsignedBigInteger('created_by'); // developer who created it
            $table->unsignedBigInteger('assigned_developer_id')->nullable();
            $table->unsignedBigInteger('reviewer_id')->nullable();
            $table->unsignedBigInteger('qa_tester_id')->nullable();
            $table->enum('priority', ['Low', 'Medium', 'High', 'Critical'])->default('Medium');

            // Deployment Details
            $table->longText('changes_done')->nullable();
            $table->longText('files_modified')->nullable();
            $table->longText('modules_affected')->nullable();
            $table->longText('testing_done')->nullable();
            $table->longText('deployment_notes')->nullable();

            // Database Changes
            $table->boolean('db_changes_required')->default(false);
            $table->longText('migration_details')->nullable();

            // Version Details
            $table->string('current_version')->nullable();
            $table->string('new_version')->nullable();
            $table->date('deployment_date')->nullable();

            // Workflow
            $table->enum('status', [
                'Draft',
                'Review Pending',
                'Review In Progress',
                'Changes Requested',
                'Review Approved',
                'Review Rejected',
                'Testing In Progress',
                'Testing Failed',
                'Testing Passed',
                'Ready For Deployment',
                'Deployment Approved',
                'Deployment Rejected',
                'Deployed',
                'Rollback Required',
                'Rolled Back',
            ])->default('Draft');

            // Approval gates
            $table->boolean('code_review_approved')->default(false);
            $table->boolean('qa_approved')->default(false);

            // Review attempt tracking
            $table->unsignedInteger('review_attempts')->default(0);
            $table->timestamp('first_submitted_for_review_at')->nullable();
            $table->timestamp('review_completed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('project_id');
            $table->index('assigned_developer_id');
            $table->index('reviewer_id');
            $table->index('qa_tester_id');
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('deployment_tickets');
    }
}
