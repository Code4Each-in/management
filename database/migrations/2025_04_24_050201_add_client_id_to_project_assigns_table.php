<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddClientIdToProjectAssignsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('project_assigns', function (Blueprint $table) {
            $table->unsignedBigInteger('client_id')->nullable()->after('user_id');

            // Optional: if there's a clients table, add foreign key constraint
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('project_assigns', function (Blueprint $table) {
            //
        });
    }
}
