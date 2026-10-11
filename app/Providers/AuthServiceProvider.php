<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
        \App\Models\UpworkOpportunity::class => \App\Policies\UpworkOpportunityPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // Upwork configuration pages: whether the viewer may change configuration (forms render read-only otherwise).
        \Illuminate\Support\Facades\View::composer('upwork.config.*', function ($view) {
            $view->with('canManage', (bool) optional(auth()->user())->can('manageConfig', \App\Models\UpworkOpportunity::class));
        });
    }
}
