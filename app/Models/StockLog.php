<?php

namespace App\Models;

use Database\Factories\StockLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'product_id',
    'qty_before',
    'qty_after',
    'change_type',
    'reason',
    'admin_id',
])]
class StockLog extends Model
{
    /** @use HasFactory<StockLogFactory> */
    use HasFactory;

    /**
     * Get the product that the stock log belongs to.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the admin that created the stock log.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
