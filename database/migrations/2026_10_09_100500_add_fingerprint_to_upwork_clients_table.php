<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Client fingerprint (country, jobs posted, hires, total spent), so a search
 * row without Upwork's client id can still be matched to a known client.
 * Additive only.
 */
class AddFingerprintToUpworkClientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('upwork_clients', function (Blueprint $table) {
            $table->char('fingerprint', 40)->nullable()->after('external_id');
            $table->index('fingerprint');
        });

        // Backfill from the latest known figures.
        foreach (DB::table('upwork_clients')->get(['id', 'country', 'jobs_posted', 'jobs_hired', 'total_spent']) as $c) {
            $fingerprint = \App\Services\Upwork\Clients\ClientIntelligenceService::fingerprint([
                'client_country' => $c->country, 'client_jobs_posted' => $c->jobs_posted,
                'client_jobs_hired' => $c->jobs_hired, 'client_total_spent' => $c->total_spent,
            ]);
            if ($fingerprint) {
                DB::table('upwork_clients')->where('id', $c->id)->update(['fingerprint' => $fingerprint]);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('upwork_clients', function (Blueprint $table) {
            $table->dropIndex(['fingerprint']);
            $table->dropColumn('fingerprint');
        });
    }
}
