<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
		Schema::table('users', function (Blueprint $table) {
			$table->after('remember_token', function ($table) {
			$table->double('salary',8,2)->default(0.0)->nullable();
			$table->string('address')->nullable();
			$table->string('phone')->nullable();
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
			
		$table->dropColumn(['salary','address','phone']);
		});

    }
}
