<?php

namespace Wave\Plugins\NotificationComposer\Support;

class UnreadCount
{
    private array $counts = [];

    public function get(): int
    {
        $user = auth()->user();
        if (! $user) {
            return 0;
        }

        return $this->counts[$user->getAuthIdentifier()] ??= $user->unreadNotifications()->count();
    }

    public function forget(): void
    {
        $this->counts = [];
    }
}
