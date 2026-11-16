<?php

namespace Wave\Plugins\NotificationComposer\Components;

use Livewire\Component;
use Wave\Plugins\NotificationComposer\Support\InboxNotifications;
use Wave\Plugins\NotificationComposer\Support\UnreadCount;

class Inbox extends Component
{
    public function markAsRead(string $id): void
    {
        if (InboxNotifications::markAsRead($id)) {
            $this->dispatch('notification-count-updated', count: app(UnreadCount::class)->get());
        }
    }

    public function render()
    {
        return view('notification-composer::inbox', ['notifications' => auth()->user()->unreadNotifications()->latest()->get()])
            ->layout(config('notification-composer.layout', 'theme::components.layouts.app'));
    }
}
