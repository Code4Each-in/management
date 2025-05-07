<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateMessagesReadStatusColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('project_messages', function (Blueprint $table) {
            if (Schema::hasColumn('project_messages', 'is_read')) {
                $table->dropColumn('is_read');
            }
            $table->boolean('is_read_from')->default(0);
            $table->boolean('is_read_to')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('project_messages', function (Blueprint $table) {
            $table->boolean('is_read')->default(0);
            $table->dropColumn('is_read_from');
            $table->dropColumn('is_read_to');
        });
    }
}
