<?php

namespace App\Filament\Resources;

use App\Enums\VendorApplicationStatus;
use App\Filament\Resources\VendorApplicationResource\Pages;
use App\Models\Product;
use App\Models\VendorApplication;
use App\Services\VendorApplicationReviewService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VendorApplicationResource extends Resource
{
    protected static ?string $model = VendorApplication::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationLabel = 'Vendor applications';

    protected static ?string $modelLabel = 'vendor application';

    protected static ?string $pluralModelLabel = 'vendor applications';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Applicant')
                    ->schema([
                        Infolists\Components\TextEntry::make('first_name'),
                        Infolists\Components\TextEntry::make('last_name'),
                        Infolists\Components\TextEntry::make('business_email'),
                        Infolists\Components\TextEntry::make('phone'),
                        Infolists\Components\TextEntry::make('ghana_card_id')
                            ->label('Ghana Card ID'),
                        Infolists\Components\TextEntry::make('user.email')
                            ->label('Account email'),
                    ])
                    ->columns(2),
                Infolists\Components\Section::make('Shop')
                    ->schema([
                        Infolists\Components\TextEntry::make('shop_name'),
                        Infolists\Components\TextEntry::make('category'),
                        Infolists\Components\TextEntry::make('referral_code')
                            ->label('Referral code')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('referrer.name')
                            ->label('Referred by')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('terms_accepted')
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Yes' : 'No'),
                    ])
                    ->columns(2),
                Infolists\Components\Section::make('Review')
                    ->schema([
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn (VendorApplicationStatus $state): string => match ($state) {
                                VendorApplicationStatus::Pending => 'warning',
                                VendorApplicationStatus::Approved => 'success',
                                VendorApplicationStatus::Rejected => 'danger',
                                VendorApplicationStatus::Closed => 'gray',
                            })
                            ->formatStateUsing(fn (VendorApplicationStatus $state): string => $state->label()),
                        Infolists\Components\TextEntry::make('rejection_reason')
                            ->label(fn (VendorApplication $record): string => $record->isClosed() ? 'Close reason' : 'Rejection reason')
                            ->visible(fn (VendorApplication $record): bool => in_array($record->status, [
                                VendorApplicationStatus::Rejected,
                                VendorApplicationStatus::Closed,
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
                Tables\Columns\TextColumn::make('shop_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('first_name')
                    ->label('Name')
                    ->formatStateUsing(fn (VendorApplication $record): string => trim("{$record->first_name} {$record->last_name}"))
                    ->searchable(['first_name', 'last_name']),
                Tables\Columns\TextColumn::make('business_email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone'),
                Tables\Columns\TextColumn::make('ghana_card_id')
                    ->label('Ghana Card ID')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('category'),
                Tables\Columns\TextColumn::make('referral_code')
                    ->label('Referral')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('referrer.name')
                    ->label('Referrer')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (VendorApplicationStatus $state): string => match ($state) {
                        VendorApplicationStatus::Pending => 'warning',
                        VendorApplicationStatus::Approved => 'success',
                        VendorApplicationStatus::Rejected => 'danger',
                        VendorApplicationStatus::Closed => 'gray',
                    })
                    ->formatStateUsing(fn (VendorApplicationStatus $state): string => $state->label()),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(VendorApplicationStatus::cases())->mapWithKeys(
                        fn (VendorApplicationStatus $status) => [$status->value => $status->label()]
                    )->all()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('approve')
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
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (VendorApplication $record): bool => $record->isPending())
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Rejection reason')
                            ->required()
                            ->maxLength(2000)
                            ->rows(4)
                            ->helperText('This reason is sent to the vendor by SMS.'),
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
                    }),
                Tables\Actions\Action::make('closeDown')
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
            'index' => Pages\ListVendorApplications::route('/'),
            'view' => Pages\ViewVendorApplication::route('/{record}'),
        ];
    }
}
