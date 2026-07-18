<?php

namespace App\Filament\Resources;

use App\Enums\PromoCodeType;
use App\Enums\PromoCostBearer;
use App\Filament\Resources\PromoCodeResource\Pages;
use App\Models\PromoCode;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PromoCodeResource extends Resource
{
    protected static ?string $model = PromoCode::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationLabel = 'Promo codes';

    protected static ?string $modelLabel = 'promo code';

    protected static ?string $pluralModelLabel = 'promo codes';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Code details')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->required()
                            ->maxLength(40)
                            ->unique(ignoreRecord: true)
                            ->dehydrateStateUsing(fn (?string $state): string => strtoupper(trim((string) $state)))
                            ->extraInputAttributes(['class' => 'uppercase'])
                            ->helperText('Customers enter this at checkout (e.g. WELCOME10).'),
                        Forms\Components\Select::make('type')
                            ->options(collect(PromoCodeType::cases())->mapWithKeys(
                                fn (PromoCodeType $type) => [$type->value => $type->label()]
                            )->all())
                            ->required()
                            ->live()
                            ->native(false),
                        Forms\Components\TextInput::make('value')
                            ->label(fn (Get $get): string => $get('type') === PromoCodeType::Fixed->value
                                ? 'Discount amount (GHS)'
                                : 'Discount (%)')
                            ->required()
                            ->numeric()
                            ->minValue(fn (Get $get): float => $get('type') === PromoCodeType::Fixed->value ? 0.01 : 1)
                            ->maxValue(fn (Get $get): ?float => $get('type') === PromoCodeType::Percent->value ? 100 : null)
                            ->step(fn (Get $get): string => $get('type') === PromoCodeType::Fixed->value ? '0.01' : '1')
                            ->dehydrateStateUsing(function ($state, Get $get): int {
                                if ($get('type') === PromoCodeType::Fixed->value) {
                                    return (int) round(((float) $state) * 100);
                                }

                                return (int) $state;
                            })
                            ->afterStateHydrated(function (Forms\Components\TextInput $component, $state, Get $get): void {
                                if ($get('type') === PromoCodeType::Fixed->value && $state !== null) {
                                    $component->state(number_format(((int) $state) / 100, 2, '.', ''));
                                }
                            })
                            ->helperText(fn (Get $get): string => $get('type') === PromoCodeType::Fixed->value
                                ? 'Fixed amount taken off the order subtotal.'
                                : 'Percentage taken off the order subtotal.'),
                        Forms\Components\Select::make('cost_bearer')
                            ->label('Promo cost borne by')
                            ->options(collect(PromoCostBearer::cases())->mapWithKeys(
                                fn (PromoCostBearer $bearer) => [$bearer->value => $bearer->label()]
                            )->all())
                            ->required()
                            ->default(PromoCostBearer::Mummish->value)
                            ->live()
                            ->native(false)
                            ->helperText(fn (Get $get): string => PromoCostBearer::tryFrom((string) $get('cost_bearer'))
                                ?->helperText()
                                ?? 'Choose who absorbs the customer discount.'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Inactive codes cannot be used at checkout.'),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Limits & schedule')
                    ->schema([
                        Forms\Components\TextInput::make('min_subtotal_cents')
                            ->label('Minimum order (GHS)')
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
                            ->helperText('Leave blank for no minimum.'),
                        Forms\Components\TextInput::make('max_uses')
                            ->label('Maximum uses')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->nullable()
                            ->helperText('Leave blank for unlimited uses.'),
                        Forms\Components\Placeholder::make('uses_count')
                            ->label('Times used')
                            ->content(fn (?PromoCode $record): string => $record ? (string) $record->uses_count : '0')
                            ->visibleOn('edit'),
                        Forms\Components\DateTimePicker::make('starts_at')
                            ->label('Starts at')
                            ->nullable()
                            ->native(false),
                        Forms\Components\DateTimePicker::make('ends_at')
                            ->label('Ends at')
                            ->nullable()
                            ->native(false)
                            ->after('starts_at'),
                    ])
                    ->columns(2),
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
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (PromoCodeType $state): string => $state->label()),
                Tables\Columns\TextColumn::make('value')
                    ->label('Discount')
                    ->formatStateUsing(fn (PromoCode $record): string => $record->description()),
                Tables\Columns\TextColumn::make('cost_bearer')
                    ->label('Cost borne by')
                    ->badge()
                    ->formatStateUsing(fn (PromoCostBearer $state): string => $state->label()),
                Tables\Columns\TextColumn::make('min_subtotal_cents')
                    ->label('Min. order')
                    ->formatStateUsing(fn (?int $state): string => $state
                        ? 'GHS '.number_format($state / 100, 2)
                        : '—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('uses_count')
                    ->label('Used')
                    ->formatStateUsing(fn (PromoCode $record): string => $record->max_uses
                        ? "{$record->uses_count} / {$record->max_uses}"
                        : (string) $record->uses_count),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('starts_at')
                    ->dateTime()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('ends_at')
                    ->dateTime()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
                Tables\Filters\SelectFilter::make('type')
                    ->options(collect(PromoCodeType::cases())->mapWithKeys(
                        fn (PromoCodeType $type) => [$type->value => $type->label()]
                    )->all()),
                Tables\Filters\SelectFilter::make('cost_bearer')
                    ->label('Cost borne by')
                    ->options(collect(PromoCostBearer::cases())->mapWithKeys(
                        fn (PromoCostBearer $bearer) => [$bearer->value => $bearer->label()]
                    )->all()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggle_active')
                    ->label(fn (PromoCode $record): string => $record->is_active ? 'Disable' : 'Enable')
                    ->icon(fn (PromoCode $record): string => $record->is_active ? 'heroicon-o-no-symbol' : 'heroicon-o-check-circle')
                    ->color(fn (PromoCode $record): string => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(fn (PromoCode $record) => $record->update([
                        'is_active' => ! $record->is_active,
                    ])),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPromoCodes::route('/'),
            'create' => Pages\CreatePromoCode::route('/create'),
            'edit' => Pages\EditPromoCode::route('/{record}/edit'),
        ];
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}
