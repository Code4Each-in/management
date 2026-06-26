<?php

namespace App\Services;

use App\Models\DeploymentNotification;
use App\Models\DeploymentTicket;
use App\Models\DeploymentBug;

class DeploymentNotifier
{
    /**
     * Create an in-app notification record for a single user.
     * Email sending is intentionally left as a hook (notify()) - wire up
     * a Laravel Notification/Mailable here later if email is required.
     */
    public static function send(
        ?int $userId,
        string $type,
        string $title,
        ?string $message = null,
        ?DeploymentTicket $ticket = null,
        ?DeploymentBug $bug = null
    ): ?DeploymentNotification {
        if (! $userId) {
            return null;
        }

        $notification = DeploymentNotification::create([
            'deployment_ticket_id' => $ticket?->id,
            'deployment_bug_id' => $bug?->id,
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
        ]);

        // Hook for email notifications (optional, per spec):
        // if ($user = \App\Models\User::find($userId)) {
        //     $user->notify(new \App\Notifications\DeploymentEmailNotification($notification));
        // }

        return $notification;
    }

    public static function sendToMany(
        array $userIds,
        string $type,
        string $title,
        ?string $message = null,
        ?DeploymentTicket $ticket = null,
        ?DeploymentBug $bug = null
    ): void {
        foreach (array_unique(array_filter($userIds)) as $userId) {
            static::send($userId, $type, $title, $message, $ticket, $bug);
        }
    }
}
