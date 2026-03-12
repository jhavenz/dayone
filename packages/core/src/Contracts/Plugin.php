<?php

declare(strict_types=1);

namespace DayOne\Contracts;

interface Plugin
{
    public function name(): string;

    public function version(): string;

    public function install(): void;

    public function uninstall(): void;

    public function boot(): void;
}
