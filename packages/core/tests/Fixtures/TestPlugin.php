<?php

declare(strict_types=1);

namespace DayOne\Tests\Fixtures;

use DayOne\Contracts\Plugin;

final class TestPlugin implements Plugin
{
    public bool $installed = false;

    public bool $uninstalled = false;

    public bool $booted = false;

    public function name(): string
    {
        return 'test-plugin';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function install(): void
    {
        $this->installed = true;
    }

    public function uninstall(): void
    {
        $this->uninstalled = true;
    }

    public function boot(): void
    {
        $this->booted = true;
    }
}
