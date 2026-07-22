<x-filament-panels::page>
    <p class="mb-6 text-sm text-gray-500 dark:text-gray-400">
        Choose a group to view or manage people on Mummish.
    </p>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($cards as $card)
            <a
                href="{{ $card['url'] }}"
                class="group block rounded-xl outline-none transition hover:opacity-95 focus-visible:ring-2 focus-visible:ring-primary-500"
            >
                <x-filament::section>
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-base font-semibold text-gray-950 dark:text-white">
                                {{ $card['title'] }}
                            </p>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                {{ $card['description'] }}
                            </p>
                        </div>
                        <x-filament::icon
                            :icon="$card['icon']"
                            class="h-6 w-6 text-gray-400 transition group-hover:text-primary-600 dark:group-hover:text-primary-400"
                        />
                    </div>
                    <p class="mt-5 text-3xl font-bold tracking-tight text-gray-950 dark:text-white">
                        {{ number_format($card['count']) }}
                    </p>
                    <p class="mt-1 text-xs font-medium text-primary-600 dark:text-primary-400">
                        Open list →
                    </p>
                </x-filament::section>
            </a>
        @endforeach
    </div>
</x-filament-panels::page>
