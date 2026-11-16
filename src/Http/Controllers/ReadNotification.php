<?php

namespace Wave\Plugins\NotificationComposer\Http\Controllers;

use Illuminate\Http\Request;
use Wave\Plugins\NotificationComposer\Support\InboxNotifications;

class ReadNotification
{
    public function __invoke(Request $request, string $id)
    {
        if (! InboxNotifications::markAsRead($id)) {
            return response()->json(['type' => 'error', 'message' => 'Could not find the specified notification.']);
        }

        return response()->json(['type' => 'success', 'message' => 'Marked Notification as Read', 'listid' => $request->listid]);
    }
}
