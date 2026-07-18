<?php

namespace App\Filament\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\OrderItem;
use App\Models\VendorApplication;
use App\Services\VendorEarningsService;
use App\Services\VendorPayoutReportService;
use Filament\Forms;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PlatformEarnings extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Platform earnings';

    protected static ?string $title = 'Platform earnings';

    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.pages.platform-earnings';

    public function table(Table $table): Table
    {
        $earnings = app(VendorEarningsService::class);

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
                Tables\Columns\TextColumn::make('product_title')
                    ->label('Product')
                    ->searchable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('line_total_cents')
                    ->label('Gross')
                    ->formatStateUsing(fn (int $state): string => 'GHS '.number_format($state / 100, 2))
                    ->sortable(),
                Tables\Columns\TextColumn::make('commission_cents')
                    ->label('Mummish fee')
                    ->getStateUsing(fn (OrderItem $record): int => $earnings->splitOrderItem($record)['commission_cents'])
                    ->formatStateUsing(fn (int $state): string => 'GHS '.number_format($state / 100, 2)),
                Tables\Columns\TextColumn::make('payout_cents')
                    ->label('Vendor payout')
                    ->getStateUsing(fn (OrderItem $record): int => $earnings->splitOrderItem($record)['payout_cents'])
                    ->formatStateUsing(fn (int $state): string => 'GHS '.number_format($state / 100, 2)),
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
            ->emptyStateDescription('Paid order line items will appear here with commission breakdown.')
            ->actions([])
            ->bulkActions([]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getEarningsSummaryProperty(): array
    {
        $items = $this->getFilteredTableQuery()
            ->with(['order', 'vendor.vendorApplication'])
            ->get();

        return app(VendorEarningsService::class)->platformSummary($items);
    }
}
