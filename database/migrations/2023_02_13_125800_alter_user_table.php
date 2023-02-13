<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
			$table->after('phone', function ($table) {
            $table->date('joining_date')->nullable();
			$table->date('birth_date')->nullable();
			$table->string('profile_picture')->nullable();
			});
			});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {	
            $table->dropColumn(['joining_date','birth_date','profile_picture']);
            });
    }
}