<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use App\Services\CourierSettlementService;
use App\Services\VendorSettlementService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('mark_courier_paid')
                ->label('Mark courier paid')
                ->icon('heroicon-o-truck')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Mark courier fee paid?')
                ->modalDescription(fn (Order $record): string => 'Record that the delivery fee of GHS '
                    .number_format($record->shipping_cents / 100, 2)
                    .' for this order has been paid to the courier.')
                ->visible(fn (Order $record): bool => $record->courierFeeIsDue())
                ->action(function (Order $record): void {
                    app(CourierSettlementService::class)->markPaid($record);

                    Notification::make()
                        ->title('Courier fee marked paid')
                        ->success()
                        ->send();
                }),
            Actions\Action::make('mark_courier_unpaid')
                ->label('Undo courier paid')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('gray')
                ->requiresConfirmation()
                ->visible(fn (Order $record): bool => $record->isPaid()
                    && $record->hasCourierFee()
                    && $record->isCourierPaid())
                ->action(function (Order $record): void {
                    app(CourierSettlementService::class)->markUnpaid($record);

                    Notification::make()
                        ->title('Courier fee marked unpaid')
                        ->success()
                        ->send();
                }),
            Actions\Action::make('mark_vendor_paid')
                ->label('Mark vendor paid')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Mark vendor payout paid?')
                ->modalDescription(fn (Order $record): string => 'Record that the vendor payout for this order has been paid out.')
                ->visible(fn (Order $record): bool => $record->vendorPayoutIsDue())
                ->action(function (Order $record): void {
                    app(VendorSettlementService::class)->markPaid($record);

                    Notification::make()
                        ->title('Vendor payout marked paid')
                        ->success()
                        ->send();
                }),
            Actions\Action::make('mark_vendor_unpaid')
                ->label('Undo vendor paid')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('gray')
                ->requiresConfirmation()
                ->visible(fn (Order $record): bool => $record->isPaid()
                    && $record->hasReleasedEscrow()
                    && $record->isVendorPaid())
                ->action(function (Order $record): void {
                    app(VendorSettlementService::class)->markUnpaid($record);

                    Notification::make()
                        ->title('Vendor payout marked unpaid')
                        ->success()
                        ->send();
                }),
        ];
    }
}
