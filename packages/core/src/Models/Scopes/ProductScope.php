<?php

declare(strict_types=1);

namespace DayOne\Models\Scopes;

use DayOne\Contracts\Context\V1\ProductContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

final class ProductScope implements Scope
{
    /** @param Builder<Model> $builder */
    public function apply(Builder $builder, Model $model): void
    {
        $context = app(ProductContext::class);

        if ($context->hasProduct()) {
            $builder->where($model->getTable() . '.product_id', $context->product()?->id);
        }
    }
}
