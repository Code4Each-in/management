<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTimeEstimationToTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
       public function up()
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->decimal('time_estimation', 5, 2)->nullable()->after('ticket_category');
            // Allows values like 0.25, 1.5, 2.75, etc.
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
      public function down()
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('time_estimation');
        });
    }
}
