<?php

namespace App\Filament\Resources\VendorApplicationResource\Pages;

use App\Filament\Resources\VendorApplicationResource;
use App\Models\Product;
use App\Models\VendorApplication;
use App\Services\VendorApplicationReviewService;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Validation\Rule;

class ViewVendorApplication extends ViewRecord
{
    protected static string $resource = VendorApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('updatePaymentDetails')
                ->label('Update payment details')
                ->icon('heroicon-o-banknotes')
                ->color('gray')
                ->fillForm(fn (VendorApplication $record): array => [
                    'payment_method' => $record->payment_method,
                    'bank_name' => $record->bank_name,
                    'bank_account_name' => $record->bank_account_name,
                    'bank_account_number' => $record->bank_account_number,
                    'mobile_money_provider' => $record->mobile_money_provider,
                    'mobile_money_name' => $record->mobile_money_name,
                    'mobile_money_number' => $record->mobile_money_number,
                ])
                ->form([
                    Forms\Components\Select::make('payment_method')
                        ->label('Payment method')
                        ->options([
                            'bank' => 'Bank',
                            'mobile_money' => 'Mobile money',
                        ])
                        ->required()
                        ->live()
                        ->native(false),
                    Forms\Components\Select::make('bank_name')
                        ->label('Bank name')
                        ->options(collect(config('ghana_banks.names', []))->mapWithKeys(
                            fn (string $name) => [$name => $name]
                        )->all())
                        ->searchable()
                        ->native(false)
                        ->visible(fn (Get $get): bool => $get('payment_method') === 'bank')
                        ->required(fn (Get $get): bool => $get('payment_method') === 'bank')
                        ->rule(fn (Get $get) => $get('payment_method') === 'bank'
                            ? Rule::in(config('ghana_banks.names', []))
                            : null),
                    Forms\Components\TextInput::make('bank_account_name')
                        ->label('Bank account name')
                        ->maxLength(120)
                        ->visible(fn (Get $get): bool => $get('payment_method') === 'bank')
                        ->required(fn (Get $get): bool => $get('payment_method') === 'bank'),
                    Forms\Components\TextInput::make('bank_account_number')
                        ->label('Bank account number')
                        ->maxLength(40)
                        ->visible(fn (Get $get): bool => $get('payment_method') === 'bank')
                        ->required(fn (Get $get): bool => $get('payment_method') === 'bank'),
                    Forms\Components\Select::make('mobile_money_provider')
                        ->label('Mobile money provider')
                        ->options([
                            'MTN MoMo' => 'MTN Mobile Money',
                            'Telecel Cash' => 'Telecel Cash',
                            'AirtelTigo Money' => 'AirtelTigo Money',
                        ])
                        ->native(false)
                        ->visible(fn (Get $get): bool => $get('payment_method') === 'mobile_money')
                        ->required(fn (Get $get): bool => $get('payment_method') === 'mobile_money'),
                    Forms\Components\TextInput::make('mobile_money_name')
                        ->label('Mobile money account name')
                        ->maxLength(120)
                        ->visible(fn (Get $get): bool => $get('payment_method') === 'mobile_money')
                        ->required(fn (Get $get): bool => $get('payment_method') === 'mobile_money'),
                    Forms\Components\TextInput::make('mobile_money_number')
                        ->label('Mobile money number')
                        ->maxLength(20)
                        ->regex('/^[\d\s+()-]+$/')
                        ->visible(fn (Get $get): bool => $get('payment_method') === 'mobile_money')
                        ->required(fn (Get $get): bool => $get('payment_method') === 'mobile_money'),
                ])
                ->requiresConfirmation()
                ->modalHeading('Update payment details')
                ->modalDescription('This will replace the vendor’s saved payout details. Confirm only if you have verified the new information.')
                ->modalSubmitActionLabel('Confirm update')
                ->action(function (VendorApplication $record, array $data): void {
                    $record->applyPayoutDetails($data);

                    Notification::make()
                        ->title('Payment details updated')
                        ->success()
                        ->send();

                    $this->refreshFormData([
                        'payment_method',
                        'bank_name',
                        'bank_account_name',
                        'bank_account_number',
                        'mobile_money_provider',
                        'mobile_money_name',
                        'mobile_money_number',
                    ]);
                }),
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
