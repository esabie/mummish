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
            ->withTrashed()
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
                    ->label('Email')
                    ->state(fn (User $record): string => $record->displayEmail())
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $inner) use ($search) {
                            $inner->where('email', 'like', "%{$search}%")
                                ->orWhere('email_before_deletion', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('vendorApplication.phone')
                    ->label('Phone')
                    ->placeholder('—')
                    ->searchable(),
                Tables\Columns\TextColumn::make('vendorApplication.payment_method')
                    ->label('Payment')
                    ->badge()
                    ->color(fn (User $record): string => $record->vendorApplication?->payment_method ? 'success' : 'danger')
                    ->formatStateUsing(fn (User $record): string => $record->vendorApplication?->payment_method
                        ? ($record->vendorApplication?->paymentMethodLabel() ?? 'Saved')
                        : 'Payment details missing'),
                Tables\Columns\TextColumn::make('vendorApplication.bank_account_number')
                    ->label('Bank/MoMo number')
                    ->placeholder('—')
                    ->formatStateUsing(fn (User $record): string => $record->vendorApplication?->bank_account_number
                        ?? $record->vendorApplication?->mobile_money_number
                        ?? '—')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('vendorApplication.status')
                    ->label('Application')
                    ->badge()
                    ->placeholder('—')
                    ->color(fn (?VendorApplicationStatus $state): string => match ($state) {
                        VendorApplicationStatus::Pending => 'warning',
                        VendorApplicationStatus::Approved => 'success',
                        VendorApplicationStatus::Rejected => 'danger',
                        VendorApplicationStatus::Closed => 'gray',
                        VendorApplicationStatus::Deleted => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?VendorApplicationStatus $state): string => $state?->label() ?? '—'),
                Tables\Columns\TextColumn::make('account_status')
                    ->label('Account')
                    ->badge()
                    ->state(fn (User $record): string => $record->trashed() ? 'Deleted' : 'Active')
                    ->color(fn (User $record): string => $record->trashed() ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Deleted at')
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Joined')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('deleted_at')
                    ->label('Account deleted')
                    ->placeholder('All vendors')
                    ->trueLabel('Deleted')
                    ->falseLabel('Active')
                    ->queries(
                        true: fn (Builder $query) => $query->onlyTrashed(),
                        false: fn (Builder $query) => $query->withoutTrashed(),
                        blank: fn (Builder $query) => $query,
                    ),
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
