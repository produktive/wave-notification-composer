<?php

use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Wave\ActivityLog;
use Wave\Plugins\NotificationComposer\Components\Inbox;
use Wave\Plugins\NotificationComposer\Filament\Pages\Notifications;
use Wave\Plugins\NotificationComposer\FilamentPlugin;
use Wave\Plugins\NotificationComposer\Jobs\DeliverNotificationBroadcast;
use Wave\Plugins\NotificationComposer\Models\NotificationBroadcast;
use Wave\Plugins\NotificationComposer\NotificationComposerPlugin;
use Wave\Plugins\NotificationComposer\Support\UnreadCount;

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

it('requires confirmation before admins queue a labeled rich announcement', function () {
    Bus::fake();
    $this->actingAs($this->admin);
    $component = Livewire::test(Notifications::class)->fillForm([
        'title' => 'Account protection', 'category' => 'security',
        'body' => '<h2>Protect your account</h2><p><strong>Enable two factor</strong> <a href="https://example.com/security">Learn more</a></p><script>alert(1)</script>',
    ])->mountAction('send')->assertActionMounted('send');
    Bus::assertNotDispatched(DeliverNotificationBroadcast::class);
    expect(NotificationBroadcast::count())->toBe(0);
    $component->callMountedAction()->assertHasNoFormErrors();
    $broadcast = NotificationBroadcast::sole();
    expect($broadcast->body)->toContain('<strong>', 'https://example.com/security')->not->toContain('<script>');
    Bus::assertDispatched(DeliverNotificationBroadcast::class, fn ($job) => $job->broadcastId === $broadcast->id);
});

it('limits composition to admins and validates labels and meaningful text', function () {
    $this->actingAs(User::factory()->create())->get('/admin/notifications')->assertForbidden();
    Livewire::test(Notifications::class)->assertForbidden();
    $this->actingAs($this->admin);
    Livewire::test(Notifications::class)->fillForm(['title' => 'News', 'category' => 'blog', 'body' => '<p>News</p>'])
        ->call('send')->assertHasFormErrors(['category']);
    Livewire::test(Notifications::class)->fillForm(['title' => 'News', 'category' => 'marketing', 'body' => '<p></p>'])
        ->call('send')->assertHasFormErrors(['body']);
    expect(NotificationBroadcast::count())->toBe(0);
});

it('accepts application-defined labels without changing plugin code', function () {
    Bus::fake();
    config(['notification-composer.labels' => ['maintenance' => 'Maintenance']]);
    $this->actingAs($this->admin);
    Livewire::test(Notifications::class)->fillForm(['title' => 'Planned work', 'category' => 'maintenance', 'body' => '<p>Scheduled work</p>'])
        ->call('send')->assertHasNoFormErrors();
    expect(NotificationBroadcast::sole()->category)->toBe('maintenance');
});

it('delivers once to existing users and retains read receipts across retries', function () {
    $user = User::factory()->create();
    $broadcast = NotificationBroadcast::create(['title' => 'Product news', 'category' => 'product_update', 'body' => '<p><strong>New feature</strong></p>']);
    $this->travel(1)->minutes();
    $laterUser = User::factory()->create();
    $job = new DeliverNotificationBroadcast($broadcast->id);
    $job->handle();
    expect($user->notifications()->count())->toBe(1)->and($this->admin->notifications()->count())->toBe(1)
        ->and($laterUser->notifications()->count())->toBe(0)->and($broadcast->fresh()->sent_at)->not->toBeNull();
    $delivery = $user->notifications()->sole();
    expect($delivery->data['body'])->toBe('New feature')->and($delivery->data['rich_body'])->toContain('<strong>');
    $delivery->markAsRead();
    $broadcast->update(['sent_at' => null]);
    $job->handle();
    expect($user->notifications()->count())->toBe(1)->and($user->unreadNotifications()->count())->toBe(0);
});

it('renders sanitized rich messages in the plugin inbox and updates both badges without polling', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $broadcast = NotificationBroadcast::create(['title' => '<b>News title</b>', 'category' => 'marketing', 'body' => '<h2>Details</h2><p><strong>New features</strong> <a href="javascript:alert(1)">Unsafe link</a></p><img src=x onerror=alert(1)>']);
    (new DeliverNotificationBroadcast($broadcast->id))->handle();
    $notification = $user->notifications()->sole();
    expect($notification->data['body'])->toBe('Details New features Unsafe link');
    $this->actingAs($user);
    $this->get('/notifications')->assertOk()->assertSee('Marketing')->assertSee('&lt;b&gt;News title&lt;/b&gt;', false)
        ->assertSee('<strong>New features</strong>', false)->assertDontSee('javascript:', false)->assertDontSee('onerror=', false)
        ->assertDontSee('wire:poll', false)->assertDontSee('wave.notification-badge');
    expect(app(UnreadCount::class)->get())->toBe(1);
    Livewire::test(Inbox::class)->call('markAsRead', $other->notifications()->sole()->id);
    expect($other->unreadNotifications()->count())->toBe(1);
    Livewire::test(Inbox::class)->call('markAsRead', $notification->id)
        ->assertDispatched('notification-count-updated', count: 0)->assertDontSee('New features');
    expect($user->notifications()->count())->toBe(1)->and(app(UnreadCount::class)->get())->toBe(0);
});

it('keeps read receipts through the existing notification endpoint', function () {
    $broadcast = NotificationBroadcast::create(['title' => 'Security notice', 'category' => 'security', 'body' => '<p>Notice</p>']);
    (new DeliverNotificationBroadcast($broadcast->id))->handle();
    $notification = $this->admin->notifications()->sole();
    $this->actingAs($this->admin)->postJson(route('wave.notification.read', ['id' => $notification->id]))->assertOk();
    expect($notification->fresh()->read_at)->not->toBeNull();
    $broadcast->update(['sent_at' => null]);
    (new DeliverNotificationBroadcast($broadcast->id))->handle();
    expect($this->admin->unreadNotifications()->count())->toBe(0);
});

it('does not enqueue another job for a replayed submission', function () {
    Bus::fake();
    $this->actingAs($this->admin);
    $component = Livewire::test(Notifications::class);
    NotificationBroadcast::create(['id' => $component->get('submissionId'), 'title' => 'Already queued', 'category' => 'security', 'body' => '<p>Notice</p>']);
    $component->fillForm(['title' => 'Already queued', 'category' => 'security', 'body' => '<p>Notice</p>'])->call('send')->assertHasNoFormErrors();
    expect(NotificationBroadcast::count())->toBe(1);
    Bus::assertNotDispatched(DeliverNotificationBroadcast::class);
});

it('uses a shared team identity and logs the real sender', function () {
    config(['notification-composer.team_name' => 'Example Team', 'notification-composer.team_avatar' => '/storage/demo/default.png', 'activity.enabled' => true, 'activity.queue' => false]);
    Bus::fake();
    $secondAdmin = User::factory()->create(['name' => 'Second Administrator']);
    $secondAdmin->assignRole('admin');
    foreach ([$this->admin, $secondAdmin] as $index => $admin) {
        $this->actingAs($admin);
        Livewire::test(Notifications::class)->fillForm(['title' => 'Notice '.$index, 'category' => 'security', 'body' => '<p>Team announcement</p>'])
            ->callAction('send')->assertHasNoFormErrors();
        $log = ActivityLog::where('action', 'notification_broadcast_queued')->latest('id')->firstOrFail();
        expect($log->user_id)->toBe($admin->id);
        (new DeliverNotificationBroadcast($log->metadata['broadcast_id']))->handle();
    }
    foreach ($this->admin->notifications()->get()->pluck('data') as $payload) {
        expect($payload['user']['name'])->toBe('Example Team')->and($payload['icon'])->toBe(url('/storage/demo/default.png'));
    }
    $this->actingAs($this->admin)->get('/notifications')->assertOk()->assertSee('Example Team')
        ->assertSee('h-10 w-10 shrink-0 rounded-full', false)->assertDontSee('Second Administrator');
});

it('renders rich content inside an alternate theme layout with no theme-specific message renderer', function () {
    $root = sys_get_temp_dir().'/notification-layout-'.bin2hex(random_bytes(8));
    mkdir($root.'/components/layouts', 0700, true);
    file_put_contents($root.'/components/layouts/app.blade.php', '<html><body><main data-alternate-theme>{{ $slot }}</main></body></html>');
    $this->beforeApplicationDestroyed(fn () => File::deleteDirectory($root));
    $broadcast = NotificationBroadcast::create(['title' => 'Portable message', 'category' => 'security', 'body' => '<p><strong>Rich message</strong></p>']);
    (new DeliverNotificationBroadcast($broadcast->id))->handle();
    $this->actingAs($this->admin);
    view()->replaceNamespace('theme', [$root]);
    $this->get('/notifications')->assertOk()->assertSee('data-alternate-theme', false)->assertSee('<strong>Rich message</strong>', false)
        ->assertDontSee('&lt;strong&gt;', false);
});

it('uses version metadata and the stock installed-plugin file for activation', function () {
    $root = sys_get_temp_dir().'/notification-activation-'.bin2hex(random_bytes(8));
    mkdir($root, 0700, true);
    $this->beforeApplicationDestroyed(fn () => File::deleteDirectory($root));
    $original = config('wave.plugins_path');
    try {
        config(['wave.plugins_path' => $root]);
        expect(FilamentPlugin::active())->toBe([]);
        file_put_contents($root.'/installed.json', '["notification-composer"]');
        expect(FilamentPlugin::active())->toHaveCount(1);
        file_put_contents($root.'/installed.json', '[]');
        expect(FilamentPlugin::active())->toBe([]);
    } finally {
        config(['wave.plugins_path' => $original]);
    }
    $provider = new NotificationComposerPlugin(app());
    expect($provider->getPluginInfo()['version']['version'])->toBe('1.0.0');
    $this->actingAs($this->admin);
    expect(view('notification-composer::badge')->render())->not->toContain('wire:poll');
});
