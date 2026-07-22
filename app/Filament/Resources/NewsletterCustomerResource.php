<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NewsletterCustomerResource\Pages;
use App\Models\NewsletterCustomer;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class NewsletterCustomerResource extends Resource
{
    protected static ?string $model = NewsletterCustomer::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationLabel = 'Newsletter';

    protected static ?string $modelLabel = 'newsletter customer';

    protected static ?string $pluralModelLabel = 'newsletter customers';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 11;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->placeholder('—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Joined')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNewsletterCustomers::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
