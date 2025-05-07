<?php
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

class UpdateTypeEnumOnRemindersTable extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE reminders MODIFY COLUMN type ENUM('daily', 'weekly', 'monthly', 'custom') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE reminders MODIFY COLUMN type ENUM('daily', 'weekly', 'monthly') NOT NULL");
    }
}
