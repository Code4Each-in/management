<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCustomDateToRemindersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
{
    Schema::table('reminders', function (Blueprint $table) {
        $table->date('custom_date')->nullable()->after('reminder_date');
    });
}

public function down(): void
{
    Schema::table('reminders', function (Blueprint $table) {
        $table->dropColumn('custom_date');
    });
}

}
