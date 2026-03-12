<?php

declare(strict_types=1);

use DayOne\Admin\DayOnePlugin;

it('has the correct plugin id', function (): void {
    $plugin = DayOnePlugin::make();

    expect($plugin->getId())->toBe('dayone');
});

it('static make returns an instance', function (): void {
    $plugin = DayOnePlugin::make();

    expect($plugin)->toBeInstanceOf(DayOnePlugin::class);
});
