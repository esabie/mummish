<?php

namespace App\Filament\Resources;

use App\Enums\VendorFulfillmentStatus;
use App\Filament\Resources\VendorOrderFulfillmentResource\Pages;
use App\Models\VendorOrderFulfillment;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VendorOrderFulfillmentResource extends Resource
{
    protected static ?string $model = VendorOrderFulfillment::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'Pickup pipeline';

    protected static ?string $modelLabel = 'pickup pipeline record';

    protected static ?string $pluralModelLabel = 'pickup pipeline';

    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Order')
                ->schema([
                    Infolists\Components\TextEntry::make('order.order_number')
                        ->label('Order #')
                        ->copyable(),
                    Infolists\Components\TextEntry::make('vendor.vendorApplication.shop_name')
                        ->label('Shop')
                        ->placeholder('—'),
                    Infolists\Components\TextEntry::make('vendor.email')
                        ->label('Vendor email')
                        ->copyable(),
                    Infolists\Components\TextEntry::make('status')
                        ->badge()
                        ->color(fn (VendorFulfillmentStatus $state): string => self::statusColor($state))
                        ->formatStateUsing(fn (VendorFulfillmentStatus $state): string => $state->label()),
                ])
                ->columns(2),
            Infolists\Components\Section::make('Pipeline timestamps')
                ->schema([
                    Infolists\Components\TextEntry::make('ready_for_pickup_at')
                        ->label('Ready for pickup')
                        ->dateTime()
                        ->placeholder('—'),
                    Infolists\Components\TextEntry::make('picked_up_at')
                        ->label('Picked up from vendor')
                        ->dateTime()
                        ->placeholder('—'),
                    Infolists\Components\TextEntry::make('received_at_warehouse_at')
                        ->label('Received at warehouse')
                        ->dateTime()
                        ->placeholder('—'),
                    Infolists\Components\TextEntry::make('shipped_to_customer_at')
                        ->label('Shipped to customer')
                        ->dateTime()
                        ->placeholder('—'),
                    Infolists\Components\TextEntry::make('delivered_at')
                        ->label('Delivered / received')
                        ->dateTime()
                        ->placeholder('—'),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('ready_for_pickup_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('order.order_number')
                    ->label('Order #')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('vendor.vendorApplication.shop_name')
                    ->label('Shop')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (VendorFulfillmentStatus $state): string => self::statusColor($state))
                    ->formatStateUsing(fn (VendorFulfillmentStatus $state): string => $state->label()),
                Tables\Columns\TextColumn::make('ready_for_pickup_at')
                    ->label('Ready')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('picked_up_at')
                    ->label('Picked up')
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('received_at_warehouse_at')
                    ->label('Warehouse')
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('shipped_to_customer_at')
                    ->label('Shipped')
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('delivered_at')
                    ->label('Delivered')
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(VendorFulfillmentStatus::cases())->mapWithKeys(
                        fn (VendorFulfillmentStatus $status) => [$status->value => $status->label()]
                    )->all()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('mark_picked_up')
                    ->label('Mark picked up')
                    ->icon('heroicon-o-truck')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (VendorOrderFulfillment $record): bool => $record->status === VendorFulfillmentStatus::ReadyForPickup)
                    ->action(function (VendorOrderFulfillment $record): void {
                        $record->update([
                            'status' => VendorFulfillmentStatus::PickedUp,
                            'picked_up_at' => now(),
                        ]);

                        Notification::make()->title('Marked as picked up')->success()->send();
                    }),
                Tables\Actions\Action::make('mark_received')
                    ->label('Mark warehouse received')
                    ->icon('heroicon-o-home-modern')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->visible(fn (VendorOrderFulfillment $record): bool => $record->status === VendorFulfillmentStatus::PickedUp)
                    ->action(function (VendorOrderFulfillment $record): void {
                        $record->update([
                            'status' => VendorFulfillmentStatus::ReceivedAtWarehouse,
                            'received_at_warehouse_at' => now(),
                        ]);

                        Notification::make()->title('Marked as received at warehouse')->success()->send();
                    }),
                Tables\Actions\Action::make('mark_shipped')
                    ->label('Mark shipped')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (VendorOrderFulfillment $record): bool => $record->status === VendorFulfillmentStatus::ReceivedAtWarehouse)
                    ->action(function (VendorOrderFulfillment $record): void {
                        $record->update([
                            'status' => VendorFulfillmentStatus::ShippedToCustomer,
                            'shipped_to_customer_at' => now(),
                        ]);

                        Notification::make()->title('Marked as shipped to customer')->success()->send();
                    }),
                Tables\Actions\Action::make('mark_delivered')
                    ->label('Mark delivered')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Confirm customer received order?')
                    ->modalDescription('This releases the vendor payout from escrow to wallet.')
                    ->visible(fn (VendorOrderFulfillment $record): bool => $record->status === VendorFulfillmentStatus::ShippedToCustomer)
                    ->action(function (VendorOrderFulfillment $record): void {
                        $record->markDelivered();

                        Notification::make()->title('Marked as delivered — escrow released')->success()->send();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['order', 'vendor.vendorApplication']);
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
            'index' => Pages\ListVendorOrderFulfillments::route('/'),
            'view' => Pages\ViewVendorOrderFulfillment::route('/{record}'),
        ];
    }

    private static function statusColor(VendorFulfillmentStatus $status): string
    {
        return match ($status) {
            VendorFulfillmentStatus::ReadyForPickup => 'warning',
            VendorFulfillmentStatus::PickedUp => 'primary',
            VendorFulfillmentStatus::ReceivedAtWarehouse => 'primary',
            VendorFulfillmentStatus::ShippedToCustomer => 'info',
            VendorFulfillmentStatus::Delivered => 'success',
        };
    }
}
