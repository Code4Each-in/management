<?php

namespace App\Providers;
use Illuminate\Support\Facades\DB;
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
        
                $notifications = Notification::where('user_id', $userId)
                    ->latest()
                    ->take(5)
                    ->get();
        
                $unreadCount = Notification::where('user_id', $userId)
                    ->where('is_read', false)
                    ->count();
        
                $view->with('unreadCount', $unreadCount)
                     ->with('notifications', $notifications);
            }
        });                      
    }
}
