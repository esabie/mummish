<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HealthBookingResource\Pages;
use App\Models\HealthBooking;
use App\Services\HealthBookingSettlementService;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class HealthBookingResource extends Resource
{
    protected static ?string $model = HealthBooking::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Health bookings';

    protected static ?string $modelLabel = 'health booking';

    protected static ?string $pluralModelLabel = 'health bookings';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Booking')
                    ->schema([
                        Infolists\Components\TextEntry::make('reference')
                            ->label('Reference')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'awaiting_payment' => 'warning',
                                'pending' => 'warning',
                                'confirmed' => 'success',
                                'completed' => 'success',
                                'cancelled' => 'gray',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => str_replace('_', ' ', ucfirst($state))),
                        Infolists\Components\TextEntry::make('visit_mode')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('appointment_date')
                            ->date(),
                        Infolists\Components\TextEntry::make('appointment_time')
                            ->formatStateUsing(fn (?string $state): string => $state
                                ? \Illuminate\Support\Carbon::parse($state)->format('g:i A')
                                : '—'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->dateTime(),
                    ])
                    ->columns(3),
                Infolists\Components\Section::make('Patient')
                    ->schema([
                        Infolists\Components\TextEntry::make('patient_name'),
                        Infolists\Components\TextEntry::make('patient_email')
                            ->copyable()
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('patient_phone')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('notes')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
                Infolists\Components\Section::make('Professional')
                    ->schema([
                        Infolists\Components\TextEntry::make('professional.name')
                            ->label('Name'),
                        Infolists\Components\TextEntry::make('professional.specialty')
                            ->label('Specialty')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('service.name')
                            ->label('Service')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('professional.payment_method')
                            ->label('Payout method')
                            ->formatStateUsing(fn (?string $state, HealthBooking $record): string => $record->professional?->paymentMethodLabel()
                                ?? 'Payment details missing'),
                        Infolists\Components\TextEntry::make('professional.bank_name')
                            ->label('Bank')
                            ->placeholder('—')
                            ->visible(fn (HealthBooking $record): bool => $record->professional?->payment_method === 'bank'),
                        Infolists\Components\TextEntry::make('professional.bank_account_name')
                            ->label('Bank account name')
                            ->placeholder('—')
                            ->visible(fn (HealthBooking $record): bool => $record->professional?->payment_method === 'bank'),
                        Infolists\Components\TextEntry::make('professional.bank_account_number')
                            ->label('Bank account number')
                            ->placeholder('—')
                            ->visible(fn (HealthBooking $record): bool => $record->professional?->payment_method === 'bank'),
                        Infolists\Components\TextEntry::make('professional.mobile_money_provider')
                            ->label('MoMo provider')
                            ->placeholder('—')
                            ->visible(fn (HealthBooking $record): bool => $record->professional?->payment_method === 'mobile_money'),
                        Infolists\Components\TextEntry::make('professional.mobile_money_name')
                            ->label('MoMo account name')
                            ->placeholder('—')
                            ->visible(fn (HealthBooking $record): bool => $record->professional?->payment_method === 'mobile_money'),
                        Infolists\Components\TextEntry::make('professional.mobile_money_number')
                            ->label('MoMo number')
                            ->placeholder('—')
                            ->visible(fn (HealthBooking $record): bool => $record->professional?->payment_method === 'mobile_money'),
                    ])
                    ->columns(3),
                Infolists\Components\Section::make('Payment')
                    ->schema([
                        Infolists\Components\TextEntry::make('payment_status')
                            ->badge()
                            ->color(fn (?string $state): string => match ($state) {
                                'paid' => 'success',
                                'pending' => 'warning',
                                'failed' => 'danger',
                                'expired' => 'gray',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (?string $state): string => $state
                                ? ucfirst($state)
                                : '—'),
                        Infolists\Components\TextEntry::make('amount_cents')
                            ->label('Amount')
                            ->formatStateUsing(fn (?int $state): string => $state !== null
                                ? 'GHS '.number_format($state / 100, 2)
                                : '—'),
                        Infolists\Components\TextEntry::make('commission_cents')
                            ->label('Commission')
                            ->formatStateUsing(fn (?int $state): string => $state !== null
                                ? 'GHS '.number_format($state / 100, 2)
                                : '—'),
                        Infolists\Components\TextEntry::make('professional_payout_cents')
                            ->label('Professional payout')
                            ->formatStateUsing(fn (?int $state): string => $state !== null
                                ? 'GHS '.number_format($state / 100, 2)
                                : '—'),
                        Infolists\Components\TextEntry::make('paystack_reference')
                            ->label('Paystack reference')
                            ->copyable()
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('paid_at')
                            ->dateTime()
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('payment_expires_at')
                            ->dateTime()
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('professional_paid_at')
                            ->label('Payout marked')
                            ->dateTime()
                            ->placeholder('Not paid out'),
                    ])
                    ->columns(3),
                Infolists\Components\Section::make('Lifecycle')
                    ->schema([
                        Infolists\Components\TextEntry::make('confirmed_at')
                            ->dateTime()
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('cancelled_at')
                            ->dateTime()
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('cancellation_reason')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('professional.name')
                    ->label('Professional')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('patient_name')
                    ->label('Patient')
                    ->searchable(),
                Tables\Columns\TextColumn::make('appointment_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('appointment_time')
                    ->label('Time')
                    ->formatStateUsing(fn (?string $state): string => $state
                        ? \Illuminate\Support\Carbon::parse($state)->format('g:i A')
                        : '—'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'awaiting_payment' => 'warning',
                        'pending' => 'warning',
                        'confirmed' => 'success',
                        'completed' => 'success',
                        'cancelled' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => str_replace('_', ' ', ucfirst($state))),
                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Payment')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'paid' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        'expired' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => $state ? ucfirst($state) : '—'),
                Tables\Columns\TextColumn::make('amount_cents')
                    ->label('Amount')
                    ->formatStateUsing(fn (?int $state): string => $state !== null
                        ? 'GHS '.number_format($state / 100, 2)
                        : '—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('professional_paid_at')
                    ->label('Pro payout')
                    ->badge()
                    ->getStateUsing(function (HealthBooking $record): string {
                        if ($record->isProfessionalPaid()) {
                            return 'Paid out';
                        }

                        if ($record->professionalPayoutIsDue()) {
                            return 'Due';
                        }

                        return '—';
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Paid out' => 'success',
                        'Due' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'awaiting_payment' => 'Awaiting payment',
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('Payment')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'failed' => 'Failed',
                        'expired' => 'Expired',
                    ]),
                Tables\Filters\Filter::make('professional_payout_due')
                    ->label('Professional payout due')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('payment_status', 'paid')
                        ->where('status', 'completed')
                        ->whereNull('professional_paid_at')
                        ->where('professional_payout_cents', '>', 0)),
                Tables\Filters\TernaryFilter::make('professional_paid_at')
                    ->label('Professional payout marked')
                    ->nullable()
                    ->trueLabel('Paid out')
                    ->falseLabel('Not paid out')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('professional_paid_at'),
                        false: fn (Builder $query) => $query->whereNull('professional_paid_at'),
                        blank: fn (Builder $query) => $query,
                    ),
                Tables\Filters\SelectFilter::make('health_professional_id')
                    ->label('Professional')
                    ->relationship('professional', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('mark_professional_paid')
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
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('mark_professional_paid')
                    ->label('Mark professional paid')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records): void {
                        $count = app(HealthBookingSettlementService::class)->markManyPaid($records);

                        Notification::make()
                            ->title($count === 1
                                ? '1 professional payout marked paid'
                                : "{$count} professional payouts marked paid")
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['professional', 'service']);
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
            'index' => Pages\ListHealthBookings::route('/'),
            'view' => Pages\ViewHealthBooking::route('/{record}'),
        ];
    }
}
