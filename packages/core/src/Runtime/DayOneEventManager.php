<?php

declare(strict_types=1);

namespace DayOne\Runtime;

use DayOne\Contracts\Events\V1\EventManager;
use Illuminate\Contracts\Events\Dispatcher;

final class DayOneEventManager implements EventManager
{
    /** @var array<string, array<string, array<string>>> */
    private array $productListeners = [];

    public function __construct(
        private readonly Dispatcher $dispatcher,
    ) {}

    public function dispatch(object $event): void
    {
        $this->dispatcher->dispatch($event);

        if (property_exists($event, 'productSlug') && is_string($event->productSlug)) {
            $shortName = (new \ReflectionClass($event))->getShortName();
            $scopedName = "dayone.{$event->productSlug}.{$shortName}";

            $this->dispatcher->dispatch($scopedName, [$event]);
        }
    }

    public function registerProductListener(string $productSlug, string $event, string $listener): void
    {
        $this->productListeners[$productSlug][$event][] = $listener;

        $this->dispatcher->listen($event, function (object $firedEvent) use ($productSlug, $listener): void {
            if (! property_exists($firedEvent, 'productSlug')) {
                return;
            }

            if ($firedEvent->productSlug !== $productSlug) {
                return;
            }

            app($listener)->handle($firedEvent);
        });
    }

    /** @return array<string, array<string>> */
    public function getProductListeners(string $productSlug): array
    {
        return $this->productListeners[$productSlug] ?? [];
    }
}
