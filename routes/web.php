<?php

use Illuminate\Support\Facades\Route;
use Wave\Plugins\NotificationComposer\Components\Inbox;
use Wave\Plugins\NotificationComposer\Http\Controllers\ReadNotification;

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    Route::livewire('/notifications', Inbox::class)->name('notifications');
    Route::post('/notification/read/{id}', ReadNotification::class)->name('wave.notification.read');
});
