<?php

namespace App\Models;

use Database\Factories\JastipRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable([
    'user_id',
    'product_name',
    'product_link',
    'image_url',
    'quantity',
    'notes',
    'status',
    'quote',
])]
class JastipRequest extends Model
{
    /** @use HasFactory<JastipRequestFactory> */
    use HasFactory;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (JastipRequest $request) {
            if (empty($request->id)) {
                $request->id = 'JS-'.now()->format('Ymd').'-'.Str::upper(Str::random(4));
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'quote' => 'decimal:2',
        ];
    }

    /**
     * Get the user that owns the jastip request.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
