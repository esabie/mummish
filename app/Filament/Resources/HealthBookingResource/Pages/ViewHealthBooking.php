<?php

namespace App\Filament\Resources\HealthBookingResource\Pages;

use App\Filament\Resources\HealthBookingResource;
use App\Models\HealthBooking;
use App\Services\HealthBookingSettlementService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewHealthBooking extends ViewRecord
{
    protected static string $resource = HealthBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('mark_professional_paid')
                ->label('Mark professional paid')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Mark professional payout paid?')
                ->modalDescription(fn (HealthBooking $record): string => 'Record that GHS '
                    .number_format(((int) $record->professional_payout_cents) / 100, 2)
                    .' for booking '.$record->reference.' has been paid to the professional.')
                ->visible(fn (HealthBooking $record): bool => $record->professionalPayoutIsDue())
                ->action(function (HealthBooking $record): void {
                    app(HealthBookingSettlementService::class)->markPaid($record);

                    Notification::make()
                        ->title('Professional payout marked paid')
                        ->success()
                        ->send();
                }),
            Actions\Action::make('mark_professional_unpaid')
                ->label('Undo professional paid')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Undo professional payout?')
                ->modalDescription(fn (HealthBooking $record): string => 'Clear the payout mark for booking '
                    .$record->reference.'. Use this only if the payout was recorded by mistake.')
                ->visible(fn (HealthBooking $record): bool => $record->isPaid() && $record->isProfessionalPaid())
                ->action(function (HealthBooking $record): void {
                    app(HealthBookingSettlementService::class)->markUnpaid($record);

                    Notification::make()
                        ->title('Professional payout marked unpaid')
                        ->success()
                        ->send();
                }),
        ];
    }
}
