<?php

namespace App\Filament\Resources;

use App\Enums\ProductStatus;
use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'Products';

    protected static ?string $modelLabel = 'product';

    protected static ?string $pluralModelLabel = 'products';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Listing')
                    ->schema([
                        Infolists\Components\ImageEntry::make('image_url')
                            ->label('Image')
                            ->state(fn (Product $record): string => $record->shopImageUrl())
                            ->height(120),
                        Infolists\Components\TextEntry::make('title'),
                        Infolists\Components\TextEntry::make('sku')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn (ProductStatus $state): string => match ($state) {
                                ProductStatus::Active => 'success',
                                ProductStatus::Draft => 'gray',
                            })
                            ->formatStateUsing(fn (ProductStatus $state): string => $state->label()),
                        Infolists\Components\TextEntry::make('category')
                            ->label('Category')
                            ->state(fn (Product $record): string => $record->categoryLabel()),
                        Infolists\Components\TextEntry::make('brand')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('condition')
                            ->label('Condition')
                            ->state(fn (Product $record): string => $record->conditionLabel()),
                        Infolists\Components\TextEntry::make('clothing_size')
                            ->label('Size')
                            ->state(fn (Product $record): ?string => $record->clothingSizeLabel())
                            ->placeholder('—'),
                    ])
                    ->columns(2),
                Infolists\Components\Section::make('Vendor')
                    ->schema([
                        Infolists\Components\TextEntry::make('user.vendorApplication.shop_name')
                            ->label('Shop name')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('user.email')
                            ->label('Vendor account'),
                    ])
                    ->columns(2),
                Infolists\Components\Section::make('Pricing & inventory')
                    ->schema([
                        Infolists\Components\TextEntry::make('price_cents')
                            ->label('Price')
                            ->formatStateUsing(fn (int $state): string => 'GHS '.number_format($state / 100, 2)),
                        Infolists\Components\TextEntry::make('compare_at_price_cents')
                            ->label('Compare-at price')
                            ->formatStateUsing(fn (?int $state): string => $state
                                ? 'GHS '.number_format($state / 100, 2)
                                : '—'),
                        Infolists\Components\TextEntry::make('stock_quantity')
                            ->label('Stock'),
                        Infolists\Components\IconEntry::make('allows_customization')
                            ->label('Customization allowed')
                            ->boolean(),
                    ])
                    ->columns(2),
                Infolists\Components\Section::make('Description')
                    ->schema([
                        Infolists\Components\TextEntry::make('description')
                            ->placeholder('No description provided.')
                            ->columnSpanFull()
                            ->prose(),
                    ])
                    ->collapsible(),
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
            ->defaultSort('updated_at', 'desc')
            ->columns([
                Tables\Columns\ImageColumn::make('image_url')
                    ->label('Image')
                    ->getStateUsing(fn (Product $record): string => $record->shopImageUrl())
                    ->height(40)
                    ->width(40),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('user.vendorApplication.shop_name')
                    ->label('Shop')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('sku')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('category')
                    ->label('Category')
                    ->formatStateUsing(fn ($state, Product $record): string => $record->categoryLabel())
                    ->toggleable(),
                Tables\Columns\TextColumn::make('price_cents')
                    ->label('Price')
                    ->formatStateUsing(fn (int $state): string => 'GHS '.number_format($state / 100, 2))
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock_quantity')
                    ->label('Stock')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (ProductStatus $state): string => match ($state) {
                        ProductStatus::Active => 'success',
                        ProductStatus::Draft => 'gray',
                    })
                    ->formatStateUsing(fn (ProductStatus $state): string => $state->label()),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(ProductStatus::cases())->mapWithKeys(
                        fn (ProductStatus $status) => [$status->value => $status->label()]
                    )->all()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('publish')
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
                    }),
                Tables\Actions\Action::make('unpublish')
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
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user.vendorApplication']);
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
            'index' => Pages\ListProducts::route('/'),
            'view' => Pages\ViewProduct::route('/{record}'),
        ];
    }
}
