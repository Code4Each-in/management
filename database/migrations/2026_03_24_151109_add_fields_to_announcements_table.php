<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToAnnouncementsTable extends Migration
{
    public function up()
{
    Schema::table('announcements', function (Blueprint $table) {
        $table->date('end_date')->nullable()->after('message');
        $table->boolean('show_to_client')->default(0)->after('end_date');
    });
}

public function down()
{
    Schema::table('announcements', function (Blueprint $table) {
        $table->dropColumn(['end_date', 'show_to_client']);
    });
}
}
