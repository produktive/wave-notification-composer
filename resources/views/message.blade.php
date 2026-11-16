@props(['notification'])
@php
    $data = $notification->data;
    $teamName = $data['user']['name'] ?? config('notification-composer.team_name', config('app.name').' Team');
    $teamAvatar = url(config('notification-composer.team_avatar', '/storage/demo/default.png'));
    $category = $data['category'] ?? '';
    $label = \Wave\Plugins\NotificationComposer\Models\NotificationBroadcast::categories()[$category] ?? 'Notification';
    $timezone = $timezone ?? (auth()->user()->profile('time-zone') ?: config('app.timezone'));
    if (! in_array($timezone, timezone_identifiers_list(), true)) {
        $timezone = config('app.timezone');
    }
@endphp
<article wire:key="notification-{{ $notification->id }}" class="border-b border-zinc-200 py-5 dark:border-zinc-700">
    <div class="flex items-start gap-3">
        <img src="{{ $teamAvatar }}" alt="" class="mt-1 h-10 w-10 shrink-0 rounded-full">
        <div class="min-w-0 flex-1">
            <p class="text-sm font-bold text-zinc-600 dark:text-zinc-200">{{ $teamName }}</p>
    <div class="mt-1 flex flex-wrap items-center gap-3">
        <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ config('notification-composer.label_styles.'.$category, 'bg-zinc-100 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-100') }}">{{ $label }}</span>
        <time datetime="{{ $notification->created_at->toIso8601String() }}" class="text-xs text-zinc-500 dark:text-zinc-400">{{ $notification->created_at->copy()->timezone($timezone)->format('M j, Y g:i A') }}</time>
    </div>
    <h3 class="mt-3 text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ $data['title'] ?? '' }}</h3>
    <div class="prose prose-sm mt-3 max-w-none dark:prose-invert">{!! \Illuminate\Support\Str::sanitizeHtml($data['rich_body'] ?? $data['body'] ?? '') !!}</div>
    <button type="button" wire:click="markAsRead('{{ $notification->id }}')" wire:loading.attr="disabled" class="mark-as-read mt-4 flex cursor-pointer items-center justify-start py-1 text-xs text-zinc-500 hover:text-zinc-700 hover:underline disabled:opacity-50">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
        Mark as Read
    </button>
        </div>
    </div>
</article>
