<?php

namespace Wave\Plugins\NotificationComposer\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use Wave\Plugins\NotificationComposer\Models\NotificationBroadcast;
use Wave\Plugins\NotificationComposer\Support\MessageContent;

class SiteNotification extends Notification
{
    public function __construct(public NotificationBroadcast $broadcast) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'broadcast_id' => $this->broadcast->id,
            'title' => $this->broadcast->title,
            'category' => $this->broadcast->category,
            'body' => MessageContent::plainText($this->broadcast->body),
            'rich_body' => Str::sanitizeHtml($this->broadcast->body),
            'format' => 'html',
            'user' => ['name' => config('notification-composer.team_name', config('app.name').' Team')],
            'icon' => url(config('notification-composer.team_avatar', '/storage/demo/default.png')),
        ];
    }
}
