<x-filament-panels::page>
    @php
        $summary = $this->earningsSummary;
        $hasSales = ($summary['totals']['gross_cents'] ?? 0) > 0
            || ($summary['delivery']['shipping_cents'] ?? 0) > 0;
    @endphp

    @if ($hasSales)
        <div class="mb-6 space-y-6">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Totals for the current filters (paid orders only). Merchandise is split between Mummish and vendors.
                Delivery fees are tracked separately for the courier and are never part of the commission pool.
            </p>

            <div class="grid gap-4 sm:grid-cols-3">
                <x-filament::section>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Collected from buyers</p>
                    <p class="mt-2 text-2xl font-bold text-gray-950 dark:text-white">{{ $summary['collected']['formatted_total'] }}</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Merchandise {{ $summary['collected']['formatted_merchandise'] }}
                        + delivery {{ $summary['collected']['formatted_shipping'] }}
                    </p>
                </x-filament::section>

                <x-filament::section>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Merchandise sales</p>
                    <p class="mt-2 text-2xl font-bold text-gray-950 dark:text-white">{{ $summary['totals']['formatted_gross'] }}</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        {{ $summary['totals']['order_count'] }} {{ $summary['totals']['order_count'] === 1 ? 'order' : 'orders' }} · item subtotals only
                    </p>
                </x-filament::section>

                <x-filament::section>
                    <p class="text-xs font-semibold uppercase tracking-wide text-sky-700 dark:text-sky-400">Delivery fees</p>
                    <p class="mt-2 text-2xl font-bold text-sky-800 dark:text-sky-300">{{ $summary['delivery']['formatted_shipping'] }}</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Collected across {{ $summary['delivery']['order_count'] }} {{ $summary['delivery']['order_count'] === 1 ? 'order' : 'orders' }}
                    </p>
                    <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-400">Due to courier</dt>
                            <dd class="mt-1 text-lg font-bold text-amber-800 dark:text-amber-300">{{ $summary['delivery']['formatted_due'] }}</dd>
                            <dd class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $summary['delivery']['due_order_count'] }} unpaid
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-400">Paid to courier</dt>
                            <dd class="mt-1 text-lg font-bold text-emerald-800 dark:text-emerald-300">{{ $summary['delivery']['formatted_paid'] }}</dd>
                            <dd class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $summary['delivery']['paid_order_count'] }} settled
                            </dd>
                        </div>
                    </dl>
                </x-filament::section>
            </div>

            <x-filament::section heading="Merchandise pool">
                <p class="mb-4 text-xs text-gray-500 dark:text-gray-400">
                    Split of item sales only — excludes delivery fees.
                </p>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-400">Mummish commission</p>
                        <p class="mt-2 text-2xl font-bold text-amber-800 dark:text-amber-300">{{ $summary['totals']['formatted_commission'] }}</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ $summary['commission_percent'] }}% platform fee
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-400">Vendor payouts</p>
                        <p class="mt-2 text-2xl font-bold text-emerald-800 dark:text-emerald-300">{{ $summary['totals']['formatted_payout'] }}</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ 100 - $summary['commission_percent'] }}% to vendors
                        </p>
                    </div>
                </div>

                <div class="mt-6 flex h-3 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                    <div
                        class="bg-amber-500"
                        style="width: {{ $summary['commission_share_percent'] }}%"
                        title="Mummish commission"
                    ></div>
                    <div
                        class="bg-emerald-500"
                        style="width: {{ $summary['payout_share_percent'] }}%"
                        title="Vendor payouts"
                    ></div>
                </div>
                <div class="mt-3 flex flex-wrap gap-4 text-xs text-gray-600 dark:text-gray-400">
                    <span class="flex items-center gap-2">
                        <span class="inline-block h-2.5 w-2.5 rounded-full bg-amber-500"></span>
                        Mummish {{ $summary['commission_share_percent'] }}% ({{ $summary['totals']['formatted_commission'] }})
                    </span>
                    <span class="flex items-center gap-2">
                        <span class="inline-block h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                        Vendors {{ $summary['payout_share_percent'] }}% ({{ $summary['totals']['formatted_payout'] }})
                    </span>
                </div>
            </x-filament::section>

            <div class="grid gap-4 lg:grid-cols-2">
                <x-filament::section heading="In escrow">
                    <p class="mb-4 text-xs text-gray-500 dark:text-gray-400">
                        Merchandise awaiting delivery confirmation before vendor wallet release.
                    </p>
                    <dl class="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Gross</dt>
                            <dd class="font-medium">{{ $summary['escrow']['formatted_gross'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Orders</dt>
                            <dd class="font-medium">{{ $summary['escrow']['order_count'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-amber-700 dark:text-amber-400">Mummish fee</dt>
                            <dd class="font-medium text-amber-800 dark:text-amber-300">{{ $summary['escrow']['formatted_commission'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-emerald-700 dark:text-emerald-400">Vendor payout</dt>
                            <dd class="font-medium text-emerald-800 dark:text-emerald-300">{{ $summary['escrow']['formatted_payout'] }}</dd>
                        </div>
                    </dl>
                </x-filament::section>

                <x-filament::section heading="Released to wallet">
                    <p class="mb-4 text-xs text-gray-500 dark:text-gray-400">
                        Delivered orders — vendor share ready for settlement or already paid out.
                    </p>
                    <dl class="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <dt class="text-amber-700 dark:text-amber-400">Due to vendors</dt>
                            <dd class="font-medium text-amber-800 dark:text-amber-300">{{ $summary['wallet_due']['formatted_payout'] }}</dd>
                            <dd class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $summary['wallet_due']['order_count'] }} unpaid
                            </dd>
                        </div>
                        <div>
                            <dt class="text-emerald-700 dark:text-emerald-400">Paid to vendors</dt>
                            <dd class="font-medium text-emerald-800 dark:text-emerald-300">{{ $summary['wallet_settled']['formatted_payout'] }}</dd>
                            <dd class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $summary['wallet_settled']['order_count'] }} settled
                            </dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Wallet gross</dt>
                            <dd class="font-medium">{{ $summary['wallet']['formatted_gross'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Orders</dt>
                            <dd class="font-medium">{{ $summary['wallet']['order_count'] }}</dd>
                        </div>
                    </dl>
                </x-filament::section>
            </div>

            @if (! empty($summary['vendor_breakdown']))
                <x-filament::section heading="By vendor">
                    <div class="-mx-6 -mb-6 mt-2 overflow-hidden rounded-b-xl">
                        <div class="overflow-x-auto">
                            <table class="w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
                                <thead class="bg-gray-50 dark:bg-white/5">
                                    <tr>
                                        <th scope="col" class="px-3 py-3.5 text-sm font-semibold text-gray-950 sm:ps-6 dark:text-white">
                                            Shop
                                        </th>
                                        <th scope="col" class="px-3 py-3.5 text-sm font-semibold text-gray-950 dark:text-white">
                                            Vendor email
                                        </th>
                                        <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-gray-950 dark:text-white">
                                            Orders
                                        </th>
                                        <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-gray-950 dark:text-white">
                                            Gross
                                        </th>
                                        <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-gray-950 dark:text-white">
                                            Mummish fee
                                        </th>
                                        <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-gray-950 sm:pe-6 dark:text-white">
                                            Vendor payout
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 whitespace-nowrap dark:divide-white/5">
                                    @foreach ($summary['vendor_breakdown'] as $row)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                                            <td class="px-3 py-4 text-sm font-medium text-gray-950 sm:ps-6 dark:text-white">
                                                {{ $row['shop_name'] }}
                                            </td>
                                            <td class="px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                                                {{ $row['vendor_email'] ?? '—' }}
                                            </td>
                                            <td class="px-3 py-4 text-right text-sm tabular-nums text-gray-950 dark:text-white">
                                                {{ $row['order_count'] }}
                                            </td>
                                            <td class="px-3 py-4 text-right text-sm tabular-nums text-gray-950 dark:text-white">
                                                {{ $row['formatted_gross'] }}
                                            </td>
                                            <td class="px-3 py-4 text-right text-sm tabular-nums text-amber-700 dark:text-amber-300">
                                                {{ $row['formatted_commission'] }}
                                            </td>
                                            <td class="px-3 py-4 text-right text-sm font-medium tabular-nums text-emerald-700 sm:pe-6 dark:text-emerald-300">
                                                {{ $row['formatted_payout'] }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </x-filament::section>
            @endif
        </div>
    @endif

    {{ $this->table }}
</x-filament-panels::page>
