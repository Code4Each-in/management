<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scheduled_emails', function (Blueprint $table) {
            $table->string('from_email')->nullable()->after('subject');
            $table->string('from_name')->nullable()->after('from_email');
            $table->string('reply_to')->nullable()->after('from_name');
            $table->text('cc_email')->nullable()->after('reply_to');
            $table->text('bcc_email')->nullable()->after('cc_email');
        });
    }

    public function down(): void
    {
        Schema::table('scheduled_emails', function (Blueprint $table) {
            $table->dropColumn(['from_email', 'from_name', 'reply_to', 'cc_email', 'bcc_email']);
        });
    }
};
