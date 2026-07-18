<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'vendor_user_id',
        'product_title',
        'product_brand',
        'product_sku',
        'product_image',
        'attributes',
        'unit_price_cents',
        'quantity',
        'line_total_cents',
    ];

    protected $casts = [
        'unit_price_cents' => 'integer',
        'quantity' => 'integer',
        'line_total_cents' => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_user_id');
    }

    public function formattedUnitPrice(): string
    {
        return 'GHS '.number_format($this->unit_price_cents / 100, 2);
    }

    public function formattedLineTotal(): string
    {
        return 'GHS '.number_format($this->line_total_cents / 100, 2);
    }
}
