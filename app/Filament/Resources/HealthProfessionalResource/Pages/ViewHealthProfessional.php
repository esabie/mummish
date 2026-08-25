<?php

namespace App\Filament\Resources\HealthProfessionalResource\Pages;

use App\Enums\HealthProfessionalApprovalStatus;
use App\Filament\Resources\HealthProfessionalResource;
use App\Models\HealthProfessional;
use App\Services\HealthProfessionalReviewService;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Validation\Rule;

class ViewHealthProfessional extends ViewRecord
{
    protected static string $resource = HealthProfessionalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('updatePaymentDetails')
                ->label('Update payment details')
                ->icon('heroicon-o-banknotes')
                ->color('gray')
                ->fillForm(fn (HealthProfessional $record): array => [
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
                ->modalDescription('This will replace the professional’s saved payout details. Confirm only if you have verified the new information.')
                ->modalSubmitActionLabel('Confirm update')
                ->action(function (HealthProfessional $record, array $data): void {
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
                ->modalHeading('Approve health professional')
                ->modalDescription(fn (HealthProfessional $record): string => "Approve {$record->name}? Their profile will go live and they will be notified by SMS.")
                ->visible(fn (HealthProfessional $record): bool => in_array($record->approval_status, [
                    HealthProfessionalApprovalStatus::Pending,
                    HealthProfessionalApprovalStatus::Rejected,
                    HealthProfessionalApprovalStatus::Suspended,
                ], true))
                ->action(function (HealthProfessional $record): void {
                    app(HealthProfessionalReviewService::class)->approve($record, auth()->user());

                    Notification::make()
                        ->title('Professional approved')
                        ->success()
                        ->send();

                    $this->refreshFormData([
                        'approval_status',
                        'is_active',
                        'reviewed_at',
                        'reviewedBy',
                        'rejection_reason',
                    ]);
                }),
            Actions\Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (HealthProfessional $record): bool => $record->isPendingApproval())
                ->form([
                    Forms\Components\Textarea::make('rejection_reason')
                        ->label('Rejection reason')
                        ->required()
                        ->maxLength(2000)
                        ->rows(4),
                ])
                ->action(function (HealthProfessional $record, array $data): void {
                    app(HealthProfessionalReviewService::class)->reject(
                        $record,
                        auth()->user(),
                        $data['rejection_reason'],
                    );

                    Notification::make()
                        ->title('Professional rejected')
                        ->success()
                        ->send();

                    $this->refreshFormData([
                        'approval_status',
                        'is_active',
                        'reviewed_at',
                        'reviewedBy',
                        'rejection_reason',
                    ]);
                }),
            Actions\Action::make('suspend')
                ->label('Suspend')
                ->icon('heroicon-o-no-symbol')
                ->color('danger')
                ->visible(fn (HealthProfessional $record): bool => $record->isApproved())
                ->requiresConfirmation()
                ->modalHeading('Suspend health professional')
                ->modalDescription(fn (HealthProfessional $record): string => "Suspend {$record->name}? Their profile will be hidden from Health Services.")
                ->form([
                    Forms\Components\Textarea::make('reason')
                        ->label('Reason (optional)')
                        ->maxLength(2000)
                        ->rows(3),
                ])
                ->action(function (HealthProfessional $record, array $data): void {
                    app(HealthProfessionalReviewService::class)->suspend(
                        $record,
                        auth()->user(),
                        $data['reason'] ?? null,
                    );

                    Notification::make()
                        ->title('Professional suspended')
                        ->success()
                        ->send();

                    $this->refreshFormData([
                        'approval_status',
                        'is_active',
                        'reviewed_at',
                        'reviewedBy',
                        'rejection_reason',
                    ]);
                }),
        ];
    }
}
