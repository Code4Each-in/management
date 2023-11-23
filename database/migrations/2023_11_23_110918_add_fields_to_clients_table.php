<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToClientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('secondary_email')->after('email')->nullable();
            $table->string('additional_email')->after('secondary_email')->nullable();
            $table->string('source')->after('company')->nullable();
            $table->string('skype')->after('source')->nullable();
            $table->date('last_worked')->after('skype')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('clients');

    }
}
