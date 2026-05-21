<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('todo_lists', function (Blueprint $table) {

            $table->unsignedBigInteger('ticket_id')->nullable()->after('user_id');

            $table->unsignedBigInteger('created_by')->nullable()->after('ticket_id');

            $table->unsignedBigInteger('completed_by')->nullable()->after('completed_at');

            // Foreign Keys

            $table->foreign('ticket_id')
                ->references('id')
                ->on('tickets')
                ->onDelete('cascade');

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->foreign('completed_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('todo_lists', function (Blueprint $table) {

            $table->dropForeign(['ticket_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['completed_by']);

            $table->dropColumn([
                'ticket_id',
                'created_by',
                'completed_by'
            ]);
        });
    }
};
