<?php

declare(strict_types=1);

namespace DayOne\Tests\Fixtures\Jobs;

use DayOne\Concerns\ProductAware;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

final class TestProductAwareJob implements ShouldQueue
{
    use Dispatchable;
    use ProductAware;
    use Queueable;

    public ?string $resolvedProductSlug = null;

    public function __construct()
    {
        $this->initializeProductAware();
    }

    public function handle(): void
    {
        $this->restoreProductContext();
    }
}
