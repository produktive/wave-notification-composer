@php
    $timezone = auth()->user()->profile('time-zone') ?: config('app.timezone');
    if (! in_array($timezone, timezone_identifiers_list(), true)) {
        $timezone = config('app.timezone');
    }
@endphp
<div class="mx-auto my-10 w-full max-w-4xl rounded-lg bg-white dark:bg-zinc-800 border border-zinc-200 p-6 dark:border-zinc-700 lg:p-10">
    <a href="{{ route('dashboard') }}" wire:navigate class="mb-3 md:mb-6 inline-flex items-center rounded-full border border-zinc-200 bg-zinc-100 px-2.5 py-1.5 text-xs font-semibold text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"><svg class="mr-2 h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>Back to Dashboard</a>
    <h1 class="text-lg sm:text-xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-100">Notifications</h1>
    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">View your current notifications</p>
    <div class="mt-5 border-t border-zinc-200 dark:border-zinc-700">
        @forelse ($notifications as $notification)
            @if (\Wave\Plugins\NotificationComposer\Support\InboxNotifications::isBroadcast($notification->type))
                @include('notification-composer::message', ['notification' => $notification])
            @else
                <article wire:key="notification-{{ $notification->id }}" class="border-b border-zinc-200 py-5 dark:border-zinc-700">
                    <div class="flex items-start gap-3">
                        @if (! empty($notification->data['icon']))
                            <img src="{{ $notification->data['icon'] }}" alt="" class="mt-1 h-10 w-10 shrink-0 rounded-full">
                        @endif
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-sm font-bold text-zinc-600 dark:text-zinc-200">{{ $notification->data['user']['name'] ?? 'Notification' }}</h2>
                                <time datetime="{{ $notification->created_at->toIso8601String() }}" class="text-xs text-zinc-500 dark:text-zinc-400">{{ $notification->created_at->copy()->timezone($timezone)->format('F jS h:i A') }}</time>
                            </div>
                            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">{{ $notification->data['body'] ?? $notification->data['message'] ?? '' }}@if (! empty($notification->data['title'])) in {{ $notification->data['title'] }}@endif</p>
                    @php
                        $link = $notification->data['link'] ?? null;
                        $safeLink = is_string($link) && ((str_starts_with($link, '/') && ! str_starts_with($link, '//')) || in_array(parse_url($link, PHP_URL_SCHEME), ['http', 'https'], true));
                    @endphp
                    @if ($safeLink)
                        <a href="{{ $link }}" class="mt-2 inline-block text-sm text-primary-600 underline dark:text-primary-400">View</a>
                    @endif
                    <button type="button" wire:click="markAsRead('{{ $notification->id }}')" class="mt-3 flex items-center text-xs text-zinc-500 hover:text-zinc-700 hover:underline"><svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>Mark as Read</button>
                        </div>
                    </div>
                </article>
            @endif
        @empty
            <p class="mt-5 rounded-lg bg-zinc-100 p-6 text-center text-sm text-zinc-500 dark:bg-zinc-900/50 dark:text-zinc-400">No Notifications</p>
        @endforelse
    </div>
</div>
