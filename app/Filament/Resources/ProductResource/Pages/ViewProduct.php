<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Enums\ProductStatus;
use App\Filament\Resources\ProductResource;
use App\Models\Product;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewProduct extends ViewRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('publish')
                ->label('Publish')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Publish product')
                ->modalDescription(fn (Product $record): string => "Make \"{$record->title}\" visible in the shop?")
                ->visible(fn (Product $record): bool => $record->status === ProductStatus::Draft)
                ->action(function (Product $record): void {
                    $record->update(['status' => ProductStatus::Active]);

                    Notification::make()
                        ->title('Product published')
                        ->success()
                        ->send();

                    $this->refreshFormData(['status']);
                }),
            Actions\Action::make('unpublish')
                ->label('Unpublish')
                ->icon('heroicon-o-no-symbol')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Unpublish product')
                ->modalDescription(fn (Product $record): string => "Remove \"{$record->title}\" from the shop? The vendor keeps the listing as a draft.")
                ->visible(fn (Product $record): bool => $record->status === ProductStatus::Active)
                ->action(function (Product $record): void {
                    $record->update(['status' => ProductStatus::Draft]);

                    Notification::make()
                        ->title('Product unpublished')
                        ->success()
                        ->send();

                    $this->refreshFormData(['status']);
                }),
        ];
    }
}
