<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Enums\VendorApplicationStatus;
use App\Filament\Resources\VendorUserResource\Pages;
use App\Models\User;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VendorUserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $slug = 'vendors';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'vendor';

    protected static ?string $pluralModelLabel = 'vendors';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('role', UserRole::Vendor)
            ->with('vendorApplication');
    }

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
                    ->sortable(),
                Tables\Columns\TextColumn::make('vendorApplication.shop_name')
                    ->label('Shop')
                    ->placeholder('—')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('vendorApplication.phone')
                    ->label('Phone')
                    ->placeholder('—')
                    ->searchable(),
                Tables\Columns\TextColumn::make('vendorApplication.status')
                    ->label('Application')
                    ->badge()
                    ->placeholder('—')
                    ->color(fn (?VendorApplicationStatus $state): string => match ($state) {
                        VendorApplicationStatus::Pending => 'warning',
                        VendorApplicationStatus::Approved => 'success',
                        VendorApplicationStatus::Rejected => 'danger',
                        VendorApplicationStatus::Closed => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?VendorApplicationStatus $state): string => $state?->label() ?? '—'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Joined')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVendorUsers::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
