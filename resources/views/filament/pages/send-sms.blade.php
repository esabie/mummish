<x-filament-panels::page>
    <form wire:submit="send" class="space-y-6">
        {{ $this->form }}

        <div class="flex flex-wrap items-center gap-3">
            <x-filament::button
                type="submit"
                icon="heroicon-o-paper-airplane"
                wire:confirm="Send this SMS to the selected audience? This cannot be undone."
            >
                Send SMS
            </x-filament::button>
        </div>
    </form>

    <div class="mt-10">
        <h2 class="text-lg font-semibold text-gray-950 dark:text-white">
            Recent campaigns
        </h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Latest outbound messages sent from the admin panel.
        </p>

        <div class="mt-4 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="overflow-x-auto">
                <table class="w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
                    <thead class="bg-gray-50 dark:bg-white/5">
                        <tr>
                            <th scope="col" class="px-3 py-3.5 text-sm font-semibold text-gray-950 sm:ps-6 dark:text-white">When</th>
                            <th scope="col" class="px-3 py-3.5 text-sm font-semibold text-gray-950 dark:text-white">Audience</th>
                            <th scope="col" class="px-3 py-3.5 text-sm font-semibold text-gray-950 dark:text-white">Message</th>
                            <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-gray-950 dark:text-white">Recipients</th>
                            <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-gray-950 dark:text-white">Sent</th>
                            <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-gray-950 dark:text-white">Failed</th>
                            <th scope="col" class="px-3 py-3.5 text-sm font-semibold text-gray-950 dark:text-white">Status</th>
                            <th scope="col" class="px-3 py-3.5 text-sm font-semibold text-gray-950 sm:pe-6 dark:text-white">By</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                        @forelse ($recentCampaigns as $campaign)
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 sm:ps-6 dark:text-gray-400">
                                    {{ $campaign['created_at'] }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-950 dark:text-white">
                                    {{ $campaign['audience'] }}
                                </td>
                                <td class="max-w-xs px-3 py-4 text-sm text-gray-700 dark:text-gray-300">
                                    <span class="line-clamp-2">{{ $campaign['message_preview'] }}</span>
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-right text-sm tabular-nums text-gray-950 dark:text-white">
                                    {{ $campaign['recipient_count'] }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-right text-sm tabular-nums text-emerald-700 dark:text-emerald-300">
                                    {{ $campaign['sent_count'] }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-right text-sm tabular-nums text-amber-700 dark:text-amber-300">
                                    {{ $campaign['failed_count'] }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm">
                                    @php
                                        $badgeColor = match ($campaign['status_value']) {
                                            'completed' => 'success',
                                            'sending', 'pending' => 'warning',
                                            'failed' => 'danger',
                                            default => 'gray',
                                        };
                                    @endphp
                                    <x-filament::badge :color="$badgeColor">
                                        {{ $campaign['status'] }}
                                    </x-filament::badge>
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 sm:pe-6 dark:text-gray-400">
                                    {{ $campaign['created_by'] }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-3 py-8 text-center text-sm text-gray-500 sm:ps-6 dark:text-gray-400">
                                    No SMS campaigns yet. Compose a message above to send your first one.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
