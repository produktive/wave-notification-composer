<x-filament-panels::page>
    <p class="text-sm text-gray-600 dark:text-gray-400">Compose an in-app notification for all current users. Choose a label and confirm before sending. No email is sent.</p>
    <form wire:submit="mountAction('send')" class="space-y-6">
        {{ $this->form }}
        {{ $this->sendAction }}
    </form>
</x-filament-panels::page>
