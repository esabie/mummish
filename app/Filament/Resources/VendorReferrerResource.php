<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VendorReferrerResource\Pages;
use App\Models\VendorReferrer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class VendorReferrerResource extends Resource
{
    protected static ?string $model = VendorReferrer::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';

    protected static ?string $navigationLabel = 'Vendor referrers';

    protected static ?string $modelLabel = 'vendor referrer';

    protected static ?string $pluralModelLabel = 'vendor referrers';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Referrer')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->required()
                            ->maxLength(50)
                            ->unique(ignoreRecord: true)
                            ->dehydrateStateUsing(fn (?string $state): string => strtoupper(trim((string) $state)))
                            ->extraInputAttributes(['class' => 'uppercase'])
                            ->helperText('Vendors use this at /sell or via /sell?ref=CODE'),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(150),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(20),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                        Forms\Components\Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Rewards')
                    ->description('Leave blank to use marketplace defaults from config.')
                    ->schema([
                        Forms\Components\TextInput::make('registration_reward_cents')
                            ->label('Registration bonus (GHS)')
                            ->numeric()
                            ->minValue(0)
                            ->step('0.01')
                            ->nullable()
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? (int) round(((float) $state) * 100) : null)
                            ->afterStateHydrated(function (Forms\Components\TextInput $component, $state): void {
                                if ($state !== null) {
                                    $component->state(number_format(((int) $state) / 100, 2, '.', ''));
                                }
                            })
                            ->helperText('Paid when a referred vendor is approved.'),
                        Forms\Components\TextInput::make('transaction_commission_bps')
                            ->label('Sales commission (%)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step('0.01')
                            ->nullable()
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? (int) round(((float) $state) * 100) : null)
                            ->afterStateHydrated(function (Forms\Components\TextInput $component, $state): void {
                                if ($state !== null) {
                                    $component->state(number_format(((int) $state) / 100, 2, '.', ''));
                                }
                            })
                            ->helperText('Percentage of each referred vendor sale, paid when orders are paid.'),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Share link')
                    ->schema([
                        Forms\Components\Placeholder::make('share_url')
                            ->label('Vendor sign-up link')
                            ->content(fn (?VendorReferrer $record): string => $record?->shareUrl() ?? 'Save to generate link'),
                    ])
                    ->visibleOn('edit'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('registration_reward_cents')
                    ->label('Registration bonus')
                    ->formatStateUsing(fn (VendorReferrer $record): string => $record->formattedRegistrationReward()),
                Tables\Columns\TextColumn::make('transaction_commission_bps')
                    ->label('Commission')
                    ->formatStateUsing(fn (VendorReferrer $record): string => $record->formattedCommissionRate()),
                Tables\Columns\TextColumn::make('applications_count')
                    ->counts('applications')
                    ->label('Vendors referred'),
                Tables\Columns\TextColumn::make('rewards_sum_amount_cents')
                    ->label('Total earned')
                    ->formatStateUsing(fn (?int $state): string => 'GHS '.number_format(($state ?? 0) / 100, 2)),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVendorReferrers::route('/'),
            'create' => Pages\CreateVendorReferrer::route('/create'),
            'edit' => Pages\EditVendorReferrer::route('/{record}/edit'),
        ];
    }
}
