<?php

namespace Wave\Plugins\NotificationComposer;

use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Wave\Plugins\NotificationComposer\Components\Inbox;
use Wave\Plugins\NotificationComposer\Support\UnreadCount;
use Wave\Plugins\Plugin;

class NotificationComposerPlugin extends Plugin
{
    protected $name = 'Notification Composer';

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/config/notification-composer.php', 'notification-composer');
        $this->app->scoped(UnreadCount::class);
        $this->app->instance('notification-composer', true);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/resources/views', 'notification-composer');
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');
        Livewire::component('notification-composer.inbox', Inbox::class);
    }

    public function getPluginInfo(): array
    {
        return ['name' => $this->name, 'description' => 'Compose site-wide in-app announcements with a shared team identity.', 'version' => File::json(__DIR__.'/version.json')];
    }
}
