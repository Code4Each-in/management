<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Drop existing FK on client_id so we can alter it
        Schema::table('scheduled_email_recipients', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
        });

        // Step 2: Make client_id nullable + add new columns
        Schema::table('scheduled_email_recipients', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable()->change();

            $table->foreignId('user_id')->nullable()->after('client_id')
                ->constrained('users')->nullOnDelete();

            $table->string('email')->nullable()->after('user_id');        // manual entries
            $table->string('name')->nullable()->after('email');           // display name for user/manual
            $table->string('recipient_type')->default('client')->after('name'); // client|user|manual
        });

        // Step 3: Re-add FK on client_id (still cascades, now nullable)
        Schema::table('scheduled_email_recipients', function (Blueprint $table) {
            $table->foreign('client_id')
                ->references('id')->on('clients')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('scheduled_email_recipients', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'email', 'name', 'recipient_type']);
        });

        Schema::table('scheduled_email_recipients', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable(false)->change();

            $table->foreign('client_id')
                ->references('id')->on('clients')
                ->cascadeOnDelete();
        });
    }
};
