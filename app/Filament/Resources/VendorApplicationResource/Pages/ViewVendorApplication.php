<?php

namespace App\Filament\Resources\VendorApplicationResource\Pages;

use App\Filament\Resources\VendorApplicationResource;
use App\Models\Product;
use App\Models\VendorApplication;
use App\Services\VendorApplicationReviewService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewVendorApplication extends ViewRecord
{
    protected static string $resource = VendorApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Approve vendor application')
                ->modalDescription(fn (VendorApplication $record): string => "Approve {$record->shop_name}? The vendor will be notified by SMS.")
                ->visible(fn (VendorApplication $record): bool => $record->isPending())
                ->action(function (VendorApplication $record): void {
                    app(VendorApplicationReviewService::class)->approve($record, auth()->user());

                    Notification::make()
                        ->title('Application approved')
                        ->success()
                        ->send();

                    $this->refreshFormData(['status', 'reviewed_at', 'reviewedBy', 'rejection_reason']);
                }),
            Actions\Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (VendorApplication $record): bool => $record->isPending())
                ->form([
                    Forms\Components\Textarea::make('rejection_reason')
                        ->label('Rejection reason')
                        ->required()
                        ->maxLength(2000)
                        ->rows(4),
                ])
                ->action(function (VendorApplication $record, array $data): void {
                    app(VendorApplicationReviewService::class)->reject(
                        $record,
                        auth()->user(),
                        $data['rejection_reason'],
                    );

                    Notification::make()
                        ->title('Application rejected')
                        ->success()
                        ->send();

                    $this->refreshFormData(['status', 'reviewed_at', 'reviewedBy', 'rejection_reason']);
                }),
            Actions\Action::make('closeDown')
                ->label('Close down')
                ->icon('heroicon-o-no-symbol')
                ->color('danger')
                ->visible(fn (VendorApplication $record): bool => $record->isApproved())
                ->requiresConfirmation()
                ->modalHeading('Close down vendor')
                ->modalDescription(function (VendorApplication $record): string {
                    $count = Product::query()->where('user_id', $record->user_id)->count();
                    $productLabel = $count === 1 ? '1 product' : "{$count} products";

                    return "Close {$record->shop_name}? All {$productLabel} created by this vendor will be permanently deleted from the website. This cannot be undone.";
                })
                ->modalSubmitActionLabel('Close vendor & delete products')
                ->form([
                    Forms\Components\Textarea::make('reason')
                        ->label('Reason (optional)')
                        ->maxLength(2000)
                        ->rows(3)
                        ->helperText('Shown to the vendor if provided.'),
                ])
                ->action(function (VendorApplication $record, array $data): void {
                    $deleted = app(VendorApplicationReviewService::class)->closeDown(
                        $record,
                        auth()->user(),
                        $data['reason'] ?? null,
                    );

                    Notification::make()
                        ->title('Vendor closed')
                        ->body($deleted === 1
                            ? '1 product was deleted from the website.'
                            : "{$deleted} products were deleted from the website.")
                        ->success()
                        ->send();

                    $this->refreshFormData(['status', 'reviewed_at', 'reviewedBy', 'rejection_reason']);
                }),
        ];
    }
}
