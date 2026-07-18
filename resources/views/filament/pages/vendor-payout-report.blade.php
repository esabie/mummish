<x-filament-panels::page>
    @if ($this->vendorSummary->isNotEmpty())
        <x-filament::section heading="Payout summary" class="mb-6">
            <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
                Totals for the current filters (paid orders only). Item subtotals — shipping and discounts are not included.
            </p>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="border-b border-gray-200 dark:border-gray-700">
                        <tr>
                            <th class="px-3 py-2 font-medium">Shop</th>
                            <th class="px-3 py-2 font-medium">Vendor email</th>
                            <th class="px-3 py-2 font-medium text-right">Qty sold</th>
                            <th class="px-3 py-2 font-medium text-right">Orders</th>
                            <th class="px-3 py-2 font-medium text-right">Payout due</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->vendorSummary as $row)
                            <tr class="border-b border-gray-100 dark:border-gray-800">
                                <td class="px-3 py-2">{{ $row['shop_name'] }}</td>
                                <td class="px-3 py-2">{{ $row['vendor_email'] ?? '—' }}</td>
                                <td class="px-3 py-2 text-right">{{ $row['quantity'] }}</td>
                                <td class="px-3 py-2 text-right">{{ $row['order_count'] }}</td>
                                <td class="px-3 py-2 text-right font-medium">
                                    GHS {{ number_format($row['total_cents'] / 100, 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @endif

    {{ $this->table }}
</x-filament-panels::page>
