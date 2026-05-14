<?php

namespace App\Models;

use Database\Factories\CollectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['title', 'slug', 'description', 'image_url'])]
class Collection extends Model
{
    /** @use HasFactory<CollectionFactory> */
    use HasFactory;

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (Collection $collection) {
            if (empty($collection->slug)) {
                $collection->slug = Str::slug($collection->title);
            }
        });
    }

    /**
     * Get the products for the collection.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
