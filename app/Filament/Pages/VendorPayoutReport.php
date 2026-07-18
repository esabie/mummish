<?php

namespace App\Filament\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\OrderItem;
use App\Models\VendorApplication;
use App\Services\VendorPayoutReportService;
use Filament\Actions;
use Filament\Forms;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VendorPayoutReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Vendor payouts';

    protected static ?string $title = 'Vendor payouts';

    protected static ?int $navigationSort = 5;

    protected static string $view = 'filament.pages.vendor-payout-report';

    public function table(Table $table): Table
    {
        return $table
            ->query(app(VendorPayoutReportService::class)->paidOrderItemsQuery())
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('order.paid_at')
                    ->label('Paid')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('order.order_number')
                    ->label('Order #')
                    ->searchable()
                    ->sortable()
                    ->url(fn (OrderItem $record): string => OrderResource::getUrl('view', ['record' => $record->order_id])),
                Tables\Columns\TextColumn::make('vendor.vendorApplication.shop_name')
                    ->label('Shop')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('vendor.email')
                    ->label('Vendor email')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('product_title')
                    ->label('Product')
                    ->searchable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('product_sku')
                    ->label('SKU')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Qty')
                    ->sortable(),
                Tables\Columns\TextColumn::make('unit_price_cents')
                    ->label('Unit price')
                    ->formatStateUsing(fn (int $state): string => 'GHS '.number_format($state / 100, 2))
                    ->sortable(),
                Tables\Columns\TextColumn::make('line_total_cents')
                    ->label('Line total')
                    ->formatStateUsing(fn (int $state): string => 'GHS '.number_format($state / 100, 2))
                    ->sortable()
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make()
                            ->formatStateUsing(fn ($state): string => 'GHS '.number_format(((int) $state) / 100, 2))
                    ),
            ])
            ->filters([
                Tables\Filters\Filter::make('paid_between')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From')
                            ->native(false),
                        Forms\Components\DatePicker::make('until')
                            ->label('Until')
                            ->native(false),
                    ])
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['from'] ?? null) {
                            $indicators[] = Indicator::make('From '.$data['from'])->removeField('from');
                        }

                        if ($data['until'] ?? null) {
                            $indicators[] = Indicator::make('Until '.$data['until'])->removeField('until');
                        }

                        return $indicators;
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->whereHas('order', function (Builder $orderQuery) use ($data): void {
                            if ($data['from'] ?? null) {
                                $orderQuery->whereDate('paid_at', '>=', $data['from']);
                            }

                            if ($data['until'] ?? null) {
                                $orderQuery->whereDate('paid_at', '<=', $data['until']);
                            }
                        });
                    }),
                Tables\Filters\SelectFilter::make('vendor_user_id')
                    ->label('Shop')
                    ->options(fn (): array => VendorApplication::query()
                        ->orderBy('shop_name')
                        ->pluck('shop_name', 'user_id')
                        ->all())
                    ->searchable(),
            ])
            ->filtersFormColumns(2)
            ->paginated([10, 25, 50, 100])
            ->emptyStateHeading('No paid orders')
            ->emptyStateDescription('Paid order line items will appear here for vendor settlement.')
            ->actions([])
            ->bulkActions([]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('exportCsv')
                ->label('Export CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(function (): StreamedResponse {
                    $items = $this->getFilteredSortedTableQuery()
                        ->with(['order', 'vendor.vendorApplication'])
                        ->get();

                    $filename = 'vendor-payouts-'.now()->format('Y-m-d').'.csv';

                    return app(VendorPayoutReportService::class)->csvDownloadResponse($items, $filename);
                }),
        ];
    }

    /**
     * @return Collection<int, array{shop_name: string, vendor_email: ?string, quantity: int, total_cents: int, order_count: int}>
     */
    public function getVendorSummaryProperty(): Collection
    {
        $items = $this->getFilteredTableQuery()
            ->with(['vendor.vendorApplication'])
            ->get();

        return app(VendorPayoutReportService::class)->vendorTotals($items);
    }
}
