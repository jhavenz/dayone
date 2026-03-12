<?php

declare(strict_types=1);

namespace DayOne\Concerns;

use DayOne\Contracts\Context\V1\ProductContext;
use DayOne\Ejection\EjectionManager;
use DayOne\Exceptions\ConcernEjectedException;

trait GuardsEjection
{
    protected function guardEjection(string $concern): void
    {
        $context = app(ProductContext::class);

        if (! $context->hasProduct()) {
            return;
        }

        if (app(EjectionManager::class)->isEjected($context->requireProduct(), $concern)) {
            throw ConcernEjectedException::forConcern($concern, $context->requireProduct());
        }
    }
}
