<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('project_clients', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('client_id');
            $table->timestamps();
            $table->unique(['project_id', 'client_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('project_clients');
    }
};

