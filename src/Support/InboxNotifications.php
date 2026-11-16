<?php

namespace Wave\Plugins\NotificationComposer\Support;

use Illuminate\Support\Str;
use Wave\Plugins\NotificationComposer\Notifications\SiteNotification;

class InboxNotifications
{
    public static function isBroadcast(string $type): bool
    {
        return in_array($type, [SiteNotification::class, ...config('notification-composer.legacy_broadcast_types', [])], true);
    }

    public static function markAsRead(string $id): bool
    {
        $notification = Str::isUuid($id) ? auth()->user()?->notifications()->find($id) : null;
        if (! $notification) {
            return false;
        }
        if (self::isBroadcast($notification->type)) {
            $notification->markAsRead();
        } else {
            $notification->delete();
        }
        app(UnreadCount::class)->forget();

        return true;
    }
}
