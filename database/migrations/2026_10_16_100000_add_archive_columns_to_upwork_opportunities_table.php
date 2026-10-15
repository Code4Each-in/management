<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Archiving: a job leaves the active review queue and every reminder without
 * being approved, rejected or deleted. The status itself becomes "archived"
 * (status is a varchar, no type change); these columns record when, by whom,
 * and which status a restore returns to. Additive and nullable: no backfill,
 * no existing row changes.
 */
class AddArchiveColumnsToUpworkOpportunitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('upwork_opportunities', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('reviewed_at');
            $table->unsignedBigInteger('archived_by')->nullable()->after('archived_at');
            $table->string('archived_from_status', 30)->nullable()->after('archived_by');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('upwork_opportunities', function (Blueprint $table) {
            $table->dropColumn(['archived_at', 'archived_by', 'archived_from_status']);
        });
    }
}
