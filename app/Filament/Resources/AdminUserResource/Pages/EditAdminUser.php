<?php

namespace App\Filament\Resources\AdminUserResource\Pages;

use App\Filament\Resources\AdminUserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAdminUser extends EditRecord
{
    protected static string $resource = AdminUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn (): bool => auth()->id() !== $this->record->id)
                ->before(function (Actions\DeleteAction $action): void {
                    $adminCount = \App\Models\User::query()
                        ->where('role', \App\Enums\UserRole::Admin)
                        ->count();

                    if ($adminCount <= 1) {
                        $action->cancel();
                        \Filament\Notifications\Notification::make()
                            ->title('Cannot delete the last admin')
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
