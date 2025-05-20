<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShopifyOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'shop_id',
        'order_id',
        'order_number',
        'name',
        'email',
        'financial_status',
        'fulfillment_status',
        'total_price',
        'currency',
        'billing_address',
        'shipping_address',
        'customer',
        'line_items',
        'shipping_lines',
        'discount_codes',
        'note_attributes',
        'tags',
        'test',
        'processed_at',
        'shareex_shipping_city',
        'shipping_status',
    ];

    protected $casts = [
        'billing_address' => 'array',
        'shipping_address' => 'array',
        'customer' => 'array',
        'line_items' => 'array',
        'shipping_lines' => 'array',
        'discount_codes' => 'array',
        'note_attributes' => 'array',
        'processed_at' => 'datetime',
    ];

    public function shop(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'shop_id');
    }
}
