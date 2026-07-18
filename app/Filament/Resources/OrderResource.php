<?php

namespace App\Filament\Resources;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PromoCostBearer;
use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use App\Services\CourierSettlementService;
use App\Services\VendorEarningsService;
use App\Services\VendorSettlementService;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationLabel = 'Orders';

    protected static ?string $modelLabel = 'order';

    protected static ?string $pluralModelLabel = 'orders';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Order')
                    ->schema([
                        Infolists\Components\TextEntry::make('order_number')
                            ->label('Order #')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn (OrderStatus $state): string => match ($state) {
                                OrderStatus::PendingPayment => 'warning',
                                OrderStatus::Paid => 'success',
                                OrderStatus::Failed => 'danger',
                                OrderStatus::Cancelled => 'gray',
                            })
                            ->formatStateUsing(fn (OrderStatus $state): string => $state->label()),
                        Infolists\Components\TextEntry::make('payment_status')
                            ->badge()
                            ->color(fn (PaymentStatus $state): string => match ($state) {
                                PaymentStatus::Pending => 'warning',
                                PaymentStatus::Paid => 'success',
                                PaymentStatus::Failed => 'danger',
                            })
                            ->formatStateUsing(fn (PaymentStatus $state): string => $state->label()),
                        Infolists\Components\TextEntry::make('paystack_reference')
                            ->label('Paystack reference')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('paid_at')
                            ->dateTime()
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->dateTime(),
                    ])
                    ->columns(3),
                Infolists\Components\Section::make('Customer')
                    ->schema([
                        Infolists\Components\TextEntry::make('customer_name'),
                        Infolists\Components\TextEntry::make('customer_email')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('customer_phone'),
                        Infolists\Components\TextEntry::make('user.email')
                            ->label('Account email')
                            ->placeholder('Guest checkout'),
                    ])
                    ->columns(2),
                Infolists\Components\Section::make('Shipping')
                    ->schema([
                        Infolists\Components\TextEntry::make('shipping_address_line1'),
                        Infolists\Components\TextEntry::make('shipping_address_line2')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('shipping_city'),
                        Infolists\Components\TextEntry::make('shipping_region'),
                        Infolists\Components\TextEntry::make('shipping_notes')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Infolists\Components\Section::make('Totals')
                    ->schema([
                        Infolists\Components\TextEntry::make('subtotal_cents')
                            ->label('Subtotal')
                            ->formatStateUsing(fn (int $state): string => 'GHS '.number_format($state / 100, 2)),
                        Infolists\Components\TextEntry::make('shipping_cents')
                            ->label('Shipping')
                            ->formatStateUsing(fn (int $state): string => 'GHS '.number_format($state / 100, 2)),
                        Infolists\Components\TextEntry::make('courier_paid_at')
                            ->label('Courier settlement')
                            ->formatStateUsing(function (?\Illuminate\Support\Carbon $state, Order $record): string {
                                if ((int) $record->shipping_cents < 1) {
                                    return 'No delivery fee';
                                }

                                return $state
                                    ? 'Paid '.$state->format('Y-m-d H:i')
                                    : 'Due to courier';
                            })
                            ->badge()
                            ->color(fn (?\Illuminate\Support\Carbon $state, Order $record): string => match (true) {
                                (int) $record->shipping_cents < 1 => 'gray',
                                $state !== null => 'success',
                                default => 'warning',
                            }),
                        Infolists\Components\TextEntry::make('vendor_paid_at')
                            ->label('Vendor settlement')
                            ->formatStateUsing(function (?\Illuminate\Support\Carbon $state, Order $record): string {
                                if (! $record->isPaid()) {
                                    return '—';
                                }

                                if (! $record->hasReleasedEscrow()) {
                                    return 'In escrow';
                                }

                                return $state
                                    ? 'Paid '.$state->format('Y-m-d H:i')
                                    : 'Due to vendor';
                            })
                            ->badge()
                            ->color(fn (?\Illuminate\Support\Carbon $state, Order $record): string => match (true) {
                                ! $record->isPaid() => 'gray',
                                ! $record->hasReleasedEscrow() => 'warning',
                                $state !== null => 'success',
                                default => 'warning',
                            }),
                        Infolists\Components\TextEntry::make('promo_code')
                            ->label('Promo code')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('discount_cents')
                            ->label('Discount')
                            ->formatStateUsing(fn (int $state): string => $state > 0
                                ? '− GHS '.number_format($state / 100, 2)
                                : '—'),
                        Infolists\Components\TextEntry::make('promo_cost_bearer')
                            ->label('Promo cost borne by')
                            ->formatStateUsing(fn (?PromoCostBearer $state): string => $state?->label() ?? '—')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('total_cents')
                            ->label('Total')
                            ->formatStateUsing(fn (int $state): string => 'GHS '.number_format($state / 100, 2)),
                        Infolists\Components\TextEntry::make('currency'),
                    ])
                    ->columns(3),
                Infolists\Components\Section::make('Vendor totals')
                    ->description('Amounts owed to each vendor after commission and any promo cost allocation.')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('vendor_totals')
                            ->label('')
                            ->state(function (Order $record): array {
                                $earnings = app(VendorEarningsService::class);

                                return $record->items
                                    ->groupBy('vendor_user_id')
                                    ->map(function ($items) use ($record, $earnings) {
                                        /** @var \Illuminate\Support\Collection<int, \App\Models\OrderItem> $items */
                                        $vendor = $items->first()?->vendor;
                                        $split = $earnings->splitOrderMerchandise($record, $vendor?->id);

                                        return [
                                            'shop_name' => $vendor?->vendorApplication?->shop_name
                                                ?? $vendor?->email
                                                ?? 'Unknown vendor',
                                            'vendor_email' => $vendor?->email,
                                            'item_count' => $items->sum('quantity'),
                                            'total_cents' => $split['gross_cents'],
                                            'commission_cents' => $split['commission_cents'],
                                            'payout_cents' => $split['payout_cents'],
                                        ];
                                    })
                                    ->values()
                                    ->all();
                            })
                            ->schema([
                                Infolists\Components\TextEntry::make('shop_name')
                                    ->label('Shop'),
                                Infolists\Components\TextEntry::make('vendor_email')
                                    ->label('Vendor email')
                                    ->copyable(),
                                Infolists\Components\TextEntry::make('item_count')
                                    ->label('Qty sold'),
                                Infolists\Components\TextEntry::make('total_cents')
                                    ->label('Gross')
                                    ->formatStateUsing(fn (int $state): string => 'GHS '.number_format($state / 100, 2)),
                                Infolists\Components\TextEntry::make('commission_cents')
                                    ->label('Mummish fee')
                                    ->formatStateUsing(fn (int $state): string => 'GHS '.number_format($state / 100, 2)),
                                Infolists\Components\TextEntry::make('payout_cents')
                                    ->label('Vendor payout')
                                    ->formatStateUsing(fn (int $state): string => 'GHS '.number_format($state / 100, 2)),
                            ])
                            ->columns(3),
                    ])
                    ->visible(fn (Order $record): bool => $record->items->isNotEmpty()),
                Infolists\Components\Section::make('Line items')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('items')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('vendor.vendorApplication.shop_name')
                                    ->label('Shop')
                                    ->placeholder('—'),
                                Infolists\Components\TextEntry::make('product_title')
                                    ->label('Product'),
                                Infolists\Components\TextEntry::make('product_sku')
                                    ->label('SKU')
                                    ->placeholder('—'),
                                Infolists\Components\TextEntry::make('quantity'),
                                Infolists\Components\TextEntry::make('unit_price_cents')
                                    ->label('Unit price')
                                    ->formatStateUsing(fn (int $state): string => 'GHS '.number_format($state / 100, 2)),
                                Infolists\Components\TextEntry::make('line_total_cents')
                                    ->label('Line total')
                                    ->formatStateUsing(fn (int $state): string => 'GHS '.number_format($state / 100, 2)),
                            ])
                            ->columns(6),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label('Order #')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('customer_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer_email')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (OrderStatus $state): string => match ($state) {
                        OrderStatus::PendingPayment => 'warning',
                        OrderStatus::Paid => 'success',
                        OrderStatus::Failed => 'danger',
                        OrderStatus::Cancelled => 'gray',
                    })
                    ->formatStateUsing(fn (OrderStatus $state): string => $state->label()),
                Tables\Columns\TextColumn::make('payment_status')
                    ->badge()
                    ->color(fn (PaymentStatus $state): string => match ($state) {
                        PaymentStatus::Pending => 'warning',
                        PaymentStatus::Paid => 'success',
                        PaymentStatus::Failed => 'danger',
                    })
                    ->formatStateUsing(fn (PaymentStatus $state): string => $state->label()),
                Tables\Columns\TextColumn::make('total_cents')
                    ->label('Total')
                    ->formatStateUsing(fn (int $state): string => 'GHS '.number_format($state / 100, 2))
                    ->sortable(),
                Tables\Columns\TextColumn::make('courier_paid_at')
                    ->label('Courier')
                    ->badge()
                    ->formatStateUsing(function (?\Illuminate\Support\Carbon $state, Order $record): string {
                        if ((int) $record->shipping_cents < 1) {
                            return 'No fee';
                        }

                        return $state ? 'Paid' : 'Due';
                    })
                    ->color(fn (?\Illuminate\Support\Carbon $state, Order $record): string => match (true) {
                        (int) $record->shipping_cents < 1 => 'gray',
                        $state !== null => 'success',
                        default => 'warning',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('vendor_paid_at')
                    ->label('Vendor')
                    ->badge()
                    ->formatStateUsing(function (?\Illuminate\Support\Carbon $state, Order $record): string {
                        if (! $record->isPaid()) {
                            return '—';
                        }

                        if (! $record->hasReleasedEscrow()) {
                            return 'Escrow';
                        }

                        return $state ? 'Paid' : 'Due';
                    })
                    ->color(fn (?\Illuminate\Support\Carbon $state, Order $record): string => match (true) {
                        ! $record->isPaid() => 'gray',
                        ! $record->hasReleasedEscrow() => 'warning',
                        $state !== null => 'success',
                        default => 'warning',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items')
                    ->sortable(),
                Tables\Columns\TextColumn::make('paid_at')
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(OrderStatus::cases())->mapWithKeys(
                        fn (OrderStatus $status) => [$status->value => $status->label()]
                    )->all()),
                Tables\Filters\SelectFilter::make('payment_status')
                    ->options(collect(PaymentStatus::cases())->mapWithKeys(
                        fn (PaymentStatus $status) => [$status->value => $status->label()]
                    )->all()),
                Tables\Filters\TernaryFilter::make('courier_due')
                    ->label('Courier fee due')
                    ->queries(
                        true: fn (Builder $query) => $query
                            ->where('payment_status', PaymentStatus::Paid)
                            ->where('shipping_cents', '>', 0)
                            ->whereNull('courier_paid_at'),
                        false: fn (Builder $query) => $query
                            ->where('shipping_cents', '>', 0)
                            ->whereNotNull('courier_paid_at'),
                        blank: fn (Builder $query) => $query,
                    ),
                Tables\Filters\TernaryFilter::make('vendor_due')
                    ->label('Vendor payout due')
                    ->queries(
                        true: fn (Builder $query) => $query
                            ->where('payment_status', PaymentStatus::Paid)
                            ->whereNull('vendor_paid_at')
                            ->whereHas('vendorFulfillments', fn (Builder $fulfillmentQuery) => $fulfillmentQuery
                                ->where('status', \App\Enums\VendorFulfillmentStatus::Delivered)),
                        false: fn (Builder $query) => $query
                            ->whereNotNull('vendor_paid_at'),
                        blank: fn (Builder $query) => $query,
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('mark_courier_paid')
                    ->label('Mark courier paid')
                    ->icon('heroicon-o-truck')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Mark courier fee paid?')
                    ->modalDescription(fn (Order $record): string => 'Record that the delivery fee of GHS '
                        .number_format($record->shipping_cents / 100, 2)
                        .' for order '.$record->order_number.' has been paid to the courier.')
                    ->visible(fn (Order $record): bool => $record->courierFeeIsDue())
                    ->action(function (Order $record): void {
                        app(CourierSettlementService::class)->markPaid($record);

                        Notification::make()
                            ->title('Courier fee marked paid')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('mark_vendor_paid')
                    ->label('Mark vendor paid')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Mark vendor payout paid?')
                    ->modalDescription(fn (Order $record): string => 'Record that the vendor payout for order '
                        .$record->order_number.' has been paid out.')
                    ->visible(fn (Order $record): bool => $record->vendorPayoutIsDue())
                    ->action(function (Order $record): void {
                        app(VendorSettlementService::class)->markPaid($record);

                        Notification::make()
                            ->title('Vendor payout marked paid')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('mark_courier_paid')
                    ->label('Mark courier paid')
                    ->icon('heroicon-o-truck')
                    ->color('success')
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records): void {
                        $count = app(CourierSettlementService::class)->markManyPaid($records);

                        Notification::make()
                            ->title($count === 1
                                ? '1 courier fee marked paid'
                                : "{$count} courier fees marked paid")
                            ->success()
                            ->send();
                    }),
                Tables\Actions\BulkAction::make('mark_vendor_paid')
                    ->label('Mark vendor paid')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records): void {
                        $count = app(VendorSettlementService::class)->markManyPaid(
                            $records->loadMissing('vendorFulfillments')
                        );

                        Notification::make()
                            ->title($count === 1
                                ? '1 vendor payout marked paid'
                                : "{$count} vendor payouts marked paid")
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user', 'items.vendor.vendorApplication', 'vendorFulfillments']);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
        ];
    }
}
