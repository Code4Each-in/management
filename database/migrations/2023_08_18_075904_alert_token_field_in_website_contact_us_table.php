<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlertTokenFieldInWebsiteContactUsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('website_contact_us', function (Blueprint $table) {
            $table->after('message', function ($table) {
            $table->string('ipaddress')->nullable();
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
        Schema::table('website_contact_us', function (Blueprint $table) {
            //
        });
    }
}
