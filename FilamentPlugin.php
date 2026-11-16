<?php

namespace Wave\Plugins\NotificationComposer;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Illuminate\Support\Facades\File;
use Wave\Plugins\NotificationComposer\Filament\Pages\Notifications;
use Wave\Plugins\PluginManager;

class FilamentPlugin implements Plugin
{
    /** Register only when enabled, using the stock Wave installed-plugin file. */
    public static function active(): array
    {
        $path = PluginManager::path().'/installed.json';

        return File::exists($path) && in_array('notification-composer', File::json($path), true)
            ? [new static]
            : [];
    }

    public function getId(): string
    {
        return 'notification-composer';
    }

    public function register(Panel $panel): void
    {
        $panel->pages([Notifications::class]);
    }

    public function boot(Panel $panel): void {}
}
