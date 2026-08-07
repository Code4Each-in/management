<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropAssignedDeveloperIdFromDeploymentTickets extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('deployment_tickets', function (Blueprint $table) {
            $table->dropForeign(['assigned_developer_id']);
            $table->dropColumn('assigned_developer_id');
        });
    }

    /**
     * Reverse the migrations.  
     *
     * @return void
     */
    public function down()
    {
        Schema::table('deployment_tickets', function (Blueprint $table) {
            //
        });
    }
}
