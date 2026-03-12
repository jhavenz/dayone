<?php

declare(strict_types=1);

it('registers the dayone config', function (): void {
    expect(config('dayone'))->toBeArray();
});

it('publishes the config file', function (): void {
    $paths = app()->make('config')->get('dayone');
    expect($paths)->toBeArray();

    $publishable = \Illuminate\Support\ServiceProvider::$publishes[\DayOne\DayOneServiceProvider::class] ?? [];
    expect($publishable)->not->toBeEmpty();
});
