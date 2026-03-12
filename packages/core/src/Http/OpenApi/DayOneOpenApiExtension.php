<?php

declare(strict_types=1);

namespace DayOne\Http\OpenApi;

use Dedoc\Scramble\Extensions\OperationExtension;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Parameter;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Dedoc\Scramble\Support\RouteInfo;

/**
 * Adds DayOne product context headers to every operation in the OpenAPI spec.
 * Scramble calls handle() for each route it documents -- we inject the
 * X-Product header so consumers know they must identify which product
 * they're operating against.
 */
final class DayOneOpenApiExtension extends OperationExtension
{
    public function handle(Operation $operation, RouteInfo $routeInfo): void
    {
        $operation->addParameters([
            Parameter::make('X-Product', 'header')
                ->setSchema(Schema::fromType(new StringType))
                ->description('Product slug identifying the target product context')
                ->required(true),
        ]);
    }
}
