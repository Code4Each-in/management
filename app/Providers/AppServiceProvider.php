<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\Notification;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        View::composer('includes.header', function ($view) {
            if (auth()->check()) {
                $userId = auth()->id();
                $unreadCount = Notification::where('user_id', $userId)->where('is_read', false)->count();
                $notifications = Notification::where('user_id', $userId)
                                    ->latest()
                                    ->take(5)
                                    ->get();
    
                $view->with('unreadCount', $unreadCount)
                     ->with('notifications', $notifications);
            }
        });
    }
}
