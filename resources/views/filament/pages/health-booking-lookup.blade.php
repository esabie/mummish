<x-filament-panels::page>
    <form wire:submit="lookup" class="space-y-6">
        {{ $this->form }}

        <div class="flex flex-wrap items-center gap-3">
            <x-filament::button type="submit" icon="heroicon-o-magnifying-glass">
                Look up booking
            </x-filament::button>
        </div>
    </form>

    @if ($result)
        <div class="mt-8 space-y-6">
            <div>
                <h2 class="text-lg font-semibold text-gray-950 dark:text-white">
                    Booking
                    <span class="font-mono">{{ $result['reference'] }}</span>
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ $result['patient_name'] }} · {{ $result['patient_email'] }}
                </p>
            </div>

            <x-filament::section>
                <x-slot name="heading">Status</x-slot>
                <x-slot name="headerEnd">
                    <x-filament::badge color="gray">
                        {{ $result['status_label'] }}
                    </x-filament::badge>
                </x-slot>

                <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Payment</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-950 dark:text-white">{{ $result['payment_status_label'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Amount</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-950 dark:text-white">{{ $result['amount_label'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Visit mode</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-950 dark:text-white">{{ $result['visit_mode'] ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Appointment</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-950 dark:text-white">
                            {{ $result['appointment_date_label'] ?: '—' }}
                            @if ($result['appointment_time'])
                                at {{ $result['appointment_time'] }}
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Service</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-950 dark:text-white">{{ $result['service_name'] ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Professional</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-950 dark:text-white">{{ $result['professional_name'] ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Patient phone</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-950 dark:text-white">{{ $result['patient_phone'] ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Paid at</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-950 dark:text-white">{{ $result['paid_at'] ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Confirmed at</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-950 dark:text-white">{{ $result['confirmed_at'] ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Paystack reference</dt>
                        <dd class="mt-1 font-mono text-sm text-gray-950 dark:text-white">{{ $result['paystack_reference'] ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Paystack transaction</dt>
                        <dd class="mt-1 font-mono text-sm text-gray-950 dark:text-white">{{ $result['paystack_transaction_id'] ?: '—' }}</dd>
                    </div>
                </dl>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">Visit logistics</x-slot>
                <dl class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Google Meet link</dt>
                        <dd class="mt-1 break-all text-sm text-gray-950 dark:text-white">
                            @if ($result['join_url'])
                                <a href="{{ $result['join_url'] }}" target="_blank" rel="noopener noreferrer" class="text-primary-600 underline">
                                    {{ $result['join_url'] }}
                                </a>
                                @if ($result['can_join_session'])
                                    <span class="ml-2 text-xs font-semibold text-emerald-700 dark:text-emerald-300">Join window open</span>
                                @endif
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Meeting location</dt>
                        <dd class="mt-1 text-sm text-gray-950 dark:text-white">{{ $result['meeting_location'] ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">WhatsApp</dt>
                        <dd class="mt-1 text-sm text-gray-950 dark:text-white">{{ $result['meeting_whatsapp'] ?: '—' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Logistics notes</dt>
                        <dd class="mt-1 text-sm text-gray-950 dark:text-white">{{ $result['logistics_notes'] ?: '—' }}</dd>
                    </div>
                    @if ($result['notes'])
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Patient notes</dt>
                            <dd class="mt-1 text-sm text-gray-950 dark:text-white">{{ $result['notes'] }}</dd>
                        </div>
                    @endif
                    @if ($result['cancellation_reason'])
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Cancellation reason</dt>
                            <dd class="mt-1 text-sm text-gray-950 dark:text-white">{{ $result['cancellation_reason'] }}</dd>
                        </div>
                    @endif
                </dl>
            </x-filament::section>

            <div class="flex flex-wrap gap-3">
                <x-filament::button
                    tag="a"
                    :href="$result['admin_booking_url']"
                    color="gray"
                    icon="heroicon-o-arrow-top-right-on-square"
                >
                    Open booking
                </x-filament::button>
                @if (! empty($result['admin_professional_url']))
                    <x-filament::button
                        tag="a"
                        :href="$result['admin_professional_url']"
                        color="gray"
                        icon="heroicon-o-user"
                    >
                        Open professional
                    </x-filament::button>
                @endif
            </div>
        </div>
    @endif
</x-filament-panels::page>
