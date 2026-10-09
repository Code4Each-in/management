<?php

namespace Tests\Feature\Upwork;

use App\Models\UpworkOpportunity;
use App\Models\Users;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The PMS migration history contains MySQL-only statements, so these tests do not
 * run the full migration set. They build a minimal schema for the tables the
 * Upwork module touches and then run only the Upwork migrations.
 *
 * Run with:  DB_CONNECTION=sqlite DB_DATABASE=:memory: vendor/bin/phpunit tests/Feature/Upwork
 */
abstract class UpworkTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Guard first: nothing may touch the database before this check.
        $this->guardAgainstRealDatabase();
        $this->createMinimalSchema();

        // Fixed clock: fixtures carry real posting dates, and the stale-job filter is age-based.
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-10-08 12:00:00', 'UTC'));

        DB::beginTransaction();
        $this->beforeApplicationDestroyed(function () {
            DB::rollBack();
        });
    }

    protected function guardAgainstRealDatabase(): void
    {
        $connection = config('database.default');
        $database = (string) config("database.connections.{$connection}.database");

        $isMemorySqlite = $connection === 'sqlite' && $database === ':memory:';
        $isTestDatabase = stripos($database, 'test') !== false;

        if (!$isMemorySqlite && !$isTestDatabase) {
            $this->markTestSkipped("Refusing to run against database '{$database}'. Use DB_CONNECTION=sqlite DB_DATABASE=:memory: or a *_test database.");
        }
    }

    protected function createMinimalSchema(): void
    {
        if (!Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // isAdmin() means role_id === 1, so reserve id 1 for Super Admin before any other role.
        if (!DB::table('roles')->where('id', 1)->exists()) {
            DB::table('roles')->insert(['id' => 1, 'name' => 'Super Admin', 'created_at' => now(), 'updated_at' => now()]);
        }

        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('first_name');
                $table->string('last_name');
                $table->string('email')->unique();
                $table->string('password');
                $table->integer('role_id')->nullable();
                $table->string('designation')->nullable();
                $table->string('gender')->nullable();
                $table->string('profile_picture')->nullable();
                $table->tinyInteger('status')->default(1);
                $table->rememberToken();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('modules')) {
            Schema::create('modules', function (Blueprint $table) {
                $table->id();
                $table->integer('page_id')->nullable();
                $table->string('module_name')->nullable();
                $table->string('route_name')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('role_permissions')) {
            Schema::create('role_permissions', function (Blueprint $table) {
                $table->id();
                $table->integer('role_id');
                $table->integer('module_id');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('personal_access_tokens')) {
            Schema::create('personal_access_tokens', function (Blueprint $table) {
                $table->id();
                $table->morphs('tokenable');
                $table->string('name');
                $table->string('token', 64)->unique();
                $table->text('abilities')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamps();
            });
        }

        // Tables queried by the layout header (left empty).
        if (!Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table) {
                $table->id();
                $table->timestamps();
            });
        }
        if (!Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->id();
                $table->integer('user_id')->nullable();
                $table->boolean('is_read')->default(false);
                $table->timestamps();
                $table->softDeletes();
            });
        }
        if (!Schema::hasTable('project_messages')) {
            Schema::create('project_messages', function (Blueprint $table) {
                $table->id();
                $table->integer('to')->nullable();
                $table->integer('is_read_to')->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('upwork_opportunities')) {
            // Every Upwork migration, in filename (= run) order.
            $files = glob(database_path('migrations/*_upwork_*.php'));
            sort($files);
            foreach ($files as $file) {
                require_once $file;
                $class = \Illuminate\Support\Str::studly(implode('_', array_slice(explode('_', basename($file, '.php')), 4)));
                (new $class)->up();
            }
        }
    }

    protected function makeRole(string $name): int
    {
        return DB::table('roles')->insertGetId(['name' => $name, 'created_at' => now(), 'updated_at' => now()]);
    }

    protected function makeUser(array $attributes = []): Users
    {
        static $n = 0;
        $n++;

        $user = new Users();
        $user->forceFill(array_merge([
            'first_name' => 'Test',
            'last_name' => 'User' . $n,
            'email' => "user{$n}_" . uniqid() . '@example.test',
            'password' => 'secret-password',
            'status' => 1,
        ], $attributes))->save();

        return $user;
    }

    protected function superAdmin(): Users
    {
        return $this->makeUser(['role_id' => 1]);
    }

    protected function bde(): Users
    {
        return $this->makeUser(['role_id' => $this->makeRole('Employee'), 'designation' => 'BDE']);
    }

    protected function developer(): Users
    {
        return $this->makeUser(['role_id' => $this->makeRole('Employee'), 'designation' => 'Developer']);
    }

    protected function payload(array $overrides = []): array
    {
        return array_merge([
            'upwork_job_id' => '~01abc123def456',
            'upwork_url' => 'https://www.upwork.com/jobs/~01abc123def456',
            'title' => 'Laravel + AI automation for SaaS dashboard',
            'description' => "We need an experienced Laravel team.\nAI integrations a plus.",
            'job_type' => 'hourly',
            'hourly_min' => 40,
            'hourly_max' => 70,
            'experience_level' => 'Expert',
            'duration' => '3 to 6 months',
            'client_country' => 'United States',
            'client_timezone' => 'America/New_York',
            'client_rating' => 4.9,
            'client_reviews_count' => 31,
            'client_total_spent' => 125000,
            'client_hire_rate' => 78,
            'client_jobs_posted' => 40,
            'client_jobs_hired' => 31,
            'payment_verified' => true,
            'proposals_count' => 12,
            'interviews_count' => 1,
            'connects_required' => 16,
            'posted_at' => '2026-10-06T10:00:00Z',
            'score' => 86,
            'technical_fit_score' => 18,
            'experience_fit_score' => 17,
            'client_quality_score' => 19,
            'commercial_value_score' => 13,
            'long_term_score' => 9,
            'ai_automation_score' => 8,
            'competition_score' => 2,
            'scoring_version' => '1',
            'recommendation' => 'APPLY',
            'match_summary' => 'Strong Laravel + OpenAI fit.',
            'relevant_experience' => 'Built multiple Laravel SaaS dashboards.',
            'risks' => 'Tight timeline.',
            'technologies' => ['Laravel', 'PHP', 'OpenAI'],
            'raw_data' => ['source' => 'test'],
        ], $overrides);
    }

    protected function opportunity(array $attributes = []): UpworkOpportunity
    {
        static $n = 0;
        $n++;

        $opportunity = new UpworkOpportunity(array_merge([
            'upwork_job_id' => 'job' . $n . uniqid(),
            'title' => 'Opportunity ' . $n,
            'score' => 75,
            'recommendation' => 'review',
        ], $attributes));
        if (isset($attributes['status'])) {
            $opportunity->status = $attributes['status'];
        }
        $opportunity->save();

        return $opportunity;
    }
}
