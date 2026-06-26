<?php

namespace App\Http\Controllers;

use App\Models\DeploymentNotification;
use Illuminate\Http\Request;

class DeploymentNotificationController extends Controller
{
    public function index(Request $request)
    {
      //  dd($request->user()->id);
        $notifications = DeploymentNotification::where('user_id', $request->user()->id)
            ->with(['ticket', 'bug'])
            ->orderByDesc('id')
            ->paginate(20);
         //dd($notifications);   

        return view('deployment.notifications.index', compact('notifications'));
    }

    public function markAsRead(Request $request, DeploymentNotification $notification)
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        $notification->markAsRead();

        return back();
    }

    public function markAllAsRead(Request $request)
    {
        DeploymentNotification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }
}
