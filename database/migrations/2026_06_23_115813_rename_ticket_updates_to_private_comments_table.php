<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('ticket_updates', 'private_comments');
    }

    public function down(): void
    {
        Schema::rename('private_comments', 'ticket_updates');
    }
};
