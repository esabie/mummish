<?php

namespace App\Filament\Resources;

use App\Enums\VendorReferralRewardStatus;
use App\Enums\VendorReferralRewardType;
use App\Filament\Resources\VendorReferralRewardResource\Pages;
use App\Models\VendorReferralReward;
use App\Services\VendorReferralRewardService;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class VendorReferralRewardResource extends Resource
{
    protected static ?string $model = VendorReferralReward::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Referral rewards';

    protected static ?string $modelLabel = 'referral reward';

    protected static ?string $pluralModelLabel = 'referral rewards';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('referrer.name')
                    ->label('Referrer')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('referrer.code')
                    ->label('Code')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (VendorReferralRewardType $state): string => $state->label()),
                Tables\Columns\TextColumn::make('amount_cents')
                    ->label('Amount')
                    ->formatStateUsing(fn (VendorReferralReward $record): string => $record->formattedAmount())
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->wrap(),
                Tables\Columns\TextColumn::make('application.shop_name')
                    ->label('Vendor')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('order.order_number')
                    ->label('Order')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (VendorReferralRewardStatus $state): string => match ($state) {
                        VendorReferralRewardStatus::Pending => 'warning',
                        VendorReferralRewardStatus::Paid => 'success',
                    })
                    ->formatStateUsing(fn (VendorReferralRewardStatus $state): string => $state->label()),
                Tables\Columns\TextColumn::make('paid_at')
                    ->dateTime()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(VendorReferralRewardStatus::cases())->mapWithKeys(
                        fn (VendorReferralRewardStatus $status) => [$status->value => $status->label()]
                    )->all()),
                Tables\Filters\SelectFilter::make('type')
                    ->options(collect(VendorReferralRewardType::cases())->mapWithKeys(
                        fn (VendorReferralRewardType $type) => [$type->value => $type->label()]
                    )->all()),
                Tables\Filters\SelectFilter::make('vendor_referrer_id')
                    ->label('Referrer')
                    ->relationship('referrer', 'name'),
            ])
            ->actions([
                Tables\Actions\Action::make('mark_paid')
                    ->label('Mark paid')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (VendorReferralReward $record): bool => $record->status === VendorReferralRewardStatus::Pending)
                    ->action(function (VendorReferralReward $record): void {
                        app(VendorReferralRewardService::class)->markPaid($record);

                        Notification::make()
                            ->title('Reward marked paid')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('mark_paid')
                    ->label('Mark selected as paid')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function ($records): void {
                        app(VendorReferralRewardService::class)->markManyPaid($records);

                        Notification::make()
                            ->title('Rewards marked paid')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVendorReferralRewards::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
