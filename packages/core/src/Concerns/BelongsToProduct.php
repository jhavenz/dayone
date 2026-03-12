<?php

declare(strict_types=1);

namespace DayOne\Concerns;

use DayOne\Models\Product;
use DayOne\Models\Scopes\ProductScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait BelongsToProduct
{
    public static function bootBelongsToProduct(): void
    {
        static::addGlobalScope(new ProductScope());
    }

    public function initializeBelongsToProduct(): void
    {
        $this->fillable = array_merge($this->fillable, ['product_id']);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
