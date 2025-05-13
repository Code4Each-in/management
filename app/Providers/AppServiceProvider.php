<?php

namespace App\Providers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\Notification;
use App\Models\Message;
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
        
                if ($userId == 1) {
                    // Super admin sees all
                    $notifications = Notification::latest()->take(5)->get();
                    $unreadCount = Notification::where('is_read', false)->count();
                } else {
                    // Other users see their own notifications only
                    $notifications = Notification::where('user_id', $userId)
                        ->latest()
                        ->take(5)
                        ->get();
        
                    $unreadCount = Notification::where('user_id', $userId)
                        ->where('is_read', false)
                        ->count();
                }
                
                $unreadMessageCount = Message::where('to', $userId)
                ->where('is_read_to', 0)
                ->count();
   
                
                $view->with('unreadCount', $unreadCount)
                     ->with('notifications', $notifications)
                     ->with('unreadMessageCount', $unreadMessageCount);
            }
        });
                            
    }
}
