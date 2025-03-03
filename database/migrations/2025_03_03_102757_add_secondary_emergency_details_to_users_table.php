<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSecondaryEmergencyDetailsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('users', function (Blueprint $table) {
            $table->string('emergency_name_secondary')->nullable();
            $table->string('emergency_relation_secondary')->nullable();
            $table->string('emergency_phone_secondary')->nullable();
        });
    }

    public function down() {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['emergency_name_secondary', 'emergency_relation_secondary', 'emergency_phone_secondary']);
        });
    }
}
