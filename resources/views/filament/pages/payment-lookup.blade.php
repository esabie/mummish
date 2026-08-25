<x-filament-panels::page>
    <form wire:submit="lookup" class="space-y-6">
        {{ $this->form }}

        <div class="flex flex-wrap items-center gap-3">
            <x-filament::button type="submit" icon="heroicon-o-magnifying-glass">
                Look up payment
            </x-filament::button>
        </div>
    </form>

    @if ($result)
        <div class="mt-8 space-y-6">
            <div>
                <h2 class="text-lg font-semibold text-gray-950 dark:text-white">
                    Results for
                    <span class="font-mono">{{ $result['query'] }}</span>
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Covers shop checkout orders and Health Services bookings.
                </p>
            </div>

            <x-filament::section>
                <x-slot name="heading">
                    Paystack gateway
                </x-slot>

                @if (! empty($result['paystack']))
                    @php
                        $paystack = $result['paystack'];
                    @endphp
                    <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-950 dark:text-white">{{ $paystack['status_label'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Amount</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-950 dark:text-white">{{ $paystack['amount_label'] ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Channel</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-950 dark:text-white">{{ $paystack['channel'] !== '' ? $paystack['channel'] : '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Reference</dt>
                            <dd class="mt-1 font-mono text-sm text-gray-950 dark:text-white">{{ $paystack['reference'] !== '' ? $paystack['reference'] : '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Transaction ID</dt>
                            <dd class="mt-1 font-mono text-sm text-gray-950 dark:text-white">{{ $paystack['transaction_id'] ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Paid at</dt>
                            <dd class="mt-1 text-sm text-gray-950 dark:text-white">{{ $paystack['paid_at'] ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Customer email</dt>
                            <dd class="mt-1 text-sm text-gray-950 dark:text-white">{{ $paystack['customer_email'] !== '' ? $paystack['customer_email'] : '—' }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Gateway response</dt>
                            <dd class="mt-1 text-sm text-gray-950 dark:text-white">{{ $paystack['gateway_response'] !== '' ? $paystack['gateway_response'] : '—' }}</dd>
                        </div>
                    </dl>
                @elseif (! empty($result['paystack_error']))
                    <p class="text-sm text-amber-700 dark:text-amber-300">
                        Could not verify with Paystack: {{ $result['paystack_error'] }}
                    </p>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Paystack verification was skipped for this search.
                    </p>
                @endif
            </x-filament::section>

            <div>
                <h3 class="mb-3 text-base font-semibold text-gray-950 dark:text-white">
                    Local records
                    <span class="font-normal text-gray-500 dark:text-gray-400">({{ count($result['matches']) }})</span>
                </h3>

                @forelse ($result['matches'] as $match)
                    @php
                        $badgeColor = match ($match['local_status'] ?? '') {
                            'paid' => 'success',
                            'pending', 'awaiting_payment' => 'warning',
                            'failed', 'expired' => 'danger',
                            default => 'gray',
                        };

                        $amountMismatch = ! empty($result['paystack']['amount_cents'])
                            && isset($match['amount_cents'])
                            && (int) $result['paystack']['amount_cents'] !== (int) $match['amount_cents'];

                        $statusMismatch = (($result['paystack']['status'] ?? '') === 'success')
                            && (($match['local_status'] ?? '') !== 'paid');
                    @endphp

                    <x-filament::section class="mb-4">
                        <x-slot name="heading">
                            {{ $match['type_label'] }} · {{ $match['reference'] }}
                        </x-slot>
                        <x-slot name="headerEnd">
                            <x-filament::badge :color="$badgeColor">
                                {{ $match['local_status_label'] }}
                            </x-filament::badge>
                        </x-slot>

                        <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Lifecycle</dt>
                                <dd class="mt-1 text-sm font-medium text-gray-950 dark:text-white">{{ $match['lifecycle_status_label'] ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Amount</dt>
                                <dd class="mt-1 text-sm font-medium text-gray-950 dark:text-white">{{ $match['amount_label'] }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Paid at</dt>
                                <dd class="mt-1 text-sm text-gray-950 dark:text-white">{{ $match['paid_at'] ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Customer</dt>
                                <dd class="mt-1 text-sm text-gray-950 dark:text-white">{{ $match['customer_name'] ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Email</dt>
                                <dd class="mt-1 text-sm text-gray-950 dark:text-white">{{ $match['customer_email'] ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Phone</dt>
                                <dd class="mt-1 text-sm text-gray-950 dark:text-white">{{ $match['customer_phone'] ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Paystack reference</dt>
                                <dd class="mt-1 font-mono text-sm text-gray-950 dark:text-white">{{ $match['paystack_reference'] ?: '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Paystack transaction</dt>
                                <dd class="mt-1 font-mono text-sm text-gray-950 dark:text-white">{{ $match['paystack_transaction_id'] ?: '—' }}</dd>
                            </div>
                            @if (($match['type'] ?? null) === 'health_booking')
                                <div>
                                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Provider</dt>
                                    <dd class="mt-1 text-sm text-gray-950 dark:text-white">{{ $match['provider_name'] ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Service</dt>
                                    <dd class="mt-1 text-sm text-gray-950 dark:text-white">{{ $match['service_name'] ?? '—' }}</dd>
                                </div>
                            @endif
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Created</dt>
                                <dd class="mt-1 text-sm text-gray-950 dark:text-white">{{ $match['created_at'] ?? '—' }}</dd>
                            </div>
                        </dl>

                        @if ($amountMismatch || $statusMismatch)
                            <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-200">
                                <p class="font-semibold">Possible mismatch with Paystack</p>
                                <ul class="mt-1 list-disc space-y-0.5 pl-5">
                                    @if ($statusMismatch)
                                        <li>Paystack reports success, but local payment status is {{ $match['local_status_label'] }}.</li>
                                    @endif
                                    @if ($amountMismatch)
                                        <li>Amounts differ (local {{ $match['amount_label'] }} vs Paystack {{ $result['paystack']['amount_label'] }}).</li>
                                    @endif
                                </ul>
                            </div>
                        @endif

                        <div class="mt-5">
                            <x-filament::button
                                tag="a"
                                :href="$match['admin_url']"
                                color="gray"
                                icon="heroicon-o-arrow-top-right-on-square"
                            >
                                Open record
                            </x-filament::button>
                        </div>
                    </x-filament::section>
                @empty
                    <x-filament::section>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            No local shop order or health booking matched this reference.
                            @if (! empty($result['paystack']))
                                Paystack still returned a gateway record above — this may be an orphaned or external payment.
                            @endif
                        </p>
                    </x-filament::section>
                @endforelse
            </div>
        </div>
    @endif
</x-filament-panels::page>
