<?php

namespace Tests\Feature\Upwork;

use App\Models\UpworkSetting;
use Database\Seeders\UpworkIntelligenceSeeder;
use Illuminate\Support\Facades\Http;

/** Phase 11: deployment preflight (read-only, no secrets printed, no external calls). */
class UpworkDeploymentTest extends UpworkTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        (new UpworkIntelligenceSeeder)->run();
        Http::fake();

        // Record the Upwork migrations as applied (the test schema is built without the migrator).
        \Schema::create('migrations', function ($t) { $t->increments('id'); $t->string('migration'); $t->integer('batch'); });
        if (!\Schema::hasTable('failed_jobs')) { // a standard Laravel table the PMS already has
            \Schema::create('failed_jobs', function ($t) { $t->id(); $t->string('uuid')->unique(); $t->text('connection'); $t->text('queue'); $t->longText('payload'); $t->longText('exception'); $t->timestamp('failed_at')->useCurrent(); });
        }
        foreach (glob(database_path('migrations/*_upwork_*.php')) as $file) {
            \DB::table('migrations')->insert(['migration' => basename($file, '.php'), 'batch' => 1]);
        }
    }

    public function test_preflight_passes_on_a_seeded_install_without_external_calls(): void
    {
        config(['upwork.ai.providers.openai.api_key' => 'sk-secret-value']);

        $this->artisan('upwork:preflight')
            ->expectsOutput('  · OPENAI_API_KEY: set')
            ->expectsOutput('Preflight passed.')
            ->assertExitCode(0);

        Http::assertNothingSent();
    }

    public function test_preflight_flags_unsafe_production_settings(): void
    {
        UpworkSetting::set('ai.provider', 'fake');
        UpworkSetting::set('discovery.client', 'fixture');
        UpworkSetting::set('ai.daily_budget_usd', 9);
        $this->app['env'] = 'production';

        $this->artisan('upwork:preflight')->assertExitCode(1);
    }

    public function test_preflight_reports_pending_migrations(): void
    {
        \DB::table('migrations')->where('migration', 'like', '%create_upwork_queue_jobs_table')->delete();

        $this->artisan('upwork:preflight')->assertExitCode(1);
    }
}
