<?php

namespace App\Filament\Resources;

use App\Enums\HealthProfessionalApprovalStatus;
use App\Filament\Resources\HealthProfessionalResource\Pages;
use App\Models\HealthProfessional;
use App\Services\HealthProfessionalReviewService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class HealthProfessionalResource extends Resource
{
    protected static ?string $model = HealthProfessional::class;

    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static ?string $navigationLabel = 'Health professionals';

    protected static ?string $modelLabel = 'health professional';

    protected static ?string $pluralModelLabel = 'health professionals';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Professional')
                    ->schema([
                        Infolists\Components\TextEntry::make('name'),
                        Infolists\Components\TextEntry::make('title'),
                        Infolists\Components\TextEntry::make('specialty'),
                        Infolists\Components\TextEntry::make('slug')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('location')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('experience')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('about')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Infolists\Components\Section::make('Contact')
                    ->schema([
                        Infolists\Components\TextEntry::make('email')
                            ->copyable()
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('phone')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('user.email')
                            ->label('Account email')
                            ->placeholder('—'),
                    ])
                    ->columns(3),
                Infolists\Components\Section::make('Payment details')
                    ->schema([
                        Infolists\Components\TextEntry::make('payment_method')
                            ->label('Status')
                            ->badge()
                            ->color(fn (HealthProfessional $record): string => $record->payment_method ? 'success' : 'danger')
                            ->formatStateUsing(fn (HealthProfessional $record): string => $record->payment_method
                                ? 'Saved'
                                : 'Payment details missing'),
                        Infolists\Components\TextEntry::make('payment_method')
                            ->label('Payment method')
                            ->formatStateUsing(fn (HealthProfessional $record): string => $record->paymentMethodLabel() ?? '—'),
                        Infolists\Components\TextEntry::make('bank_name')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('bank_account_name')
                            ->label('Bank account name')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('bank_account_number')
                            ->label('Bank account number')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('mobile_money_provider')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('mobile_money_name')
                            ->label('Mobile money account name')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('mobile_money_number')
                            ->label('Mobile money number')
                            ->placeholder('—'),
                    ])
                    ->columns(2),
                Infolists\Components\Section::make('Listing')
                    ->schema([
                        Infolists\Components\TextEntry::make('visit_modes')
                            ->formatStateUsing(function (mixed $state): string {
                                $modes = match (true) {
                                    is_array($state) => $state,
                                    is_string($state) && $state !== '' => (json_decode($state, true) ?? preg_split('/\s*,\s*/', $state) ?: []),
                                    default => [],
                                };

                                $modes = array_values(array_filter(array_map(
                                    static fn ($mode) => is_scalar($mode) ? trim((string) $mode) : '',
                                    $modes,
                                )));

                                return $modes !== [] ? implode(', ', $modes) : '—';
                            }),
                        Infolists\Components\TextEntry::make('services_count')
                            ->label('Services')
                            ->state(fn (HealthProfessional $record): int => $record->services()->count()),
                        Infolists\Components\TextEntry::make('availability_count')
                            ->label('Availability windows')
                            ->state(fn (HealthProfessional $record): int => $record->availability()->count()),
                        Infolists\Components\IconEntry::make('is_active')
                            ->label('Self-listed as active')
                            ->boolean(),
                    ])
                    ->columns(2),
                Infolists\Components\Section::make('Review')
                    ->schema([
                        Infolists\Components\TextEntry::make('approval_status')
                            ->badge()
                            ->color(fn (HealthProfessionalApprovalStatus $state): string => match ($state) {
                                HealthProfessionalApprovalStatus::Pending => 'warning',
                                HealthProfessionalApprovalStatus::Approved => 'success',
                                HealthProfessionalApprovalStatus::Rejected => 'danger',
                                HealthProfessionalApprovalStatus::Suspended => 'gray',
                            })
                            ->formatStateUsing(fn (HealthProfessionalApprovalStatus $state): string => $state->label()),
                        Infolists\Components\TextEntry::make('rejection_reason')
                            ->label(fn (HealthProfessional $record): string => $record->approval_status === HealthProfessionalApprovalStatus::Suspended
                                ? 'Suspension reason'
                                : 'Rejection reason')
                            ->visible(fn (HealthProfessional $record): bool => in_array($record->approval_status, [
                                HealthProfessionalApprovalStatus::Rejected,
                                HealthProfessionalApprovalStatus::Suspended,
                            ], true))
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('reviewed_at')
                            ->dateTime()
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('reviewedBy.name')
                            ->label('Reviewed by')
                            ->placeholder('—'),
                    ])
                    ->columns(2),
                Infolists\Components\Section::make('Timestamps')
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')->dateTime(),
                        Infolists\Components\TextEntry::make('updated_at')->dateTime(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('specialty')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('phone')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Payment')
                    ->badge()
                    ->color(fn (HealthProfessional $record): string => $record->payment_method ? 'success' : 'danger')
                    ->formatStateUsing(fn (HealthProfessional $record): string => $record->payment_method
                        ? $record->paymentMethodLabel()
                        : 'Payment details missing'),
                Tables\Columns\TextColumn::make('location')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('approval_status')
                    ->label('Approval')
                    ->badge()
                    ->color(fn (HealthProfessionalApprovalStatus $state): string => match ($state) {
                        HealthProfessionalApprovalStatus::Pending => 'warning',
                        HealthProfessionalApprovalStatus::Approved => 'success',
                        HealthProfessionalApprovalStatus::Rejected => 'danger',
                        HealthProfessionalApprovalStatus::Suspended => 'gray',
                    })
                    ->formatStateUsing(fn (HealthProfessionalApprovalStatus $state): string => $state->label()),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('approval_status')
                    ->label('Approval')
                    ->options(collect(HealthProfessionalApprovalStatus::cases())->mapWithKeys(
                        fn (HealthProfessionalApprovalStatus $status) => [$status->value => $status->label()]
                    )->all()),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('approve')
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
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (HealthProfessional $record): bool => $record->isPendingApproval())
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Rejection reason')
                            ->required()
                            ->maxLength(2000)
                            ->rows(4)
                            ->helperText('This reason is sent to the professional by SMS.'),
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
                    }),
                Tables\Actions\Action::make('suspend')
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
                            ->rows(3)
                            ->helperText('Shown to the professional if provided.'),
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
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user', 'reviewedBy']);
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
            'index' => Pages\ListHealthProfessionals::route('/'),
            'view' => Pages\ViewHealthProfessional::route('/{record}'),
        ];
    }
}
