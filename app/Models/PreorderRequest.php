<?php

namespace App\Models;

use Database\Factories\PreorderRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'product_id',
    'notes',
    'status',
])]
class PreorderRequest extends Model
{
    /** @use HasFactory<PreorderRequestFactory> */
    use HasFactory;

    /**
     * Get the user that made the preorder request.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the product that the preorder request is for.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
