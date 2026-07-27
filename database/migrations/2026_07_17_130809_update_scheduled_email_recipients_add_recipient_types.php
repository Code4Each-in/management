<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Make client_id nullable
        DB::statement("
            ALTER TABLE scheduled_email_recipients
            MODIFY client_id BIGINT UNSIGNED NULL
        ");

        Schema::table('scheduled_email_recipients', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->after('client_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->string('email')->nullable()->after('user_id');
            $table->string('name')->nullable()->after('email');
            $table->string('recipient_type')->default('client')->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('scheduled_email_recipients', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn([
                'user_id',
                'email',
                'name',
                'recipient_type',
            ]);
        });

        DB::statement("
            ALTER TABLE scheduled_email_recipients
            MODIFY client_id BIGINT UNSIGNED NOT NULL
        ");
    }
};