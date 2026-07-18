<?php

namespace App\Filament\Resources\VendorOrderFulfillmentResource\Pages;

use App\Enums\VendorFulfillmentStatus;
use App\Filament\Resources\VendorOrderFulfillmentResource;
use App\Models\VendorOrderFulfillment;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewVendorOrderFulfillment extends ViewRecord
{
    protected static string $resource = VendorOrderFulfillmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('mark_picked_up')
                ->label('Mark picked up')
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn (VendorOrderFulfillment $record): bool => $record->status === VendorFulfillmentStatus::ReadyForPickup)
                ->action(function (VendorOrderFulfillment $record): void {
                    $record->update([
                        'status' => VendorFulfillmentStatus::PickedUp,
                        'picked_up_at' => now(),
                    ]);

                    Notification::make()->title('Marked as picked up')->success()->send();
                    $this->refreshFormData(['status', 'picked_up_at']);
                }),
            Actions\Action::make('mark_received')
                ->label('Mark warehouse received')
                ->color('primary')
                ->requiresConfirmation()
                ->visible(fn (VendorOrderFulfillment $record): bool => $record->status === VendorFulfillmentStatus::PickedUp)
                ->action(function (VendorOrderFulfillment $record): void {
                    $record->update([
                        'status' => VendorFulfillmentStatus::ReceivedAtWarehouse,
                        'received_at_warehouse_at' => now(),
                    ]);

                    Notification::make()->title('Marked as received at warehouse')->success()->send();
                    $this->refreshFormData(['status', 'received_at_warehouse_at']);
                }),
            Actions\Action::make('mark_shipped')
                ->label('Mark shipped')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (VendorOrderFulfillment $record): bool => $record->status === VendorFulfillmentStatus::ReceivedAtWarehouse)
                ->action(function (VendorOrderFulfillment $record): void {
                    $record->update([
                        'status' => VendorFulfillmentStatus::ShippedToCustomer,
                        'shipped_to_customer_at' => now(),
                    ]);

                    Notification::make()->title('Marked as shipped to customer')->success()->send();
                    $this->refreshFormData(['status', 'shipped_to_customer_at']);
                }),
            Actions\Action::make('mark_delivered')
                ->label('Mark delivered')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Confirm customer received order?')
                ->modalDescription('This releases the vendor payout from escrow to wallet.')
                ->visible(fn (VendorOrderFulfillment $record): bool => $record->status === VendorFulfillmentStatus::ShippedToCustomer)
                ->action(function (VendorOrderFulfillment $record): void {
                    $record->markDelivered();

                    Notification::make()->title('Marked as delivered — escrow released')->success()->send();
                    $this->refreshFormData(['status', 'delivered_at']);
                }),
        ];
    }
}
